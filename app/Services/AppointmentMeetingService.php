<?php

namespace App\Services;

use App\Contracts\MeetingProvider;
use App\Models\Appointment;
use App\Models\PaymentTransaction;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AppointmentMeetingService
{
    public function __construct(private readonly MeetingProvider $provider) {}

    /** Provision only after the online appointment is confirmed and any required payment is verified. */
    public function provision(Appointment $appointment): bool
    {
        if ($appointment->consultation_type !== 'online'
            || ! in_array($appointment->status, [Appointment::CONFIRMED, Appointment::RESCHEDULED], true)
            || $appointment->meeting_link) {
            return false;
        }
        $payment = $appointment->payments()->latest('id')->first();
        if ($payment && $payment->status !== PaymentTransaction::PAID) return false;

        $url = $this->provider->provision($appointment);
        if ($url === null) return false;
        if (filter_var($url, FILTER_VALIDATE_URL) === false || ! str_starts_with(strtolower($url), 'https://')) {
            throw new RuntimeException('The configured meeting provider returned an invalid secure URL.');
        }

        return DB::transaction(function () use ($appointment, $url): bool {
            $locked = Appointment::whereKey($appointment->id)->lockForUpdate()->firstOrFail();
            if (! in_array($locked->status, [Appointment::CONFIRMED, Appointment::RESCHEDULED], true) || $locked->meeting_link) return false;
            $payment = $locked->payments()->latest('id')->first();
            if ($payment && $payment->status !== PaymentTransaction::PAID) return false;
            $locked->update(['meeting_link' => $url]);
            $locked->events()->create(['event_type' => 'meeting_provider_link_created', 'details' => ['provider' => config('services.meeting.provider', 'manual')], 'created_at' => now()]);
            return true;
        });
    }
}
