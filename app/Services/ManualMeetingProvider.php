<?php

namespace App\Services;

use App\Contracts\MeetingProvider;
use App\Models\Appointment;

/** Manual mode never fabricates a meeting URL; the doctor supplies one in the appointment dashboard. */
class ManualMeetingProvider implements MeetingProvider
{
    public function provision(Appointment $appointment): ?string
    {
        return null;
    }

    public function revoke(Appointment $appointment): void
    {
        // Manually created provider meetings must be revoked at the provider by their owner.
    }
}
