<?php

namespace App\Services;

use App\Models\DoctorProfile;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/** Calculates open doctor work intervals for future appointment-booking code. */
class DoctorAvailabilityService
{
    public function canFitDuration(DoctorProfile $profile, CarbonImmutable $startsAt, int $durationMinutes, ?string $consultationType = null): bool
    {
        if (! $profile->isPubliclyBookable() || $durationMinutes < 1) {
            return false;
        }
        if ($consultationType !== null && ! $profile->consultationOptions()->where('type', $consultationType)->where('is_active', true)->exists()) {
            return false;
        }
        $end = $startsAt->addMinutes($durationMinutes);
        foreach ($this->intervalsForDate($profile, $startsAt) as $interval) {
            if ($startsAt->greaterThanOrEqualTo($interval['start']) && $end->lessThanOrEqualTo($interval['end'])) {
                return true;
            }
        }
        return false;
    }

    /** @return Collection<int, array{start: CarbonImmutable, end: CarbonImmutable}> */
    public function intervalsForDate(DoctorProfile $profile, CarbonImmutable $date): Collection
    {
        $timezone = $profile->timezone ?: config('app.timezone', 'UTC');
        try {
            $localDay = $date->setTimezone($timezone)->startOfDay();
        } catch (\Throwable $exception) {
            throw new InvalidArgumentException('The doctor profile has an invalid timezone.', previous: $exception);
        }

        $periods = $profile->availabilityPeriods()->with('breaks')
            ->where('day_of_week', $localDay->dayOfWeek)->get();
        $open = collect();
        foreach ($periods as $period) {
            $start = $localDay->setTimeFromTimeString(substr((string) $period->starts_at, 0, 8));
            $end = $localDay->setTimeFromTimeString(substr((string) $period->ends_at, 0, 8));
            $segments = collect([['start' => $start, 'end' => $end]]);

            foreach ($period->breaks as $break) {
                $breakStart = $localDay->setTimeFromTimeString(substr((string) $break->starts_at, 0, 8));
                $breakEnd = $localDay->setTimeFromTimeString(substr((string) $break->ends_at, 0, 8));
                $segments = $this->subtract($segments, $breakStart, $breakEnd);
            }
            $open = $open->concat($segments);
        }

        $dayStartUtc = $localDay->utc();
        $dayEndUtc = $localDay->endOfDay()->utc();
        $unavailable = $profile->unavailablePeriods()
            ->where('starts_at', '<=', $dayEndUtc)
            ->where('ends_at', '>=', $dayStartUtc)->get();
        foreach ($unavailable as $period) {
            // SQL timestamp columns are stored as UTC wall values; read their raw
            // representation before applying the doctor's display timezone.
            $blockStart = CarbonImmutable::parse($period->getRawOriginal('starts_at'), 'UTC')->setTimezone($timezone);
            $blockEnd = CarbonImmutable::parse($period->getRawOriginal('ends_at'), 'UTC')->setTimezone($timezone);
            $open = $this->subtract($open, $blockStart, $blockEnd);
        }

        return $open->sortBy(fn (array $interval) => $interval['start']->timestamp)->values();
    }

    /** @return Collection<int, array{date: string, intervals: Collection}> */
    public function intervalsForRange(DoctorProfile $profile, CarbonImmutable $from, CarbonImmutable $through): Collection
    {
        if ($through->lessThan($from)) {
            throw new InvalidArgumentException('The availability range end must be on or after its start.');
        }
        $timezone = $profile->timezone ?: config('app.timezone', 'UTC');
        $first = $from->setTimezone($timezone)->startOfDay();
        $last = $through->setTimezone($timezone)->startOfDay();
        if ($first->diffInDays($last) > 92) {
            throw new InvalidArgumentException('Availability ranges may cover at most 93 days.');
        }
        $days = collect();
        for ($day = $first; $day->lessThanOrEqualTo($last); $day = $day->addDay()) {
            $days->push(['date' => $day->toDateString(), 'intervals' => $this->intervalsForDate($profile, $day)]);
        }
        return $days;
    }

    /** @param Collection<int, array{start: CarbonImmutable, end: CarbonImmutable}> $segments */
    private function subtract(Collection $segments, CarbonImmutable $blockedStart, CarbonImmutable $blockedEnd): Collection
    {
        if ($blockedEnd->lessThanOrEqualTo($blockedStart)) {
            return $segments;
        }
        $result = collect();
        foreach ($segments as $segment) {
            if ($blockedEnd->lessThanOrEqualTo($segment['start']) || $blockedStart->greaterThanOrEqualTo($segment['end'])) {
                $result->push($segment);
                continue;
            }
            if ($blockedStart->greaterThan($segment['start'])) {
                $result->push(['start' => $segment['start'], 'end' => $blockedStart->min($segment['end'])]);
            }
            if ($blockedEnd->lessThan($segment['end'])) {
                $result->push(['start' => $blockedEnd->max($segment['start']), 'end' => $segment['end']]);
            }
        }
        return $result->filter(fn (array $segment) => $segment['end']->greaterThan($segment['start']))->values();
    }
}
