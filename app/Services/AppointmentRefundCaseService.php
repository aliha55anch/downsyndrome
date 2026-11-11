<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AppointmentRefundCase;
use App\Models\PaymentRefund;
use App\Models\PaymentTransaction;
use App\Models\User;

class AppointmentRefundCaseService
{
    public function requestRemainingAmount(Appointment $appointment, ?User $actor, string $reason): ?AppointmentRefundCase
    {
        $payment = $appointment->payments()->whereIn('status', [PaymentTransaction::PAID, 'partially_refunded'])->first();
        if (! $payment) return null;
        $refunded = (int) PaymentRefund::where('payment_transaction_id', $payment->id)->where('status', 'succeeded')->sum('amount_minor');
        $remaining = max(0, $payment->amount_minor - $refunded);
        if ($remaining === 0) return null;

        return AppointmentRefundCase::firstOrCreate(['idempotency_key' => 'appointment-'.$appointment->id.'-refund'], [
            'appointment_id' => $appointment->id,
            'payment_transaction_id' => $payment->id,
            'requested_by' => $actor?->id,
            'reason' => $reason,
            'amount_minor' => $remaining,
            'amount_previously_refunded_minor' => $refunded,
            'currency' => $payment->currency,
            'status' => AppointmentRefundCase::PENDING_REVIEW,
        ]);
    }
}
