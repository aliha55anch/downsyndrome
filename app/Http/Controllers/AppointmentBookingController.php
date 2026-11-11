<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\AppointmentSlotHold;
use App\Models\DoctorConsultationOption;
use App\Models\DoctorProfile;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Services\AppointmentAvailabilityService;
use App\Services\AppointmentBookingService;
use App\Services\PaymentService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class AppointmentBookingController extends Controller
{
    public function create(DoctorProfile $doctorProfile): View
    {
        abort_unless($doctorProfile->isPubliclyBookable(), 404);
        if (auth()->user()->hasRole(User::ROLE_PENDING_DOCTOR, User::ROLE_APPROVED_DOCTOR)) {
            return view('appointments.doctor-booking-restricted', ['profile' => $doctorProfile->load('user')]);
        }

        abort_unless(auth()->user()->hasRole(User::ROLE_PARENT), 403);
        return view('appointments.create', ['profile' => $doctorProfile->load(['user', 'consultationOptions' => fn ($q) => $q->where('is_active', true)]) , 'idempotencyKey' => (string) Str::uuid()]);
    }

    public function slots(Request $request, DoctorProfile $doctorProfile, AppointmentAvailabilityService $availability)
    {
        abort_unless($doctorProfile->isPubliclyBookable(), 404);
        $data = $request->validate(['date' => ['required', 'date_format:Y-m-d'], 'consultation_option_id' => ['required', 'integer']]);
        $option = DoctorConsultationOption::whereKey($data['consultation_option_id'])->where('doctor_profile_id', $doctorProfile->id)->where('is_active', true)->firstOrFail();
        $date = CarbonImmutable::createFromFormat('!Y-m-d', $data['date'], $doctorProfile->timezone);
        return response()->json(['slots' => $availability->slotsForDate($doctorProfile, $option, $date)->map(fn ($slot) => ['value' => $slot['value'], 'label' => $slot['label'], 'slot_id' => $slot['doctor_appointment_slot_id'], 'clinic_location' => $slot['clinic_location']])]);
    }

    public function review(Request $request, DoctorProfile $doctorProfile, AppointmentBookingService $booking): View
    {
        abort_unless($request->user()->hasRole(User::ROLE_PARENT), 403);
        abort_unless($doctorProfile->isPubliclyBookable(), 404);
        $data = $request->validate([
            'consultation_option_id' => ['required', 'integer'],
            'starts_at' => ['required', 'date_format:Y-m-d\\TH:i'],
            'idempotency_key' => ['required', 'uuid'],
            'doctor_appointment_slot_id' => ['nullable', 'integer'],
        ]);
        $option = DoctorConsultationOption::whereKey($data['consultation_option_id'])
            ->where('doctor_profile_id', $doctorProfile->id)->where('is_active', true)->firstOrFail();
        $localStart = CarbonImmutable::createFromFormat('!Y-m-d\\TH:i', $data['starts_at'], $doctorProfile->timezone);
        $hold = $booking->reserveSlot($request->user(), $doctorProfile->id, $option->id, $localStart, $data['idempotency_key'], isset($data['doctor_appointment_slot_id']) ? (int) $data['doctor_appointment_slot_id'] : null);

        return view('appointments.review', [
            'profile' => $doctorProfile->load('user'), 'option' => $option, 'localStart' => $localStart, 'hold' => $hold->load('doctorSlot'),
            'idempotencyKey' => $data['idempotency_key'],
        ]);
    }

    public function releaseSlot(Request $request, AppointmentSlotHold $hold, AppointmentBookingService $booking)
    {
        abort_unless($hold->patient_user_id === $request->user()->id, 404);
        $profile = $hold->doctorProfile;
        $booking->releaseSlotHold($request->user(), $hold);
        return redirect()->route('appointments.create', $profile)->with('status', 'Your temporary time hold was released. Choose another available slot.');
    }

    public function store(Request $request, DoctorProfile $doctorProfile, AppointmentBookingService $booking)
    {
        $data = $request->validate([
            'consultation_option_id' => ['required', 'integer'],
            'starts_at' => ['required', 'date_format:Y-m-d\\TH:i'],
            'idempotency_key' => ['required', 'uuid'],
            'doctor_appointment_slot_id' => ['nullable', 'integer'],
        ]);
        abort_unless($doctorProfile->isPubliclyBookable(), 404);
        abort_unless($doctorProfile->hasPublishedAvailability(), 404);
        $localStart = CarbonImmutable::createFromFormat('!Y-m-d\\TH:i', $data['starts_at'], $doctorProfile->timezone);
        abort_unless($request->user()->hasRole(User::ROLE_PARENT), 403);
        $result = $booking->book($request->user(), $doctorProfile->id, (int) $data['consultation_option_id'], $localStart, $data['idempotency_key'], isset($data['doctor_appointment_slot_id']) ? (int) $data['doctor_appointment_slot_id'] : null);
        $appointment = $result['appointment'];
        if ($result['ended'] ?? false) {
            return redirect()->route('appointments.create', $doctorProfile)
                ->withErrors(['appointment' => 'This booking attempt has ended. Choose an available time to start a new booking.']);
        }
        if ($result['payment']?->checkout_url && $appointment->status === Appointment::PENDING_PAYMENT) {
            return redirect()->away($result['payment']->checkout_url);
        }
        return redirect()->route('appointments.show', $appointment)->with('status', $appointment->status === Appointment::CONFIRMED ? 'Your appointment is confirmed.' : 'Your booking is being processed.');
    }

    public function paymentReturn(Request $request, Appointment $appointment)
    {
        abort_unless($request->user()->id === $appointment->patient_user_id, 404);

        // Safepay's browser redirect is not proof of payment. Once the signed
        // webhook has marked the payment paid, send the patient to the booked
        // appointment page. Until then, show a waiting page that checks again.
        $payment = $appointment->payments()->latest('id')->first();
        if ($payment?->status === PaymentTransaction::PAID) {
            return redirect()->route('appointments.show', $appointment);
        }

        return view('appointments.payment-return', [
            'appointment' => $appointment->load(['doctorProfile.user', 'payments']),
            'checkoutWasCancelled' => false,
        ]);
    }

    public function paymentCancel(Request $request, Appointment $appointment): View
    {
        abort_unless($request->user()->id === $appointment->patient_user_id, 404);
        return view('appointments.payment-return', [
            'appointment' => $appointment->load(['doctorProfile.user', 'payments']),
            'checkoutWasCancelled' => true,
        ]);
    }

    public function retryPayment(Request $request, Appointment $appointment, PaymentService $payments)
    {
        abort_unless($request->user()->id === $appointment->patient_user_id, 404);

        $payment = $appointment->payments()->where('status', PaymentTransaction::PENDING)->latest('id')->first();
        if (! $payment || $appointment->status !== Appointment::PENDING_PAYMENT || ! $appointment->holdExpiresAtUtc()?->isFuture()) {
            return redirect()->route('appointments.payment.return', $appointment)
                ->withErrors(['payment' => 'This payment hold has expired or is already complete. Check the appointment status before trying again.']);
        }

        try {
            $payment = $payments->refreshPendingSafepayCheckout(
                $payment,
                route('appointments.payment.return', $appointment, absolute: true),
                route('appointments.payment.cancel', $appointment, absolute: true),
            );
        } catch (Throwable $exception) {
            report($exception);
            return redirect()->route('appointments.payment.return', $appointment)
                ->withErrors(['payment' => 'Safepay could not restart checkout. No new payment was submitted. Please try again or contact support.']);
        }

        if ($payment->status === PaymentTransaction::PENDING && $payment->checkout_url) {
            return redirect()->away($payment->checkout_url);
        }

        return redirect()->route('appointments.payment.return', $appointment)
            ->with('status', 'The payment status changed while checkout was being refreshed. Review the latest status below.');
    }
}

