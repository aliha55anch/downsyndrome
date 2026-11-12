<?php

namespace App\Http\Controllers\Doctor;

use App\Contracts\MeetingProvider;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AppointmentCancellationRequest;
use App\Models\DoctorAppointmentSlot;
use App\Models\PaymentTransaction;
use App\Services\AppointmentEmailDispatcher;
use App\Services\AppointmentLifecycleNotifier;
use App\Services\AppointmentRefundCaseService;
use App\Services\AppointmentAvailabilityService;
use App\Models\DoctorConsultationOption;
use App\Models\DoctorProfile;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    public function cancel(Request $request, Appointment $appointment, AppointmentEmailDispatcher $emails, MeetingProvider $meetingProvider, AppointmentRefundCaseService $refundCases, AppointmentLifecycleNotifier $notifier)
    {
        $profile = $request->user()->doctorProfile()->firstOrFail();
        abort_unless($appointment->doctor_profile_id === $profile->id, 404);
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);
        DB::transaction(function () use ($appointment, $request, $data): void {
            $locked = Appointment::whereKey($appointment->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($locked->status, [Appointment::CONFIRMED, Appointment::RESCHEDULED], true), 409);
            abort_if($locked->startsAtUtc()->lessThanOrEqualTo(now('UTC')), 409, 'A started appointment cannot be cancelled here.');
            $from = $locked->status;
            $locked->update(['status' => Appointment::CANCELLED, 'slot_lock_key' => null, 'cancelled_by' => $request->user()->id, 'cancellation_reason' => $data['reason'], 'meeting_link' => null]);
            if ($locked->doctor_appointment_slot_id) DoctorAppointmentSlot::whereKey($locked->doctor_appointment_slot_id)->where('status', DoctorAppointmentSlot::BOOKED)->update(['status' => DoctorAppointmentSlot::OPEN]);
            $locked->events()->create(['actor_user_id' => $request->user()->id, 'event_type' => 'cancelled_by_doctor', 'from_status' => $from, 'to_status' => Appointment::CANCELLED, 'details' => ['reason' => $data['reason']], 'created_at' => now()]);
        });
        $meetingProvider->revoke($appointment);
        $fresh = $appointment->fresh();
        $refundCase = $refundCases->requestRemainingAmount($fresh, $request->user(), $data['reason']);
        $emails->dispatch($fresh, 'appointment_cancelled');
        $notifier->notify($fresh, 'appointment_cancelled', 'Appointment cancelled', 'Your doctor cancelled appointment LS-APT-'.$fresh->id.'. Reason: '.$data['reason'], true);
        if ($refundCase) $notifier->notify($fresh, 'refund_requested', 'Refund review started', 'A refund of '.number_format($refundCase->amount_minor / 100, 2).' '.$refundCase->currency.' is awaiting payment processing.', true);
        return back()->with('status', 'Appointment cancelled and the time released. Paid transactions remain recorded for reconciliation.');
    }

    public function reviewCancellation(Request $request, Appointment $appointment, AppointmentEmailDispatcher $emails, MeetingProvider $meetingProvider, AppointmentRefundCaseService $refundCases, AppointmentLifecycleNotifier $notifier)
    {
        $profile = $request->user()->doctorProfile()->firstOrFail();
        abort_unless($appointment->doctor_profile_id === $profile->id, 404);
        $data = $request->validate(['decision' => ['required', 'in:approve,reject'], 'response' => ['nullable', 'string', 'max:1000']]);
        $refundReason = null;
        DB::transaction(function () use ($appointment, $request, $data, &$refundReason): void {
            $locked = Appointment::whereKey($appointment->id)->lockForUpdate()->firstOrFail();
            $cancelRequest = $locked->cancellationRequest()->lockForUpdate()->firstOrFail();
            abort_unless($cancelRequest->status === AppointmentCancellationRequest::PENDING
                && $locked->status === Appointment::PENDING_CANCELLATION, 409);
            $from = $locked->status;
            $approved = $data['decision'] === 'approve';
            $cancelRequest->update([
                'status' => $approved ? AppointmentCancellationRequest::APPROVED : AppointmentCancellationRequest::REJECTED,
                'reviewed_by' => $request->user()->id, 'response' => $data['response'] ?? null, 'reviewed_at' => now(),
            ]);
            $locked->update($approved
                ? ['status' => Appointment::CANCELLED, 'cancelled_by' => $cancelRequest->requested_by, 'cancellation_reason' => $cancelRequest->reason, 'slot_lock_key' => null, 'meeting_link' => null]
                : ['status' => $cancelRequest->appointment_status_before, 'cancelled_by' => null, 'cancellation_reason' => null]);
            if ($approved && $locked->doctor_appointment_slot_id) DoctorAppointmentSlot::whereKey($locked->doctor_appointment_slot_id)->where('status', DoctorAppointmentSlot::BOOKED)->update(['status' => DoctorAppointmentSlot::OPEN]);
            $locked->events()->create([
                'actor_user_id' => $request->user()->id, 'event_type' => 'patient_cancellation_request_'.$data['decision'],
                'from_status' => $from, 'to_status' => $locked->fresh()->status,
                'details' => ['reason' => $cancelRequest->reason, 'response' => $data['response'] ?? null], 'created_at' => now(),
            ]);
            if ($approved) $refundReason = $cancelRequest->reason;
        });
        $fresh = $appointment->fresh();
        if ($data['decision'] === 'approve') $meetingProvider->revoke($appointment);
        $refundCase = $refundReason ? $refundCases->requestRemainingAmount($fresh, $request->user(), 'Patient cancellation approved: '.$refundReason) : null;
        $emails->dispatch($fresh, $data['decision'] === 'approve' ? 'cancellation_approved' : 'cancellation_rejected');
        $notifier->notify($fresh, 'cancellation_'.$data['decision'], $data['decision'] === 'approve' ? 'Cancellation approved' : 'Cancellation request declined', $data['decision'] === 'approve'
            ? 'Your cancellation request for LS-APT-'.$fresh->id.' was approved.'
            : 'Your cancellation request for LS-APT-'.$fresh->id.' was declined. '.($data['response'] ?? ''), true);
        if ($refundCase) $notifier->notify($fresh, 'refund_requested', 'Refund review started', 'A refund is awaiting payment processing.', true);
        return back()->with('status', $data['decision'] === 'approve' ? 'Cancellation approved; refund review was queued where payment had been captured.' : 'Cancellation request declined; the appointment remains active.');
    }

    public function reschedule(Request $request, Appointment $appointment, AppointmentAvailabilityService $availability, AppointmentEmailDispatcher $emails, MeetingProvider $meetingProvider, AppointmentLifecycleNotifier $notifier)
    {
        $profile = $request->user()->doctorProfile()->firstOrFail();
        abort_unless($appointment->doctor_profile_id === $profile->id, 404);
        $data = $request->validate(['starts_at' => ['required', 'date_format:Y-m-d\\TH:i']]);
        $localStart = CarbonImmutable::createFromFormat('!Y-m-d\\TH:i', $data['starts_at'], $appointment->timezone);
        DB::transaction(function () use ($appointment, $request, $availability, $localStart): void {
            $locked = Appointment::whereKey($appointment->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($locked->status, [Appointment::CONFIRMED, Appointment::RESCHEDULED], true), 409);
            abort_if($locked->startsAtUtc()->lessThanOrEqualTo(now('UTC')->addHours(24)), 409, 'Appointments can be changed up to 24 hours before their start.');
            $profile = DoctorProfile::whereKey($locked->doctor_profile_id)->lockForUpdate()->firstOrFail();
            $option = DoctorConsultationOption::where('doctor_profile_id', $profile->id)->where('type', $locked->consultation_type)->where('is_active', true)->first();
            if (! $option || ! $availability->slotIsAvailable($profile, $option, $localStart, $locked->id)) {
                throw ValidationException::withMessages(['starts_at' => 'That replacement time is no longer available.']);
            }
            $previous = ['starts_at' => $locked->startsAtUtc()->toIso8601String(), 'ends_at' => $locked->endsAtUtc()->toIso8601String()];
            $locked->update(['starts_at' => $localStart->utc(), 'ends_at' => $localStart->addMinutes($locked->duration_minutes)->utc(), 'status' => Appointment::RESCHEDULED, 'slot_lock_key' => 'doctor:'.$profile->id.':'.$localStart->utc()->format('YmdHis'), 'meeting_link' => null, 'reschedule_count' => $locked->reschedule_count + 1, 'reminder_24h_sent_at' => null, 'reminder_1h_sent_at' => null]);
            $locked->events()->create(['actor_user_id' => $request->user()->id, 'event_type' => 'rescheduled_by_doctor', 'from_status' => $appointment->status, 'to_status' => Appointment::RESCHEDULED, 'details' => ['previous' => $previous], 'created_at' => now()]);
        }, attempts: 3);
        $meetingProvider->revoke($appointment);
        app(\App\Services\AppointmentMeetingService::class)->provision($appointment->fresh());
        $emails->dispatch($appointment->fresh(), 'appointment_rescheduled');
        $notifier->notify($appointment->fresh(), 'appointment_rescheduled', 'Appointment rescheduled', 'Your doctor rescheduled appointment LS-APT-'.$appointment->id.'.');
        return back()->with('status', 'Appointment rescheduled and confirmation sent. Add a new meeting link if needed.');
    }
    public function index(Request $request): View
    {
        $profile = $request->user()->doctorProfile()->firstOrFail();
        $filters = $request->validate([
            'status' => ['nullable', 'string', 'in:'.implode(',', Appointment::STATUSES)],
            'consultation_type' => ['nullable', 'in:online,in_person'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);
        $base = $profile->appointments()->with(['patient', 'payments', 'prescription', 'cancellationRequest'])
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['consultation_type'] ?? null, fn ($query, $type) => $query->where('consultation_type', $type))
            ->when($filters['date_from'] ?? null, fn ($query, $date) => $query->where('starts_at', '>=', CarbonImmutable::parse($date, $profile->timezone)->startOfDay()->utc()))
            ->when($filters['date_to'] ?? null, fn ($query, $date) => $query->where('starts_at', '<', CarbonImmutable::parse($date, $profile->timezone)->addDay()->startOfDay()->utc()));
        $upcoming = (clone $base)->whereIn('status', [Appointment::CONFIRMED, Appointment::RESCHEDULED])->where('starts_at', '>=', now('UTC'))->orderBy('starts_at')->paginate(10, ['*'], 'upcoming_page');
        $pending = (clone $base)->where(function ($query): void {
            $query->whereIn('status', [Appointment::PENDING_PAYMENT, Appointment::PENDING_CONFIRMATION])
                ->orWhere(function ($past) {
                    $past->whereIn('status', [Appointment::CONFIRMED, Appointment::RESCHEDULED])->where('starts_at', '<', now('UTC'));
                });
        })->orderBy('starts_at')->paginate(10, ['*'], 'pending_page');
        $completed = (clone $base)->whereIn('status', [Appointment::COMPLETED, Appointment::NO_SHOW])->orderByDesc('starts_at')->paginate(10, ['*'], 'completed_page');
        $closed = (clone $base)->whereIn('status', [Appointment::CANCELLED, Appointment::EXPIRED])->orderByDesc('starts_at')->paginate(10, ['*'], 'closed_page');
        $cancellationRequests = $profile->appointments()->with(['patient', 'cancellationRequest'])
            ->where('status', Appointment::PENDING_CANCELLATION)->orderBy('starts_at')->paginate(10, ['*'], 'cancellation_page');
        return view('doctor.appointments.index', compact('upcoming', 'pending', 'completed', 'closed', 'cancellationRequests'));
    }

    public function history(Request $request): View
    {
        $profile = $request->user()->doctorProfile()->firstOrFail();
        $appointments = $profile->appointments()->with(['patient', 'payments'])
            ->whereIn('status', [Appointment::COMPLETED, Appointment::NO_SHOW, Appointment::CANCELLED, Appointment::EXPIRED])
            ->orderByDesc('starts_at')->paginate(20);

        return view('doctor.appointments.history', compact('appointments'));
    }

    public function show(Request $request, Appointment $appointment): View
    {
        $profile = $request->user()->doctorProfile()->firstOrFail();
        abort_unless($appointment->doctor_profile_id === $profile->id, 404);
        return view('doctor.appointments.show', ['appointment' => $appointment->load(['patient', 'doctorSlot', 'payments', 'events', 'cancellationRequest', 'prescription'])]);
    }

    public function update(Request $request, Appointment $appointment, AppointmentEmailDispatcher $emails, AppointmentLifecycleNotifier $notifier)
    {
        $profile = $request->user()->doctorProfile()->firstOrFail();
        abort_unless($appointment->doctor_profile_id === $profile->id, 404);
        $data = $request->validate(['status' => ['required', Rule::in([Appointment::COMPLETED, Appointment::NO_SHOW])]]);
        DB::transaction(function () use ($appointment, $request, $data): void {
            $locked = Appointment::whereKey($appointment->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($locked->status, [Appointment::CONFIRMED, Appointment::RESCHEDULED], true), 409);
            abort_if($locked->startsAtUtc()->greaterThan(now('UTC')), 409, 'An appointment can be marked completed or no-show only after its scheduled start time.');
            $from = $locked->status;
            $locked->update(['status' => $data['status'], 'slot_lock_key' => null]);
            $locked->events()->create(['actor_user_id' => $request->user()->id, 'event_type' => 'doctor_marked_'.$data['status'], 'from_status' => $from, 'to_status' => $data['status'], 'created_at' => now()]);
        });
        $emails->dispatch($appointment->fresh(), 'appointment_'.$data['status']);
        $notifier->notify($appointment->fresh(), 'appointment_'.$data['status'], $data['status'] === Appointment::COMPLETED ? 'Consultation completed' : 'Appointment marked no-show', 'Your doctor updated appointment LS-APT-'.$appointment->id.'.');
        return back()->with('status', 'Appointment status updated.');
    }

    public function updateMeetingLink(Request $request, Appointment $appointment, AppointmentEmailDispatcher $emails, AppointmentLifecycleNotifier $notifier)
    {
        $profile = $request->user()->doctorProfile()->firstOrFail();
        abort_unless($appointment->doctor_profile_id === $profile->id, 404);
        $data = $request->validate([
            'meeting_link' => ['nullable', 'string', 'max:2048', 'url', 'regex:/^https:\/\//i'],
        ]);

        DB::transaction(function () use ($appointment, $request, $data): void {
            $locked = Appointment::whereKey($appointment->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($locked->status, [Appointment::CONFIRMED, Appointment::RESCHEDULED], true), 409);
            $activePayment = $locked->payments()->latest('id')->first();
            abort_if($activePayment && $activePayment->status !== PaymentTransaction::PAID, 409, 'A meeting link can be shared only after the booking is confirmed with a verified payment.');
            $link = trim((string) ($data['meeting_link'] ?? '')) ?: null;
            if ($link === $locked->meeting_link) return;
            $locked->update(['meeting_link' => $link]);
            $locked->events()->create([
                'actor_user_id' => $request->user()->id,
                'event_type' => $link ? 'meeting_link_added' : 'meeting_link_removed',
                'details' => ['link_present' => (bool) $link],
                'created_at' => now(),
            ]);
        });

        $fresh = $appointment->fresh(['payments']);
        if ($fresh->meeting_link) {
            $emails->dispatch($fresh, 'meeting_link_added', [$fresh->patient]);
            $notifier->notify($fresh, 'meeting_link_added', 'Consultation link available', 'Your doctor added a meeting link for LS-APT-'.$fresh->id.'.');
        }

        return back()->with('status', $fresh->meeting_link ? 'Meeting link saved. The patient can see it on their confirmed appointment.' : 'Meeting link removed.');
    }

    public function revenue(Request $request): View
    {
        $profile = $request->user()->doctorProfile()->firstOrFail();
        $dates = $this->dateRange($request, $profile->timezone);
        $payments = $this->completedPaidPayments($profile->id, ...$dates)->with(['payable', 'refunds'])->get();
        $daily = $payments->groupBy(fn (PaymentTransaction $payment) => $payment->payable->localStartsAt()->toDateString())
            ->map(fn ($items) => ['amount_minor' => $items->sum('amount_minor') - $items->sum(fn ($payment) => $payment->refunds->where('status', 'succeeded')->sum('amount_minor')), 'currency' => $items->first()->currency, 'count' => $items->count()])->sortKeysDesc();
        $monthly = $payments->groupBy(fn (PaymentTransaction $payment) => $payment->payable->localStartsAt()->format('Y-m'))
            ->map(fn ($items) => ['amount_minor' => $items->sum('amount_minor') - $items->sum(fn ($payment) => $payment->refunds->where('status', 'succeeded')->sum('amount_minor')), 'currency' => $items->first()->currency, 'count' => $items->count()])->sortKeysDesc();
        $counts = [
            'completed' => $profile->appointments()->where('status', Appointment::COMPLETED)->count(),
            'pending' => $profile->appointments()->whereIn('status', [Appointment::PENDING_PAYMENT, Appointment::PENDING_CONFIRMATION])->count(),
            'cancelled' => $profile->appointments()->where('status', Appointment::CANCELLED)->count(),
            'no_show' => $profile->appointments()->where('status', Appointment::NO_SHOW)->count(),
        ];
        $totals = $payments->groupBy('currency')->map(fn ($items) => [
            'gross_minor' => $items->sum('amount_minor'),
            'refunded_minor' => $items->sum(fn ($payment) => $payment->refunds->where('status', 'succeeded')->sum('amount_minor')),
            'net_minor' => $items->sum('amount_minor') - $items->sum(fn ($payment) => $payment->refunds->where('status', 'succeeded')->sum('amount_minor')),
            'gateway_fee_minor' => $items->sum('gateway_fee_minor'),
            'provider_net_minor' => $items->sum(fn ($payment) => $payment->gateway_net_minor ?? $payment->amount_minor),
        ]);

        return view('doctor.revenue', compact('daily', 'monthly', 'counts', 'totals') + ['dateFrom' => $request->query('date_from'), 'dateTo' => $request->query('date_to'), 'selectedMonth' => $request->query('month'), 'selectedYear' => $request->query('year')]);
    }

    public function revenueCsv(Request $request)
    {
        $profile = $request->user()->doctorProfile()->firstOrFail();
        $dates = $this->dateRange($request, $profile->timezone);
        $rows = $this->completedPaidPayments($profile->id, ...$dates)->with(['payable.patient', 'refunds'])->orderBy('paid_at')->get();
        $callback = static function () use ($rows): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Paid at (UTC)', 'Appointment date', 'Patient', 'Gross minor units', 'Refunded minor units', 'Net minor units', 'Currency', 'Payment reference']);
            foreach ($rows as $payment) {
                $refunded = $payment->refunds->where('status', 'succeeded')->sum('amount_minor');
                fputcsv($out, [$payment->paid_at?->utc()->toIso8601String(), $payment->payable->localStartsAt()->toIso8601String(), $payment->payable->patient?->name, $payment->amount_minor, $refunded, $payment->amount_minor - $refunded, $payment->currency, $payment->provider_reference]);
            }
            fclose($out);
        };

        return Response::streamDownload($callback, 'doctor-revenue.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function completedPaidPayments(int $profileId, ?CarbonImmutable $from = null, ?CarbonImmutable $through = null)
    {
        return PaymentTransaction::query()->where('payable_type', (new Appointment())->getMorphClass())
            ->whereIn('status', [PaymentTransaction::PAID, 'partially_refunded', 'refunded'])
            ->whereHasMorph('payable', [Appointment::class], function ($query) use ($profileId, $from, $through): void {
                $query->where('doctor_profile_id', $profileId)
                    ->when($from, fn ($q) => $q->where('starts_at', '>=', $from->utc()))
                    ->when($through, fn ($q) => $q->where('starts_at', '<', $through->utc()));
            });
    }

    /** @return array{?CarbonImmutable, ?CarbonImmutable} */
    private function dateRange(Request $request, string $timezone): array
    {
        $data = $request->validate([
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'between:2000,'.(now($timezone)->year + 1)],
        ]);
        if (! empty($data['month']) || ! empty($data['year'])) {
            $year = (int) ($data['year'] ?? now($timezone)->year);
            $period = isset($data['month'])
                ? CarbonImmutable::create($year, (int) $data['month'], 1, 0, 0, 0, $timezone)
                : CarbonImmutable::create($year, 1, 1, 0, 0, 0, $timezone);
            return [$period->startOfMonth(), isset($data['month']) ? $period->addMonth()->startOfMonth() : $period->addYear()->startOfYear()];
        }
        if (! empty($data['date_from']) && ! empty($data['date_to']) && $data['date_from'] > $data['date_to']) {
            throw ValidationException::withMessages(['date_to' => 'The end date must be on or after the start date.']);
        }
        return [
            ! empty($data['date_from']) ? CarbonImmutable::parse($data['date_from'], $timezone)->startOfDay() : null,
            ! empty($data['date_to']) ? CarbonImmutable::parse($data['date_to'], $timezone)->addDay()->startOfDay() : null,
        ];
    }
}

