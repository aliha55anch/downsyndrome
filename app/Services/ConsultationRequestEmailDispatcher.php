<?php

namespace App\Services;

use App\Jobs\SendConsultationRequestEmail;
use App\Models\ConsultationRequest;
use App\Models\ConsultationRequestEmail;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ConsultationRequestEmailDispatcher
{
    public function dispatch(ConsultationRequest $request, string $type): void
    {
        $request->loadMissing(['patient', 'doctorProfile.user', 'doctorProfile.specialties', 'payments']);
        $doctor = $request->doctorProfile;
        $users = collect([$request->patient, $doctor?->user])
            ->filter(fn ($user) => $user instanceof User)
            ->unique('id');
        if ($users->isEmpty()) return;

        $payment = $request->payments->first();
        $reference = 'LS-CNS-'.str_pad((string) $request->id, 8, '0', STR_PAD_LEFT);
        $snapshot = [
            'reference' => $reference,
            'request_id' => $request->id,
            'patient_name' => $request->patient?->name ?? 'Patient',
            'doctor_name' => $doctor?->user?->name ?? 'Doctor',
            'professional_title' => $doctor?->professional_title ?? '',
            'specialty' => $doctor?->specialties?->pluck('name')->join(', ') ?: ($doctor?->specialty ?? ''),
            'consultation_type' => $request->consultation_type,
            'request_date' => $request->request_date?->format('l, F j, Y'),
            'fee' => number_format((float) $request->consultation_fee, 2),
            'currency' => strtoupper((string) config('app.currency', 'PKR')),
            'payment_method' => $request->payment_method,
            'payment_status' => $request->payment_status,
            'paid_at' => $payment?->status === PaymentTransaction::PAID ? $payment->paid_at?->format('F j, Y g:i A T') : null,
            'patient_url' => route('consultations.mine'),
            'doctor_url' => $doctor ? route('doctors.show', $doctor) : url('/'),
        ];

        foreach ($users as $user) {
            $message = ConsultationRequestEmail::firstOrCreate([
                'event_key' => 'consultation-request:'.$request->id.':'.$type.':user:'.$user->id,
            ], [
                'consultation_request_id' => $request->id,
                'recipient_user_id' => $user->id,
                'email_type' => $type,
                'status' => 'pending',
                'snapshot' => $snapshot + ['recipient_role' => $user->id === $request->patient_user_id ? 'patient' : 'doctor'],
            ]);

            if (filled(config('services.google_apps_script.web_app_url')) && filled(config('services.google_apps_script.shared_secret'))) {
                $claimed = ConsultationRequestEmail::whereKey($message->id)->whereIn('status', ['pending', 'failed'])->update(['status' => 'queued', 'last_error' => null]);
                if ($claimed) SendConsultationRequestEmail::dispatch($message->id)->afterCommit();
            } elseif ($message->wasRecentlyCreated) {
                Log::notice('Consultation request email saved to the outbox; Google Apps Script setup is still required.', ['outbox_id' => $message->id]);
            }
        }
    }
}
