<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\AppointmentSlotHold;
use App\Models\DoctorAppointmentSlot;
use App\Models\DoctorConsultationOption;
use App\Models\DoctorProfile;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/** Generates bookable slots from Module 7 schedules plus current appointment holds. */
class AppointmentAvailabilityService
{
    public function __construct(private readonly DoctorAvailabilityService $schedule) {}

    /** @return Collection<int, array{value: string, label: string, starts_at: CarbonImmutable, ends_at: CarbonImmutable}> */
    public function slotsForDate(
        DoctorProfile $profile,
        DoctorConsultationOption $option,
        CarbonImmutable $date,
        ?int $ignoreAppointmentId = null,
        ?int $ignoreHoldId = null,
    ): Collection {
        if (! $profile->isPubliclyBookable()
            || $option->doctor_profile_id !== $profile->id
            || ! $option->is_active
            || $option->duration_minutes < 1) {
            return collect();
        }

        $timezone = $profile->timezone ?: config('app.timezone', 'UTC');
        $localDate = $date->setTimezone($timezone)->startOfDay();
        $dayStart = $localDate->utc();
        $dayEnd = $localDate->endOfDay()->utc();
        $datedSlots = DoctorAppointmentSlot::where('doctor_profile_id', $profile->id)
            ->where('starts_at', '>=', $dayStart)->where('starts_at', '<=', $dayEnd)->get();
        if ($datedSlots->isNotEmpty()) {
            return $datedSlots->where('consultation_option_id', $option->id)->where('status', DoctorAppointmentSlot::OPEN)
                ->map(function (DoctorAppointmentSlot $slot) use ($profile, $option, $ignoreAppointmentId, $ignoreHoldId, $timezone): ?array {
                    $start = CarbonImmutable::parse($slot->getRawOriginal('starts_at'), 'UTC')->setTimezone($timezone);
                    $end = CarbonImmutable::parse($slot->getRawOriginal('ends_at'), 'UTC')->setTimezone($timezone);
                    if ($start->lessThanOrEqualTo(CarbonImmutable::now($timezone)) || $this->hasBlockingAppointment($profile, $start, $end, $ignoreAppointmentId, $ignoreHoldId)) return null;
                    return [
                        'value' => $start->format('Y-m-d\\TH:i'), 'label' => $start->format('g:i A'),
                        'starts_at' => $start, 'ends_at' => $end, 'doctor_appointment_slot_id' => $slot->id,
                        'meeting_link' => $slot->meeting_url, 'clinic_location' => $slot->clinic_location,
                    ];
                })->filter()->sortBy(fn (array $slot) => $slot['starts_at']->timestamp)->values();
        }
        if (! $profile->hasPublishedAvailability()) return collect();
        $nowUtc = CarbonImmutable::now('UTC');
        $slots = collect();
        foreach ($this->schedule->intervalsForDate($profile, $localDate) as $interval) {
            $cursor = $interval['start'];
            while ($cursor->addMinutes($option->duration_minutes)->lessThanOrEqualTo($interval['end'])) {
                $end = $cursor->addMinutes($option->duration_minutes);
                if ($cursor->utc()->greaterThan($nowUtc) && ! $this->hasBlockingAppointment($profile, $cursor, $end, $ignoreAppointmentId, $ignoreHoldId)) {
                    $slots->push([
                        'value' => $cursor->format('Y-m-d\\TH:i'),
                        'label' => $cursor->format('g:i A'),
                        'starts_at' => $cursor,
                        'ends_at' => $end,
                        'doctor_appointment_slot_id' => null,
                        'meeting_link' => null,
                        'clinic_location' => null,
                    ]);
                }
                $cursor = $end;
            }
        }
        return $slots->values();
    }

    public function hasBlockingAppointment(
        DoctorProfile $profile,
        CarbonImmutable $start,
        CarbonImmutable $end,
        ?int $ignoreAppointmentId = null,
        ?int $ignoreHoldId = null,
    ): bool
    {
        $appointmentBlocks = Appointment::query()->where('doctor_profile_id', $profile->id)
            ->when($ignoreAppointmentId, fn ($query) => $query->where('id', '!=', $ignoreAppointmentId))
            ->where('starts_at', '<', $end->utc())
            ->where('ends_at', '>', $start->utc())
            ->where(function ($query): void {
                $query->whereIn('status', [Appointment::PENDING_CONFIRMATION, Appointment::PENDING_CANCELLATION, Appointment::CONFIRMED, Appointment::RESCHEDULED])
                    ->orWhere(function ($pending): void {
                        $pending->where('status', Appointment::PENDING_PAYMENT)
                            ->where('hold_expires_at', '>', now('UTC'));
                    });
            })->exists();
        if ($appointmentBlocks) return true;

        return AppointmentSlotHold::query()->where('doctor_profile_id', $profile->id)
            ->where('status', AppointmentSlotHold::HELD)
            ->where('expires_at', '>', now('UTC'))
            ->when($ignoreHoldId, fn ($query) => $query->where('id', '!=', $ignoreHoldId))
            ->where('starts_at', '<', $end->utc())
            ->where('ends_at', '>', $start->utc())
            ->exists();
    }

    public function slotIsAvailable(
        DoctorProfile $profile,
        DoctorConsultationOption $option,
        CarbonImmutable $localStart,
        ?int $ignoreAppointmentId = null,
        ?int $ignoreHoldId = null,
        ?int $doctorSlotId = null,
    ): bool {
        if ($localStart->second !== 0) {
            return false;
        }
        $end = $localStart->addMinutes($option->duration_minutes);
        $endOfLocalDay = $localStart->startOfDay()->endOfDay();
        if ($end->greaterThan($endOfLocalDay) || $localStart->lessThanOrEqualTo(CarbonImmutable::now($profile->timezone))) {
            return false;
        }

        return $this->slotsForDate($profile, $option, $localStart, $ignoreAppointmentId, $ignoreHoldId)
            ->contains(fn (array $slot) => $slot['starts_at']->equalTo($localStart)
                && $slot['doctor_appointment_slot_id'] === $doctorSlotId);
    }
}
