<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\PaymentTransaction;
use App\Models\PaymentWebhookEvent;
use App\Models\PaymentRefund;
use App\Models\AppointmentRefundCase;
use App\Models\DoctorAppointmentSlot;
use App\Services\AppointmentEmailDispatcher;
use App\Services\AppointmentLifecycleNotifier;
use App\Services\SafepayPaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class SafepayWebhookController extends Controller
{
    public function __invoke(Request $request, SafepayPaymentGateway $gateway, AppointmentEmailDispatcher $emails, AppointmentLifecycleNotifier $notifier): Response
    {
        $raw = $request->getContent();
        if (! $gateway->verifiesWebhook($raw, $request->header('X-SFPY-SIGNATURE'))) {
            return response('Invalid signature', 400);
        }
        $payload = json_decode($raw, true);
        if (! is_array($payload)) return response('Invalid payload', 400);

        $eventId = (string) ($payload['token'] ?? $payload['id'] ?? '');
        $eventType = (string) ($payload['type'] ?? $payload['event'] ?? 'unknown');
        $trackerValue = data_get($payload, 'data.tracker');
        $tracker = is_array($trackerValue) ? (string) ($trackerValue['token'] ?? '') : (string) ($trackerValue ?? '');
        if ($eventId === '' || strlen($eventId) > 120) return response('Missing event id', 400);

        try {
            $event = PaymentWebhookEvent::firstOrCreate(['provider' => 'safepay', 'event_id' => $eventId], [
                'event_type' => $eventType, 'provider_reference' => $tracker ?: null,
                'body_sha256' => hash('sha256', $raw), 'processing_status' => 'received',
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            return response('Already received', 200);
        }
        if (! $event->wasRecentlyCreated) return response('Already received', 200);

        $notice = null;
        $changedAppointment = null;
        $result = DB::transaction(function () use ($event, $eventType, $tracker, $payload, &$notice, &$changedAppointment): string {
            if ($tracker === '') {
                $event->update(['processing_status' => 'ignored', 'rejection_reason' => 'missing_tracker', 'processed_at' => now()]);
                return 'ignored';
            }
            $payment = PaymentTransaction::where('provider', 'safepay')->where('provider_reference', $tracker)->lockForUpdate()->first();
            if (! $payment) {
                $event->update(['processing_status' => 'unmatched', 'rejection_reason' => 'unknown_tracker', 'processed_at' => now()]);
                return 'unmatched';
            }
            $amount = data_get($payload, 'data.amount');
            $currency = strtoupper((string) data_get($payload, 'data.currency', ''));
            $success = $eventType === 'payment.succeeded' && (bool) data_get($payload, 'data.success', true);
            $hasAmountOrCurrency = $eventType !== 'payment.refunded' && ($amount !== null || $currency !== '');
            if (($success || $hasAmountOrCurrency) && ((int) $amount !== $payment->amount_minor || $currency !== strtoupper($payment->currency))) {
                $event->update(['processing_status' => 'rejected', 'rejection_reason' => 'amount_or_currency_mismatch', 'processed_at' => now()]);
                Log::warning('Safepay webhook amount/currency mismatch.', ['event_id' => $event->event_id, 'payment_id' => $payment->id]);
                return 'rejected';
            }
            $appointment = $payment->payable;
            if (! $appointment instanceof Appointment) {
                $event->update(['processing_status' => 'unsupported', 'rejection_reason' => 'unsupported_payable', 'processed_at' => now()]);
                return 'unsupported';
            }
            $appointment = Appointment::whereKey($appointment->id)->lockForUpdate()->firstOrFail();
            $changedAppointment = $appointment;
            if ($eventType === 'payment.refunded') {
                $refundAmount = (int) data_get($payload, 'data.refund_amount', 0);
                $refundCurrency = strtoupper((string) data_get($payload, 'data.currency', ''));
                $alreadyRefunded = (int) PaymentRefund::where('payment_transaction_id', $payment->id)->where('status', 'succeeded')->sum('amount_minor');
                if ($payment->paid_at === null || $refundAmount < 1 || $refundCurrency !== strtoupper($payment->currency) || $alreadyRefunded + $refundAmount > $payment->amount_minor) {
                    $event->update(['processing_status' => 'rejected', 'rejection_reason' => 'invalid_refund_amount_or_currency', 'processed_at' => now()]);
                    return 'rejected';
                }
                PaymentRefund::create([
                    'payment_transaction_id' => $payment->id, 'provider_event_id' => $event->event_id,
                    'provider_reference' => $tracker, 'amount_minor' => $refundAmount, 'currency' => $refundCurrency,
                    'status' => 'succeeded', 'refunded_at' => data_get($payload, 'data.refunded_at') ?: now(),
                ]);
                $totalRefunded = $alreadyRefunded + $refundAmount;
                $payment->update(['status' => $totalRefunded >= $payment->amount_minor ? 'refunded' : 'partially_refunded']);
                $refundCase = AppointmentRefundCase::where('payment_transaction_id', $payment->id)
                    ->where('status', AppointmentRefundCase::PROCESSING)->lockForUpdate()->first();
                if ($refundCase && ($totalRefunded - $refundCase->amount_previously_refunded_minor) >= $refundCase->amount_minor) {
                    $refundCase->update(['status' => \App\Models\AppointmentRefundCase::SUCCEEDED, 'completed_at' => now()]);
                    $appointment->events()->create(['event_type' => 'refund_case_verified_succeeded', 'details' => ['refund_case_id' => $refundCase->id, 'verified_refund_minor' => $refundAmount], 'created_at' => now()]);
                }
                $appointment->events()->create(['event_type' => 'verified_payment_refund_recorded', 'details' => ['payment_id' => $payment->id, 'refund_amount_minor' => $refundAmount, 'total_refunded_minor' => $totalRefunded], 'created_at' => now()]);
                if ($totalRefunded >= $payment->amount_minor && in_array($appointment->status, Appointment::BLOCKING_STATUSES, true)) {
                    $fromStatus = $appointment->status;
                    $appointment->update(['status' => Appointment::CANCELLED, 'cancelled_by' => null, 'cancellation_reason' => 'Safepay payment fully refunded.', 'slot_lock_key' => null, 'hold_expires_at' => null, 'meeting_link' => null]);
                    $appointment->events()->create(['event_type' => 'fully_refunded_appointment_released', 'from_status' => $fromStatus, 'to_status' => Appointment::CANCELLED, 'created_at' => now()]);
                    if ($appointment->doctor_appointment_slot_id) DoctorAppointmentSlot::whereKey($appointment->doctor_appointment_slot_id)->where('status', DoctorAppointmentSlot::BOOKED)->update(['status' => DoctorAppointmentSlot::OPEN]);
                }
                $notice = 'payment_refunded_'.substr(hash('sha256', $event->event_id), 0, 16);
            } elseif ($success && ! in_array($payment->status, [PaymentTransaction::PAID, 'partially_refunded', 'refunded'], true)) {
                $payment->update([
                    'status' => PaymentTransaction::PAID, 'paid_at' => now(), 'failed_at' => null, 'failure_code' => null,
                    'gateway_fee_minor' => data_get($payload, 'data.fee') !== null ? (int) data_get($payload, 'data.fee') : $payment->gateway_fee_minor,
                    'gateway_net_minor' => data_get($payload, 'data.net') !== null ? (int) data_get($payload, 'data.net') : $payment->gateway_net_minor,
                ]);
                if ($appointment->status === Appointment::PENDING_PAYMENT && $appointment->holdExpiresAtUtc()?->greaterThan(\Carbon\CarbonImmutable::now('UTC'))) {
                    // The original slot lock remains active; the hold now represents a confirmed booking.
                    $appointment->update(['status' => Appointment::CONFIRMED, 'hold_expires_at' => null]);
                    $appointment->events()->create(['event_type' => 'payment_verified_and_appointment_confirmed', 'from_status' => Appointment::PENDING_PAYMENT, 'to_status' => Appointment::CONFIRMED, 'details' => ['payment_id' => $payment->id], 'created_at' => now()]);
                    $notice = 'payment_succeeded';
                } else {
                    // Payment is genuine but the slot expired or booking ended; admin must reconcile it.
                    $appointment->update(['status' => Appointment::PENDING_CONFIRMATION, 'slot_lock_key' => null, 'hold_expires_at' => null]);
                    $appointment->events()->create(['event_type' => 'late_payment_requires_admin_review', 'from_status' => $appointment->status, 'to_status' => Appointment::PENDING_CONFIRMATION, 'details' => ['payment_id' => $payment->id], 'created_at' => now()]);
                    $notice = 'payment_requires_review';
                }
            } elseif (in_array($eventType, ['payment.failed', 'payment.cancelled'], true) && $payment->status === PaymentTransaction::PENDING) {
                $payment->update(['status' => $eventType === 'payment.cancelled' ? PaymentTransaction::CANCELLED : PaymentTransaction::FAILED, 'failed_at' => now(), 'failure_code' => Str::limit($eventType, 120, '')]);
                if ($appointment->status === Appointment::PENDING_PAYMENT) {
                    $fromStatus = $appointment->status;
                    $appointment->update([
                        'status' => Appointment::EXPIRED,
                        'hold_expires_at' => now('UTC'),
                        'slot_lock_key' => null,
                    ]);
                    if ($appointment->doctor_appointment_slot_id) DoctorAppointmentSlot::whereKey($appointment->doctor_appointment_slot_id)->where('status', DoctorAppointmentSlot::BOOKED)->update(['status' => DoctorAppointmentSlot::OPEN]);
                    $appointment->events()->create([
                        'event_type' => 'verified_payment_failed_slot_released',
                        'from_status' => $fromStatus,
                        'to_status' => Appointment::EXPIRED,
                        'details' => ['payment_id' => $payment->id, 'provider_event' => $eventType],
                        'created_at' => now(),
                    ]);
                }
                $notice = 'payment_failed';
            }
            $event->update(['processing_status' => 'processed', 'processed_at' => now()]);
            return 'processed';
        });

        if ($notice && $changedAppointment) {
            if ($notice === 'payment_succeeded' && app(\App\Services\AppointmentMeetingService::class)->provision($changedAppointment->fresh())) {
                $changedAppointment->refresh();
                $emails->dispatch($changedAppointment, 'meeting_link_added');
            }
            $emails->dispatch($changedAppointment->fresh(), $notice);
            $messages = [
                'payment_succeeded' => ['Appointment confirmed', 'Your Safepay payment was verified and appointment LS-APT-'.$changedAppointment->id.' is confirmed.'],
                'payment_failed' => ['Payment not completed', 'Safepay did not complete payment for appointment LS-APT-'.$changedAppointment->id.'. Choose another available time to try again.'],
                'payment_requires_review' => ['Payment needs review', 'Safepay confirmed payment for LS-APT-'.$changedAppointment->id.'. The booking needs administrator review.'],
            ];
            if (isset($messages[$notice])) {
                [$title, $message] = $messages[$notice];
                $notifier->notify($changedAppointment, $notice, $title, $message, $notice === 'payment_requires_review');
            }
            if (str_starts_with($notice, 'payment_refunded_')) $notifier->notify($changedAppointment, 'payment_refunded', 'Refund verified', 'Safepay confirmed a refund for appointment LS-APT-'.$changedAppointment->id.'.', true);
        }
        return response($result, 200);
    }
}

