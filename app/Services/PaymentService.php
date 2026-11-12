<?php

namespace App\Services;

use App\Models\PaymentTransaction;
use App\Models\Appointment;
use App\Models\DoctorAppointmentSlot;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/** Central payment orchestration shared by appointment and future billable modules. */
class PaymentService
{
    public function __construct(private readonly SafepayPaymentGateway $safepay) {}

    public function refreshPendingSafepayCheckout(PaymentTransaction $payment, string $returnUrl, string $cancelUrl): PaymentTransaction
    {
        $payment->loadMissing('payable');
        $appointment = $payment->payable;
        if (! $appointment instanceof Appointment || $payment->status !== PaymentTransaction::PENDING
            || $appointment->status !== Appointment::PENDING_PAYMENT
            || ! $appointment->holdExpiresAtUtc()?->isFuture()
            || ! filled($payment->provider_reference)) {
            throw new \RuntimeException('This appointment no longer has an active Safepay checkout to retry.');
        }

        $checkoutUrl = $this->safepay->refreshHostedCheckout(
            $payment->provider_reference,
            $payment->currency,
            $returnUrl,
            $cancelUrl,
        );

        return DB::transaction(function () use ($payment, $checkoutUrl): PaymentTransaction {
            $locked = PaymentTransaction::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $payable = $locked->payable;
            if ($locked->status !== PaymentTransaction::PENDING || ! $payable instanceof Appointment
                || $payable->status !== Appointment::PENDING_PAYMENT
                || ! $payable->holdExpiresAtUtc()?->isFuture()) {
                return $locked;
            }

            $locked->update(['checkout_url' => $checkoutUrl]);
            return $locked->fresh();
        });
    }

    public function startSafepayCheckout(PaymentTransaction $payment, string $returnUrl, string $cancelUrl): PaymentTransaction
    {
        $payment->loadMissing('payable');
        $reference = 'LS-'.$payment->id.'-'.Str::upper(Str::random(10));

        try {
            $checkout = $this->safepay->createHostedCheckout(
                $payment->amount_minor,
                $payment->currency,
                $reference,
                $returnUrl,
                $cancelUrl,
                function (string $tracker) use ($payment): void {
                    DB::transaction(function () use ($payment, $tracker): void {
                        $locked = PaymentTransaction::whereKey($payment->id)->lockForUpdate()->firstOrFail();
                        if ($locked->status === PaymentTransaction::PENDING && ! $locked->provider_reference) {
                            $locked->update(['provider_reference' => $tracker]);
                        }
                    });
                },
            );
        } catch (Throwable $exception) {
            DB::transaction(function () use ($payment): void {
                $lockedPayment = PaymentTransaction::whereKey($payment->id)->lockForUpdate()->firstOrFail();
                // A tracker may exist even though the checkout URL could not be produced.
                // The payment row is locked first, matching webhook lock order, so a concurrently
                // verified payment can never be downgraded to failed.
                if ($lockedPayment->status !== PaymentTransaction::PENDING || filled($lockedPayment->checkout_url)) {
                    return;
                }

                $lockedPayment->update([
                    'status' => PaymentTransaction::FAILED,
                    'failed_at' => CarbonImmutable::now('UTC'),
                    'failure_code' => 'checkout_initialization_failed',
                ]);

                $payable = $lockedPayment->payable;
                if (! $payable instanceof Appointment) return;

                $lockedAppointment = Appointment::whereKey($payable->id)->lockForUpdate()->first();
                if (! $lockedAppointment || $lockedAppointment->status !== Appointment::PENDING_PAYMENT) return;

                $lockedAppointment->update([
                    'status' => Appointment::EXPIRED,
                    'hold_expires_at' => CarbonImmutable::now('UTC'),
                    'slot_lock_key' => null,
                ]);
                if ($lockedAppointment->doctor_appointment_slot_id) DoctorAppointmentSlot::whereKey($lockedAppointment->doctor_appointment_slot_id)->where('status', DoctorAppointmentSlot::BOOKED)->update(['status' => DoctorAppointmentSlot::OPEN]);
                $lockedAppointment->events()->create([
                    'event_type' => 'payment_initialization_failed',
                    'from_status' => Appointment::PENDING_PAYMENT,
                    'to_status' => Appointment::EXPIRED,
                    'details' => ['reason' => 'The hosted checkout could not be initialized.'],
                    'created_at' => now(),
                ]);
            });
            report($exception);
            return $payment->fresh();
        }

        return DB::transaction(function () use ($payment, $checkout): PaymentTransaction {
            $locked = PaymentTransaction::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== PaymentTransaction::PENDING) {
                return $locked;
            }
            $locked->update([
                'provider_reference' => $checkout['reference'],
                'checkout_url' => $checkout['checkout_url'],
                'expires_at' => CarbonImmutable::now('UTC')->addMinutes((int) config('services.safepay.hold_minutes', 55)),
            ]);
            if ($locked->payable instanceof \App\Models\Appointment && $locked->payable->status === \App\Models\Appointment::PENDING_PAYMENT) {
                $locked->payable->update(['hold_expires_at' => $locked->expires_at]);
            }
            return $locked->fresh();
        });
    }
}
