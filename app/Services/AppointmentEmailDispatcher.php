<?php

namespace App\Services;

use App\Jobs\SendAppointmentEmail;
use App\Models\Appointment;
use App\Models\AppointmentEmail;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class AppointmentEmailDispatcher
{
    /** Creates recipient-specific, idempotent outbox rows for one appointment event. */
    public function dispatch(Appointment $appointment, string $type, ?array $recipients = null): void
    {
        $appointment->loadMissing(['patient', 'doctorProfile.user', 'payments']);
        $users = collect($recipients ?? [$appointment->patient, $appointment->doctorProfile->user])
            ->filter(fn ($user) => $user instanceof User)->unique('id');
        if ($users->isEmpty()) {
            return;
        }

        $start = $appointment->localStartsAt();
        $snapshot = [
            'doctor_name' => $appointment->doctorProfile->user->name,
            'professional_title' => $appointment->doctorProfile->professional_title,
            'patient_name' => $appointment->patient?->name ?? 'Patient',
            'consultation_type' => $appointment->consultation_type,
            'starts_at' => $start->format('l, F j, Y g:i A'),
            'ends_at' => $appointment->localEndsAt()->format('g:i A'),
            'timezone' => $appointment->timezone,
            'amount' => number_format($appointment->amount_minor / 100, 2),
            'currency' => $appointment->currency,
            'appointment_status' => $appointment->status,
            'payment_status' => $appointment->payments->first()?->status ?? 'not_required',
            'refunded_amount' => number_format(($appointment->payments->first()?->refunds?->where('status', 'succeeded')->sum('amount_minor') ?? 0) / 100, 2),
            'hold_expires_at' => $appointment->hold_expires_at?->setTimezone($appointment->timezone)->format('g:i A'),
            'meeting_link' => in_array($appointment->status, [Appointment::CONFIRMED, Appointment::RESCHEDULED], true) ? $appointment->meeting_link : null,
            'appointment_reference' => 'LS-APT-'.$appointment->id,
            'appointment_id' => $appointment->id,
            'payment_reference' => $appointment->payments->first()?->provider_reference,
            'doctor_specialty' => $appointment->doctorProfile->specialty,
            'cancellation_reason' => $appointment->cancellation_reason,
        ];

        foreach ($users as $user) {
            $message = AppointmentEmail::firstOrCreate([
                'event_key' => 'appointment:'.$appointment->id.':'.$type.':user:'.$user->id,
            ], [
                'appointment_id' => $appointment->id,
                'recipient_user_id' => $user->id,
                'email_type' => $type,
                'status' => 'pending',
                'snapshot' => $snapshot + [
                    'appointment_url' => $user->hasRole(User::ROLE_APPROVED_DOCTOR)
                        ? route('doctor.appointments.show', $appointment)
                        : route('appointments.show', $appointment),
                    'patient_email' => $user->hasRole(User::ROLE_APPROVED_DOCTOR) ? $appointment->patient?->email : null,
                ],
            ]);

            if (filled(config('services.google_apps_script.web_app_url')) && filled(config('services.google_apps_script.shared_secret'))) {
                $claimed = AppointmentEmail::whereKey($message->id)->whereIn('status', ['pending', 'failed'])->update(['status' => 'queued', 'last_error' => null]);
                if ($claimed) SendAppointmentEmail::dispatch($message->id)->afterCommit();
            } elseif ($message->wasRecentlyCreated) {
                Log::notice('Appointment email saved to the outbox; Google Apps Script setup is still required.', ['outbox_id' => $message->id]);
            }
        }
    }
}
