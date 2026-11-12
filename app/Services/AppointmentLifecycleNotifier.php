<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\User;
use App\Notifications\AppointmentLifecycleNotification;

class AppointmentLifecycleNotifier
{
    /** Create a role-aware in-app event for the patient, treating doctor, and admins when requested. */
    public function notify(Appointment $appointment, string $event, string $title, string $message, bool $includeAdmins = false): void
    {
        $appointment->loadMissing(['patient', 'doctorProfile.user']);
        $recipients = collect([$appointment->patient, $appointment->doctorProfile->user]);
        if ($includeAdmins) $recipients = $recipients->merge(User::where('role', User::ROLE_ADMIN)->get());

        foreach ($recipients->filter()->unique('id') as $recipient) {
            $url = $recipient->hasRole(User::ROLE_APPROVED_DOCTOR)
                ? route('doctor.appointments.show', $appointment)
                : ($recipient->hasRole(User::ROLE_ADMIN)
                    ? route('admin.appointments.show', $appointment)
                    : route('appointments.show', $appointment));
            $recipient->notify(new AppointmentLifecycleNotification($title, $message, $event, $appointment->id, $url));
        }
    }
}
