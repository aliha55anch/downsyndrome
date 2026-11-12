<?php

namespace App\Http\Controllers;

use App\Contracts\MeetingProvider;
use App\Models\Appointment;
use App\Models\AppointmentCancellationRequest;
use App\Models\DoctorConsultationOption;
use App\Models\DoctorProfile;
use App\Models\PaymentTransaction;
use App\Services\AppointmentAvailabilityService;
use App\Services\AppointmentEmailDispatcher;
use App\Services\AppointmentLifecycleNotifier;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ParentAppointmentController extends Controller
{
    public function index(Request $request): View
    {
        if (! $request->user()) {
            return view('appointments.guest-upcoming');
        }
        abort_unless($request->user()->hasRole(\App\Models\User::ROLE_PARENT), 403);
        $base = Appointment::with(['doctorProfile.user', 'doctorSlot', 'payments', 'cancellationRequest'])->where('patient_user_id', $request->user()->id);
        $appointments = (clone $base)->whereIn('status', [Appointment::CONFIRMED, Appointment::RESCHEDULED])->where('starts_at', '>=', now('UTC'))->orderBy('starts_at')->paginate(10, ['*'], 'upcoming_page');
        $attention = (clone $base)->whereIn('status', [Appointment::PENDING_PAYMENT, Appointment::PENDING_CONFIRMATION, Appointment::PENDING_CANCELLATION])->orderBy('starts_at')->paginate(10, ['*'], 'attention_page');
        return view('appointments.index', compact('appointments', 'attention'));
    }

    public function history(Request $request): View
    {
        $filters = $request->validate([
            'status' => ['nullable', 'string', 'in:'.implode(',', Appointment::STATUSES)],
            'consultation_type' => ['nullable', 'string', 'in:online,in_person'],
            'doctor_id' => ['nullable', 'integer', 'min:1'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ]);
        $query = Appointment::with(['doctorProfile.user', 'payments', 'prescription'])
            ->where('patient_user_id', $request->user()->id)
            ->whereNotIn('status', Appointment::BLOCKING_STATUSES)
            ->where(function ($past): void {
                $past->where('starts_at', '<', now('UTC'))->orWhereIn('status', [Appointment::CANCELLED, Appointment::EXPIRED]);
            });
        if (! empty($filters['status'])) $query->where('status', $filters['status']);
        if (! empty($filters['consultation_type'])) $query->where('consultation_type', $filters['consultation_type']);
        if (! empty($filters['doctor_id'])) $query->where('doctor_profile_id', (int) $filters['doctor_id']);
        if (! empty($filters['date_from'])) $query->where('starts_at', '>=', CarbonImmutable::parse($filters['date_from'], 'UTC')->startOfDay());
        if (! empty($filters['date_to'])) $query->where('starts_at', '<', CarbonImmutable::parse($filters['date_to'], 'UTC')->addDay()->startOfDay());
        $appointments = $query->orderByDesc('starts_at')->paginate(15)->withQueryString();

        return view('appointments.history', ['appointments' => $appointments, 'doctors' => DoctorProfile::whereHas('appointments', fn ($q) => $q->where('patient_user_id', $request->user()->id))->with('user')->get()]);
    }

    public function doctorsConsulted(Request $request): View
    {
        $appointments = Appointment::with('doctorProfile.user')
            ->where('patient_user_id', $request->user()->id)
            ->where('status', Appointment::COMPLETED)
            ->orderByDesc('starts_at')->paginate(15);

        return view('appointments.doctors-consulted', compact('appointments'));
    }

    public function paymentHistory(Request $request): View
    {
        $payments = PaymentTransaction::query()->with(['payable.doctorProfile.user'])
            ->where('payer_user_id', $request->user()->id)
            ->where('payable_type', (new Appointment())->getMorphClass())
            ->latest()->paginate(15);
        return view('payments.history', compact('payments'));
    }

    public function receipt(Request $request, PaymentTransaction $payment): View
    {
        abort_unless($payment->payer_user_id === $request->user()->id, 404);
        abort_unless($payment->status === PaymentTransaction::PAID, 404);
        $appointment = $payment->payable;
        abort_unless($appointment instanceof Appointment && $appointment->patient_user_id === $request->user()->id, 404);
        return view('payments.receipt', ['payment' => $payment, 'appointment' => $appointment->load('doctorProfile.user')]);
    }

    public function show(Request $request, Appointment $appointment): View
    {
        abort_unless($appointment->patient_user_id === $request->user()->id, 404);
        return view('appointments.show', ['appointment' => $appointment->load(['doctorProfile.user', 'doctorSlot', 'payments', 'events', 'cancellationRequest', 'prescription'])]);
    }

    public function slots(Request $request, Appointment $appointment, AppointmentAvailabilityService $availability)
    {
        abort_unless($appointment->patient_user_id === $request->user()->id, 404);
        abort_unless(in_array($appointment->status, [Appointment::CONFIRMED, Appointment::RESCHEDULED], true), 409);
        $data = $request->validate(['date' => ['required', 'date_format:Y-m-d']]);
        $option = DoctorConsultationOption::where('doctor_profile_id', $appointment->doctor_profile_id)->where('type', $appointment->consultation_type)->where('is_active', true)->firstOrFail();
        $date = CarbonImmutable::createFromFormat('!Y-m-d', $data['date'], $appointment->timezone);
        return response()->json(['slots' => $availability->slotsForDate($appointment->doctorProfile, $option, $date, $appointment->id)->map(fn ($slot) => ['value' => $slot['value'], 'label' => $slot['label']])]);
    }

    public function cancel(Request $request, Appointment $appointment, AppointmentEmailDispatcher $emails, AppointmentLifecycleNotifier $notifier)
    {
        abort_unless($appointment->patient_user_id === $request->user()->id, 404);
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:1000']]);
        DB::transaction(function () use ($appointment, $request, $data): void {
            $locked = Appointment::whereKey($appointment->id)->lockForUpdate()->firstOrFail();
            if (! in_array($locked->status, [Appointment::CONFIRMED, Appointment::RESCHEDULED], true) || $locked->startsAtUtc()->lessThanOrEqualTo(now('UTC')->addHours(24))) {
                throw ValidationException::withMessages(['appointment' => 'Appointments can be cancelled up to 24 hours before their start time.']);
            }
            $from = $locked->status;
            $pending = $locked->cancellationRequest()->first();
            if ($pending?->status === AppointmentCancellationRequest::PENDING) {
                throw ValidationException::withMessages(['appointment' => 'A cancellation request is already awaiting review.']);
            }
            $refund = $locked->payments()->whereIn('status', [PaymentTransaction::PAID, 'partially_refunded'])->first();
            $refundedMinor = $refund ? (int) $refund->refunds()->where('status', 'succeeded')->sum('amount_minor') : 0;
            $locked->cancellationRequest()->updateOrCreate([], [
                'requested_by' => $request->user()->id, 'reason' => $data['reason'], 'appointment_status_before' => $from,
                'refund_amount_minor' => $refund ? max(0, $refund->amount_minor - $refundedMinor) : 0,
                'status' => AppointmentCancellationRequest::PENDING, 'requested_at' => now(),
                'reviewed_by' => null, 'response' => null, 'reviewed_at' => null,
            ]);
            $locked->update(['status' => Appointment::PENDING_CANCELLATION]);
            $locked->events()->create(['actor_user_id' => $request->user()->id, 'event_type' => 'cancellation_requested_by_patient', 'from_status' => $from, 'to_status' => Appointment::PENDING_CANCELLATION, 'details' => ['reason' => $data['reason']], 'created_at' => now()]);
        });
        $fresh = $appointment->fresh();
        $emails->dispatch($fresh, 'cancellation_requested');
        $notifier->notify($fresh, 'cancellation_requested', 'Cancellation request received', 'A patient has requested cancellation of appointment LS-APT-'.$fresh->id.'.', true);
        return back()->with('status', 'Your cancellation request was sent to the doctor for review. The appointment remains scheduled until a decision is recorded.');
    }

    public function reschedule(Request $request, Appointment $appointment, AppointmentAvailabilityService $availability, AppointmentEmailDispatcher $emails, MeetingProvider $meetingProvider, AppointmentLifecycleNotifier $notifier)
    {
        abort_unless($appointment->patient_user_id === $request->user()->id, 404);
        $data = $request->validate(['starts_at' => ['required', 'date_format:Y-m-d\\TH:i']]);
        $localStart = CarbonImmutable::createFromFormat('!Y-m-d\\TH:i', $data['starts_at'], $appointment->timezone);
        DB::transaction(function () use ($appointment, $request, $availability, $localStart): void {
            $locked = Appointment::whereKey($appointment->id)->lockForUpdate()->firstOrFail();
            if (! in_array($locked->status, [Appointment::CONFIRMED, Appointment::RESCHEDULED], true) || $locked->startsAtUtc()->lessThanOrEqualTo(now('UTC')->addHours(24))) {
                throw ValidationException::withMessages(['appointment' => 'Appointments can be rescheduled up to 24 hours before their start time.']);
            }
            $profile = DoctorProfile::whereKey($locked->doctor_profile_id)->lockForUpdate()->firstOrFail();
            $option = DoctorConsultationOption::where('doctor_profile_id', $profile->id)->where('type', $locked->consultation_type)->where('is_active', true)->first();
            if (! $option || ! $availability->slotIsAvailable($profile, $option, $localStart, $locked->id)) {
                throw ValidationException::withMessages(['starts_at' => 'That replacement time is no longer available.']);
            }
            $previous = ['starts_at' => $locked->startsAtUtc()->toIso8601String(), 'ends_at' => $locked->endsAtUtc()->toIso8601String()];
            $locked->update(['starts_at' => $localStart->utc(), 'ends_at' => $localStart->addMinutes($locked->duration_minutes)->utc(), 'status' => Appointment::RESCHEDULED, 'slot_lock_key' => 'doctor:'.$profile->id.':'.$localStart->utc()->format('YmdHis'), 'reschedule_count' => $locked->reschedule_count + 1, 'reminder_24h_sent_at' => null, 'reminder_1h_sent_at' => null]);
            $locked->events()->create(['actor_user_id' => $request->user()->id, 'event_type' => 'rescheduled_by_patient', 'from_status' => $appointment->status, 'to_status' => Appointment::RESCHEDULED, 'details' => ['previous' => $previous], 'created_at' => now()]);
        }, attempts: 3);
        $meetingProvider->revoke($appointment);
        app(\App\Services\AppointmentMeetingService::class)->provision($appointment->fresh());
        $emails->dispatch($appointment->fresh(), 'appointment_rescheduled');
        $notifier->notify($appointment->fresh(), 'appointment_rescheduled', 'Appointment rescheduled', 'Your appointment LS-APT-'.$appointment->id.' was rescheduled.');
        return back()->with('status', 'Appointment rescheduled.');
    }
}

