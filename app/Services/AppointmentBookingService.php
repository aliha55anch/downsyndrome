<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AppointmentSlotHold;
use App\Models\DoctorConsultationOption;
use App\Models\DoctorAppointmentSlot;
use App\Models\DoctorProfile;
use App\Models\PaymentTransaction;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class AppointmentBookingService
{
    public function __construct(
        private readonly AppointmentAvailabilityService $availability,
        private readonly SafepayPaymentGateway $gateway,
        private readonly PaymentService $payments,
        private readonly AppointmentEmailDispatcher $emails,
        private readonly AppointmentLifecycleNotifier $notifier,
    ) {}

    /** @return array{appointment: Appointment, payment: ?PaymentTransaction, reused: bool, ended?: bool} */
    public function book(User $patient, int $doctorProfileId, int $optionId, CarbonImmutable $localStart, string $idempotencyKey, ?int $doctorSlotId = null): array
    {
        $existing = Appointment::where('patient_user_id', $patient->id)->where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            return $this->resumeExisting($existing);
        }

        $slotHold = AppointmentSlotHold::where('patient_user_id', $patient->id)->where('idempotency_key', $idempotencyKey)->first();
        if ($slotHold) {
            return $this->bookHeldSlot($patient, $doctorProfileId, $optionId, $localStart, $slotHold);
        }

        $profile = DoctorProfile::with('user')->findOrFail($doctorProfileId);
        $option = DoctorConsultationOption::whereKey($optionId)->where('doctor_profile_id', $profile->id)->firstOrFail();
        abort_unless($profile->isPubliclyBookable() && $option->is_active, 404);

        $currency = strtoupper((string) config('app.currency', 'PKR'));
        $amountMinor = $this->toMinor($option->fee);
        if ($amountMinor > 0) {
            try {
                $this->gateway->assertReadyFor($currency);
            } catch (RuntimeException $exception) {
                throw ValidationException::withMessages(['payment' => $exception->getMessage()]);
            }
        }

        $result = DB::transaction(function () use ($patient, $profile, $option, $localStart, $idempotencyKey, $currency, $amountMinor, $doctorSlotId): array {
            $lockedProfile = DoctorProfile::whereKey($profile->id)->lockForUpdate()->firstOrFail();
            $lockedOption = DoctorConsultationOption::whereKey($option->id)
                ->where('doctor_profile_id', $lockedProfile->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedProfile->isPubliclyBookable() && $lockedOption->is_active, 404);
            $datedSlot = $doctorSlotId ? DoctorAppointmentSlot::whereKey($doctorSlotId)->where('doctor_profile_id', $lockedProfile->id)->lockForUpdate()->firstOrFail() : null;
            if ($datedSlot && ($datedSlot->consultation_option_id !== $lockedOption->id || $datedSlot->status !== DoctorAppointmentSlot::OPEN)) {
                throw ValidationException::withMessages(['slot' => 'That doctor-created slot is no longer available. Choose another time.']);
            }

            $existing = Appointment::where('patient_user_id', $patient->id)->where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                return ['appointment' => $existing, 'payment' => $existing->payments()->first(), 'reused' => true];
            }

            $activeHolds = Appointment::where('patient_user_id', $patient->id)
                ->where('status', Appointment::PENDING_PAYMENT)->where('hold_expires_at', '>', now('UTC'))->count();
            $activeHolds += AppointmentSlotHold::where('patient_user_id', $patient->id)
                ->where('status', AppointmentSlotHold::HELD)->where('expires_at', '>', now('UTC'))->count();
            if ($activeHolds >= 2) {
                throw ValidationException::withMessages(['appointment' => 'You already have two appointments waiting for payment. Finish or wait for one to expire before booking another.']);
            }
            if (! $this->availability->slotIsAvailable($lockedProfile, $lockedOption, $localStart, null, null, $doctorSlotId)) {
                throw ValidationException::withMessages(['slot' => 'That appointment time is no longer available. Please choose another slot.']);
            }

            $end = $localStart->addMinutes($lockedOption->duration_minutes);
            $holdExpires = $amountMinor > 0 ? CarbonImmutable::now('UTC')->addMinutes((int) config('services.safepay.hold_minutes', 55)) : null;
            $slotLockKey = $this->slotLockKey($lockedProfile->id, $localStart);
            try {
                $appointment = Appointment::create([
                    'patient_user_id' => $patient->id,
                    'doctor_profile_id' => $lockedProfile->id,
                    'consultation_option_id' => $lockedOption->id,
                    'doctor_appointment_slot_id' => $doctorSlotId,
                    'consultation_type' => $lockedOption->type,
                    'duration_minutes' => $lockedOption->duration_minutes,
                    'starts_at' => $localStart->utc(),
                    'ends_at' => $end->utc(),
                    'timezone' => $lockedProfile->timezone,
                    'amount_minor' => $amountMinor,
                    'currency' => $currency,
                    'status' => $amountMinor > 0 ? Appointment::PENDING_PAYMENT : Appointment::CONFIRMED,
                    'hold_expires_at' => $holdExpires,
                    'slot_lock_key' => $slotLockKey,
                    'idempotency_key' => $idempotencyKey,
                    'meeting_link' => $datedSlot?->meeting_url,
                ]);
                if ($datedSlot) $datedSlot->update(['status' => DoctorAppointmentSlot::BOOKED]);
            } catch (\Illuminate\Database\UniqueConstraintViolationException) {
                throw ValidationException::withMessages(['slot' => 'Another patient just reserved that time. Please choose another slot.']);
            }

            $payment = null;
            if ($amountMinor > 0) {
                $payment = $appointment->payments()->create([
                    'payer_user_id' => $patient->id,
                    'provider' => 'safepay',
                    'status' => PaymentTransaction::PENDING,
                    'amount_minor' => $amountMinor,
                    'currency' => $currency,
                    'idempotency_key' => (string) Str::uuid(),
                    'expires_at' => $holdExpires,
                    'metadata' => ['purpose' => 'doctor_appointment'],
                ]);
            }
            $appointment->events()->create([
                'actor_user_id' => $patient->id,
                'event_type' => $amountMinor > 0 ? 'booking_started' : 'booking_confirmed_no_payment_required',
                'from_status' => null,
                'to_status' => $appointment->status,
                'details' => ['consultation_type' => $lockedOption->type],
                'created_at' => now(),
            ]);

            return ['appointment' => $appointment, 'payment' => $payment, 'reused' => false];
        }, attempts: 3);

        $appointment = $result['appointment']->fresh(['patient', 'doctorProfile.user', 'payments']);
        $payment = $result['payment']?->fresh();
        if ($result['reused']) {
            return ['appointment' => $appointment, 'payment' => $payment, 'reused' => true];
        }

        if ($appointment->status === Appointment::CONFIRMED) {
            app(AppointmentMeetingService::class)->provision($appointment);
            $appointment->refresh();
        }

        if ($payment) {
            $payment = $this->payments->startSafepayCheckout(
                $payment,
                route('appointments.payment.return', $appointment, absolute: true),
                route('appointments.payment.cancel', $appointment, absolute: true),
            );
            $appointment->refresh();
            $this->emails->dispatch(
                $appointment,
                $payment->status === PaymentTransaction::FAILED ? 'payment_failed' : 'booking_initiated',
            );
            $this->notifier->notify($appointment,
                $payment->status === PaymentTransaction::FAILED ? 'payment_failed' : 'booking_initiated',
                $payment->status === PaymentTransaction::FAILED ? 'Payment could not start' : 'Appointment awaiting payment',
                $payment->status === PaymentTransaction::FAILED
                    ? 'Safepay checkout could not be initialized for appointment LS-APT-'.$appointment->id.'. No payment was taken.'
                    : 'Complete Safepay checkout to confirm appointment LS-APT-'.$appointment->id.'.',
            );
        } else {
            $this->emails->dispatch($appointment, 'appointment_confirmed');
            $this->notifier->notify($appointment, 'appointment_confirmed', 'Appointment confirmed', 'Your appointment LS-APT-'.$appointment->id.' is confirmed.');
        }

        return ['appointment' => $appointment, 'payment' => $payment, 'reused' => false];
    }

    public function reserveSlot(User $patient, int $doctorProfileId, int $optionId, CarbonImmutable $localStart, string $idempotencyKey, ?int $doctorSlotId = null): AppointmentSlotHold
    {
        $existing = AppointmentSlotHold::where('patient_user_id', $patient->id)->where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            if ($existing->status === AppointmentSlotHold::HELD && $existing->expires_at->isFuture()) return $existing;
            throw ValidationException::withMessages(['slot' => 'This time selection expired. Choose an available time again.']);
        }

        $profile = DoctorProfile::findOrFail($doctorProfileId);
        abort_unless($profile->isPubliclyBookable(), 404);
        $option = DoctorConsultationOption::whereKey($optionId)->where('doctor_profile_id', $profile->id)->where('is_active', true)->firstOrFail();
        $hold = DB::transaction(function () use ($patient, $profile, $option, $localStart, $idempotencyKey, $doctorSlotId): AppointmentSlotHold {
            $lockedProfile = DoctorProfile::whereKey($profile->id)->lockForUpdate()->firstOrFail();
            $lockedOption = DoctorConsultationOption::whereKey($option->id)->where('doctor_profile_id', $lockedProfile->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedProfile->isPubliclyBookable() && $lockedOption->is_active, 404);
            $datedSlot = $doctorSlotId ? DoctorAppointmentSlot::whereKey($doctorSlotId)->where('doctor_profile_id', $lockedProfile->id)->lockForUpdate()->firstOrFail() : null;
            if ($datedSlot && ($datedSlot->consultation_option_id !== $lockedOption->id || $datedSlot->status !== DoctorAppointmentSlot::OPEN)) {
                throw ValidationException::withMessages(['slot' => 'That doctor-created slot is no longer available. Choose another time.']);
            }

            $now = CarbonImmutable::now('UTC');
            AppointmentSlotHold::where('status', AppointmentSlotHold::HELD)->where('expires_at', '<=', $now)
                ->update(['status' => AppointmentSlotHold::EXPIRED, 'slot_lock_key' => null, 'updated_at' => $now]);
            $expiredAppointments = Appointment::where('doctor_profile_id', $lockedProfile->id)->where('status', Appointment::PENDING_PAYMENT)
                ->where('hold_expires_at', '<=', $now)->whereNotNull('slot_lock_key')
                ->get(['id', 'doctor_appointment_slot_id']);
            foreach ($expiredAppointments as $expiredAppointment) {
                if ($expiredAppointment->doctor_appointment_slot_id) DoctorAppointmentSlot::whereKey($expiredAppointment->doctor_appointment_slot_id)->where('status', DoctorAppointmentSlot::BOOKED)->update(['status' => DoctorAppointmentSlot::OPEN]);
            }
            if ($expiredAppointments->isNotEmpty()) Appointment::whereIn('id', $expiredAppointments->pluck('id'))->update(['status' => Appointment::EXPIRED, 'slot_lock_key' => null, 'updated_at' => $now]);

            $existing = AppointmentSlotHold::where('patient_user_id', $patient->id)->where('idempotency_key', $idempotencyKey)->first();
            if ($existing) {
                if ($existing->status === AppointmentSlotHold::HELD && $existing->expires_at->isFuture()) return $existing;
                throw ValidationException::withMessages(['slot' => 'This time selection expired. Choose an available time again.']);
            }

            $activeCount = Appointment::where('patient_user_id', $patient->id)->where('status', Appointment::PENDING_PAYMENT)
                ->where('hold_expires_at', '>', $now)->count();
            $activeCount += AppointmentSlotHold::where('patient_user_id', $patient->id)->where('status', AppointmentSlotHold::HELD)
                ->where('expires_at', '>', $now)->count();
            if ($activeCount >= 2) {
                throw ValidationException::withMessages(['appointment' => 'You already have two times reserved or waiting for payment. Finish or wait for one to expire before selecting another.']);
            }

            if (! $this->availability->slotIsAvailable($lockedProfile, $lockedOption, $localStart, null, null, $doctorSlotId)) {
                throw ValidationException::withMessages(['slot' => 'That time is no longer available. Choose another slot.']);
            }

            $expiresAt = $now->addMinutes(max(2, (int) config('services.appointments.selection_hold_minutes', 10)));
            try {
                return AppointmentSlotHold::create([
                    'patient_user_id' => $patient->id,
                    'doctor_profile_id' => $lockedProfile->id,
                    'consultation_option_id' => $lockedOption->id,
                    'doctor_appointment_slot_id' => $doctorSlotId,
                    'hold_token' => (string) Str::uuid(),
                    'idempotency_key' => $idempotencyKey,
                    'starts_at' => $localStart->utc(),
                    'ends_at' => $localStart->addMinutes($lockedOption->duration_minutes)->utc(),
                    'timezone' => $lockedProfile->timezone,
                    'slot_lock_key' => $this->slotLockKey($lockedProfile->id, $localStart),
                    'status' => AppointmentSlotHold::HELD,
                    'expires_at' => $expiresAt,
                ]);
            } catch (\Illuminate\Database\UniqueConstraintViolationException) {
                throw ValidationException::withMessages(['slot' => 'Another patient just selected this time. Choose a different available slot.']);
            }
        }, attempts: 3);

        return $hold->fresh(['doctorProfile.user', 'consultationOption']);
    }

    public function releaseSlotHold(User $patient, AppointmentSlotHold $hold): void
    {
        DB::transaction(function () use ($patient, $hold): void {
            $locked = AppointmentSlotHold::whereKey($hold->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->patient_user_id === $patient->id, 404);
            if ($locked->status === AppointmentSlotHold::HELD) {
                $locked->update(['status' => AppointmentSlotHold::RELEASED, 'slot_lock_key' => null]);
            }
        });
    }

    private function bookHeldSlot(User $patient, int $doctorProfileId, int $optionId, CarbonImmutable $localStart, AppointmentSlotHold $hold): array
    {
        $profile = DoctorProfile::with('user')->findOrFail($doctorProfileId);
        $option = DoctorConsultationOption::whereKey($optionId)->where('doctor_profile_id', $profile->id)->where('is_active', true)->firstOrFail();
        abort_unless($hold->doctor_profile_id === $profile->id && $hold->consultation_option_id === $option->id
            && $hold->localStartsAt()->equalTo($localStart), 404);
        $currency = strtoupper((string) config('app.currency', 'PKR'));
        $amountMinor = $this->toMinor($option->fee);

        if ($amountMinor > 0) {
            try {
                $this->gateway->assertReadyFor($currency);
            } catch (RuntimeException $exception) {
                $this->releaseSlotHold($patient, $hold);
                throw ValidationException::withMessages(['payment' => $exception->getMessage()]);
            }
        }

        $result = DB::transaction(function () use ($patient, $profile, $option, $localStart, $hold, $currency, $amountMinor): ?array {
            $lockedProfile = DoctorProfile::whereKey($profile->id)->lockForUpdate()->firstOrFail();
            $lockedHold = AppointmentSlotHold::whereKey($hold->id)->lockForUpdate()->firstOrFail();
            if ($lockedHold->patient_user_id !== $patient->id || $lockedHold->status !== AppointmentSlotHold::HELD
                || $lockedHold->expires_at->lessThanOrEqualTo(now('UTC'))) return null;
            $lockedOption = DoctorConsultationOption::whereKey($option->id)->where('doctor_profile_id', $lockedProfile->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedProfile->isPubliclyBookable() && $lockedOption->is_active, 404);
            $datedSlot = $lockedHold->doctor_appointment_slot_id ? DoctorAppointmentSlot::whereKey($lockedHold->doctor_appointment_slot_id)->where('doctor_profile_id', $lockedProfile->id)->lockForUpdate()->firstOrFail() : null;
            if ($datedSlot && ($datedSlot->consultation_option_id !== $lockedOption->id || $datedSlot->status !== DoctorAppointmentSlot::OPEN)) return null;
            if (! $this->availability->slotIsAvailable($lockedProfile, $lockedOption, $localStart, null, $lockedHold->id, $lockedHold->doctor_appointment_slot_id)) return null;

            $now = CarbonImmutable::now('UTC');
            $paymentExpires = $amountMinor > 0 ? $now->addMinutes((int) config('services.safepay.hold_minutes', 55)) : null;
            try {
                $appointment = Appointment::create([
                    'patient_user_id' => $patient->id, 'doctor_profile_id' => $lockedProfile->id,
                    'consultation_option_id' => $lockedOption->id, 'consultation_type' => $lockedOption->type,
                    'doctor_appointment_slot_id' => $lockedHold->doctor_appointment_slot_id,
                    'duration_minutes' => $lockedOption->duration_minutes, 'starts_at' => $localStart->utc(),
                    'ends_at' => $localStart->addMinutes($lockedOption->duration_minutes)->utc(), 'timezone' => $lockedProfile->timezone,
                    'amount_minor' => $amountMinor, 'currency' => $currency,
                    'status' => $amountMinor > 0 ? Appointment::PENDING_PAYMENT : Appointment::CONFIRMED,
                    'hold_expires_at' => $paymentExpires, 'slot_lock_key' => $this->slotLockKey($lockedProfile->id, $localStart),
                    'idempotency_key' => $lockedHold->idempotency_key,
                    'meeting_link' => $datedSlot?->meeting_url,
                ]);
                if ($datedSlot) $datedSlot->update(['status' => DoctorAppointmentSlot::BOOKED]);
            } catch (\Illuminate\Database\UniqueConstraintViolationException) {
                throw ValidationException::withMessages(['slot' => 'This time was just reserved by another patient. Choose another slot.']);
            }

            $lockedHold->update(['status' => AppointmentSlotHold::CONVERTED, 'slot_lock_key' => null]);
            $payment = null;
            if ($amountMinor > 0) {
                $payment = $appointment->payments()->create([
                    'payer_user_id' => $patient->id, 'provider' => 'safepay', 'status' => PaymentTransaction::PENDING,
                    'amount_minor' => $amountMinor, 'currency' => $currency, 'idempotency_key' => (string) Str::uuid(),
                    'expires_at' => $paymentExpires, 'metadata' => ['purpose' => 'doctor_appointment'],
                ]);
            }
            $appointment->events()->create([
                'actor_user_id' => $patient->id,
                'event_type' => $amountMinor > 0 ? 'booking_started' : 'booking_confirmed_no_payment_required',
                'from_status' => null, 'to_status' => $appointment->status,
                'details' => ['consultation_type' => $lockedOption->type, 'slot_hold_token' => $lockedHold->hold_token],
                'created_at' => now(),
            ]);

            return ['appointment' => $appointment, 'payment' => $payment];
        }, attempts: 3);

        if (! $result) {
            return ['appointment' => null, 'payment' => null, 'reused' => true, 'ended' => true];
        }
        $appointment = $result['appointment']->fresh(['patient', 'doctorProfile.user', 'payments']);
        $payment = $result['payment']?->fresh();
        if ($appointment->status === Appointment::CONFIRMED) {
            app(AppointmentMeetingService::class)->provision($appointment);
            $appointment->refresh();
            $this->emails->dispatch($appointment, 'appointment_confirmed');
            $this->notifier->notify($appointment, 'appointment_confirmed', 'Appointment confirmed', 'Your appointment LS-APT-'.$appointment->id.' is confirmed.');
        } else {
            $payment = $this->payments->startSafepayCheckout(
                $payment,
                route('appointments.payment.return', $appointment, absolute: true),
                route('appointments.payment.cancel', $appointment, absolute: true),
            );
            $appointment->refresh();
            $this->emails->dispatch($appointment, $payment->status === PaymentTransaction::FAILED ? 'payment_failed' : 'booking_initiated');
            $this->notifier->notify($appointment,
                $payment->status === PaymentTransaction::FAILED ? 'payment_failed' : 'booking_initiated',
                $payment->status === PaymentTransaction::FAILED ? 'Payment could not start' : 'Appointment awaiting payment',
                $payment->status === PaymentTransaction::FAILED
                    ? 'Safepay checkout could not be initialized for appointment LS-APT-'.$appointment->id.'. No payment was taken.'
                    : 'Complete Safepay checkout to confirm appointment LS-APT-'.$appointment->id.'.',
            );
        }

        return ['appointment' => $appointment, 'payment' => $payment, 'reused' => false, 'ended' => false];
    }

    public function resumeExisting(Appointment $appointment): array
    {
        $payment = $appointment->payments()->first();
        if (! in_array($appointment->status, [Appointment::PENDING_PAYMENT, Appointment::CONFIRMED, Appointment::RESCHEDULED], true)
            || ($appointment->status === Appointment::PENDING_PAYMENT && (! $payment?->checkout_url || $payment->status !== PaymentTransaction::PENDING))) {
            return ['appointment' => $appointment->load(['patient', 'doctorProfile.user']), 'payment' => $payment, 'reused' => true, 'ended' => true];
        }
        return ['appointment' => $appointment->load(['patient', 'doctorProfile.user']), 'payment' => $payment, 'reused' => true, 'ended' => false];
    }

    public function slotLockKey(int $doctorProfileId, CarbonImmutable $localStart): string
    {
        return 'doctor:'.$doctorProfileId.':'.$localStart->utc()->format('YmdHis');
    }

    public function toMinor(mixed $amount): int
    {
        if ($amount === null || (float) $amount <= 0) {
            return 0;
        }
        [$whole, $fraction] = array_pad(explode('.', number_format((float) $amount, 2, '.', '')), 2, '00');
        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }
}
