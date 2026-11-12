<?php

namespace App\Services;

use App\Models\AppointmentEmail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GoogleAppsScriptMailer
{
    public function sendAppointmentMessage(AppointmentEmail $message): bool
    {
        $url = (string) config('services.google_apps_script.web_app_url');
        $secret = (string) config('services.google_apps_script.shared_secret');
        if ($url === '' || $secret === '') {
            Log::notice('Appointment email remains in the outbox because Google Apps Script is not configured.', ['outbox_id' => $message->id]);
            return false;
        }
        $message->loadMissing(['appointment.patient', 'appointment.doctorProfile.user', 'recipient']);
        $appointment = $message->appointment;
        $snapshot = $message->snapshot ?? [];
        $logoUrl = config('mail.logo_url');
        if ($logoUrl && ! str_starts_with(strtolower((string) $logoUrl), 'https://')) $logoUrl = null;
        $html = view('emails.appointment', [
            'message' => $message, 'appointment' => $appointment, 'snapshot' => $snapshot,
            'brandName' => config('app.name', 'Little Steps'), 'logoUrl' => $logoUrl,
        ])->render();
        $payload = [
            'event_key' => $message->event_key,
            'to' => $message->recipient->email,
            'sender_name' => config('mail.from.name', config('app.name', 'Little Steps')),
            'reply_to' => config('mail.reply_to.address'),
            'subject' => $this->subject($message->email_type),
            'html' => $html,
            'text' => trim(preg_replace('/\\s+/', ' ', strip_tags($html)) ?? ''),
        ];
        $payloadJson = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $response = Http::acceptJson()->asJson()->connectTimeout(8)->timeout(30)->post($url, [
            'payload' => $payloadJson, 'signature' => hash_hmac('sha256', $payloadJson, $secret),
        ]);
        if (! $response->successful() || $response->json('ok') !== true) {
            throw new RuntimeException('Google Apps Script did not accept the appointment email (HTTP '.$response->status().').');
        }
        $message->update(['status' => 'sent', 'sent_at' => now(), 'last_error' => null]);
        return true;
    }

    private function subject(string $type): string
    {
        return match (true) {
            $type === 'booking_initiated' => 'Appointment checkout started',
            $type === 'appointment_confirmed' => 'Your appointment is confirmed',
            $type === 'payment_failed' => 'Appointment payment needs attention',
            $type === 'payment_succeeded' => 'Your appointment payment is confirmed',
            $type === 'payment_requires_review' => 'Your appointment payment needs review',
            str_starts_with($type, 'payment_refunded') => 'Appointment payment refund confirmed',
            $type === 'appointment_cancelled' => 'Appointment update: cancelled',
            $type === 'cancellation_requested' => 'Appointment cancellation requested',
            $type === 'cancellation_approved' => 'Appointment cancellation approved',
            $type === 'cancellation_rejected' => 'Appointment cancellation request update',
            $type === 'refund_processing' => 'Appointment refund processing started',
            $type === 'prescription_published' => 'A digital prescription is available',
            $type === 'appointment_rescheduled' => 'Appointment update: new time',
            in_array($type, ['reminder_24h', 'reminder_1h'], true) => 'Appointment reminder',
            $type === 'payment_review' => 'Appointment payment needs review',
            $type === 'meeting_link_added' => 'Your consultation link is ready',
            default => 'Little Steps appointment update',
        };
    }
}
