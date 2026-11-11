<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\DoctorConsultationOption;
use App\Models\DoctorProfile;
use App\Services\AppointmentAvailabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AppointmentManagementController extends Controller
{
    public function index(Request $request): View
    {
        $query = Appointment::with(['patient', 'doctorProfile.user', 'payments']);
        $query->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')));
        $query->when($request->filled('payment_status'), fn ($q) => $q->whereHas('payments', fn ($p) => $p->where('status', $request->string('payment_status'))));
        $query->when($request->filled('date'), fn ($q) => $q->whereDate('starts_at', $request->date('date')));
        $query->when($request->filled('search'), fn ($q) => $q->where(fn ($sub) => $sub->whereHas('patient', fn ($u) => $u->where('name', 'like', '%'.$request->search.'%'))->orWhereHas('doctorProfile.user', fn ($u) => $u->where('name', 'like', '%'.$request->search.'%'))));
        $appointments = $query->orderByDesc('starts_at')->paginate(25)->withQueryString();
        return view('admin.appointments.index', compact('appointments'));
    }

    public function show(Appointment $appointment): View
    {
        return view('admin.appointments.show', ['appointment' => $appointment->load(['patient', 'doctorProfile.user', 'payments.refunds', 'events', 'emailOutbox.recipient', 'cancellationRequest.requester', 'cancellationRequest.reviewer', 'refundCases'])]);
    }

    public function resolve(Request $request, Appointment $appointment, AppointmentAvailabilityService $availability)
    {
        $data = $request->validate(['decision' => ['required', 'in:confirm,cancel']]);
        DB::transaction(function () use ($request, $appointment, $data, $availability): void {
            $locked = Appointment::whereKey($appointment->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== Appointment::PENDING_CONFIRMATION) abort(409);
            $from = $locked->status;
            if ($data['decision'] === 'confirm') {
                $profile = DoctorProfile::whereKey($locked->doctor_profile_id)->lockForUpdate()->firstOrFail();
                $option = DoctorConsultationOption::where('doctor_profile_id', $profile->id)->where('type', $locked->consultation_type)->where('is_active', true)->first();
                if (! $option || ! $availability->slotIsAvailable($profile, $option, $locked->localStartsAt(), $locked->id)) {
                    throw ValidationException::withMessages(['decision' => 'The original slot is no longer available. Cancel this booking and contact the patient about the captured payment.']);
                }
                $locked->update(['status' => Appointment::CONFIRMED, 'slot_lock_key' => 'doctor:'.$profile->id.':'.$locked->startsAtUtc()->format('YmdHis')]);
            } else {
                $locked->update(['status' => Appointment::CANCELLED, 'slot_lock_key' => null, 'cancelled_by' => $request->user()->id, 'cancellation_reason' => 'Cancelled during manual review of a late payment. No refund was issued automatically.']);
            }
            $locked->events()->create(['actor_user_id' => $request->user()->id, 'event_type' => 'admin_resolved_late_payment', 'from_status' => $from, 'to_status' => $locked->fresh()->status, 'details' => ['decision' => $data['decision'], 'payment_remains_recorded' => true], 'created_at' => now()]);
        }, attempts: 3);
        return back()->with('status', 'Late payment booking review saved. The payment record remains unchanged.');
    }
}
