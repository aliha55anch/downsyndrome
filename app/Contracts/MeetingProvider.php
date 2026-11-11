<?php

namespace App\Contracts;

use App\Models\Appointment;

interface MeetingProvider
{
    /** Create a provider meeting for a confirmed online appointment, or return null for manual provisioning. */
    public function provision(Appointment $appointment): ?string;

    /** Revoke provider access when an appointment is cancelled or moved. */
    public function revoke(Appointment $appointment): void;
}
