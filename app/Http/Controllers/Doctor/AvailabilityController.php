<?php

namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Models\DoctorProfile;
use App\Models\DoctorAppointmentSlot;
use App\Models\Appointment;
use App\Models\AppointmentSlotHold;
use App\Services\DoctorAvailabilityService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AvailabilityController extends Controller
{
    public function edit(Request $request): View
    {
        $profile = $request->user()->doctorProfile()->firstOrFail();
        $periods = $profile->availabilityPeriods()->with('breaks')->get();
        $leaves = $profile->unavailablePeriods()->where('ends_at', '>=', now('UTC')->subDay())->get();
        $options = $profile->consultationOptions;
        $datedSlots = $profile->appointmentSlots()->with('consultationOption')->where('starts_at', '>=', now('UTC')->subDay())->orderBy('starts_at')->limit(100)->get();
        $calendar = app(DoctorAvailabilityService::class)->intervalsForRange(
            $profile, CarbonImmutable::now($profile->timezone)->startOfMonth(), CarbonImmutable::now($profile->timezone)->endOfMonth()
        );
        return view('doctor.availability', compact('profile', 'periods', 'leaves', 'options', 'calendar', 'datedSlots'));
    }

    public function storeAppointmentSlot(Request $request): RedirectResponse
    {
        $profile = $request->user()->doctorProfile()->firstOrFail();
        abort_unless($profile->isPubliclyBookable(), 403);
        $data = $request->validate([
            'consultation_option_id' => ['required', 'integer'],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'starts_at' => ['required', 'date_format:H:i'], 'ends_at' => ['required', 'date_format:H:i', 'after:starts_at'],
            'meeting_url' => ['nullable', 'url', 'max:2048', 'regex:/^https:\/\//i'],
            'clinic_location' => ['nullable', 'string', 'max:500'],
        ]);
        $option = $profile->consultationOptions()->whereKey($data['consultation_option_id'])->where('is_active', true)->firstOrFail();
        if ($option->type === 'online' && blank($data['meeting_url'] ?? null)) {
            throw ValidationException::withMessages(['meeting_url' => 'An online slot must have its own secure HTTPS meeting URL.']);
        }
        $clinic = trim((string) ($data['clinic_location'] ?? $profile->workplace_address));
        if ($option->type === 'in_person' && $clinic === '') {
            throw ValidationException::withMessages(['clinic_location' => 'Add a clinic location to the physical consultation slot.']);
        }
        if ((int) CarbonImmutable::parse($data['date'].' '.$data['starts_at'], $profile->timezone)->diffInMinutes(CarbonImmutable::parse($data['date'].' '.$data['ends_at'], $profile->timezone)) !== (int) $option->duration_minutes) {
            throw ValidationException::withMessages(['ends_at' => 'The slot length must match the consultation duration ('.$option->duration_minutes.' minutes).']);
        }
        $timezone = $profile->timezone ?: config('app.timezone');
        $start = CarbonImmutable::createFromFormat('!Y-m-d H:i', $data['date'].' '.$data['starts_at'], $timezone);
        $end = CarbonImmutable::createFromFormat('!Y-m-d H:i', $data['date'].' '.$data['ends_at'], $timezone);
        abort_if($start->lessThanOrEqualTo(CarbonImmutable::now($timezone)), 422, 'Appointment slots must start in the future.');
        DB::transaction(function () use ($profile, $option, $start, $end, $timezone, $data, $clinic): void {
            DoctorProfile::whereKey($profile->id)->lockForUpdate()->firstOrFail();
            if (DoctorAppointmentSlot::where('doctor_profile_id', $profile->id)->where('starts_at', '<', $end->utc())->where('ends_at', '>', $start->utc())->exists()
                || Appointment::where('doctor_profile_id', $profile->id)->whereIn('status', Appointment::BLOCKING_STATUSES)->where('starts_at', '<', $end->utc())->where('ends_at', '>', $start->utc())->exists()
                || AppointmentSlotHold::where('doctor_profile_id', $profile->id)->where('status', AppointmentSlotHold::HELD)->where('expires_at', '>', now('UTC'))->where('starts_at', '<', $end->utc())->where('ends_at', '>', $start->utc())->exists()) {
                throw ValidationException::withMessages(['starts_at' => 'This time overlaps an existing slot, appointment, or active reservation.']);
            }
            DoctorAppointmentSlot::create([
                'doctor_profile_id' => $profile->id, 'consultation_option_id' => $option->id,
                'starts_at' => $start->utc(), 'ends_at' => $end->utc(), 'timezone' => $timezone,
                'meeting_url' => $option->type === 'online' ? $data['meeting_url'] : null,
                'clinic_location' => $option->type === 'in_person' ? $clinic : null,
                'status' => DoctorAppointmentSlot::OPEN,
            ]);
        }, attempts: 3);
        return back()->with('status', 'Dated appointment slot created and published to patients.');
    }

    public function closeAppointmentSlot(Request $request, DoctorAppointmentSlot $slot): RedirectResponse
    {
        $profile = $request->user()->doctorProfile()->firstOrFail();
        abort_unless($slot->doctor_profile_id === $profile->id, 404);
        DB::transaction(function () use ($slot): void {
            $locked = DoctorAppointmentSlot::whereKey($slot->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === DoctorAppointmentSlot::OPEN && $locked->starts_at->isFuture(), 409);
            if (AppointmentSlotHold::where('doctor_appointment_slot_id', $locked->id)->where('status', AppointmentSlotHold::HELD)->where('expires_at', '>', now('UTC'))->exists()) {
                throw ValidationException::withMessages(['slot' => 'A patient is reviewing this slot. Wait for their hold to expire before closing it.']);
            }
            if (Appointment::where('doctor_appointment_slot_id', $locked->id)->whereIn('status', Appointment::BLOCKING_STATUSES)->exists()) {
                throw ValidationException::withMessages(['slot' => 'This slot is attached to an active appointment and cannot be closed. Resolve that appointment first.']);
            }
            $locked->update(['status' => DoctorAppointmentSlot::CLOSED]);
        });
        return back()->with('status', 'Future slot closed.');
    }

    public function saveConsultations(Request $request): RedirectResponse
    {
        $profile = $request->user()->doctorProfile()->firstOrFail();
        $data = $request->validate([
            'consultations' => ['nullable', 'array', 'max:2'],
            'consultations.*.type' => ['required', 'in:online,in_person', 'distinct'],
            'consultations.*.duration_minutes' => ['required', 'integer', 'min:10', 'max:240'],
            'consultations.*.fee' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'consultations.*.is_active' => ['nullable', 'boolean'],
        ]);
        if ($profile->appointments()->whereIn('status', \App\Models\Appointment::BLOCKING_STATUSES)->where('starts_at', '>', now('UTC'))->exists()) {
            throw ValidationException::withMessages(['consultations' => 'Consultation settings cannot be replaced while future appointments are active. Complete or resolve those appointments first.']);
        }
        DB::transaction(function () use ($profile, $data): void {
            $profile->consultationOptions()->delete();
            foreach (array_values($data['consultations'] ?? []) as $option) {
                $profile->consultationOptions()->create([
                    'type' => $option['type'], 'duration_minutes' => $option['duration_minutes'],
                    'fee' => ($option['fee'] ?? '') === '' ? null : $option['fee'], 'is_active' => (bool) ($option['is_active'] ?? false),
                ]);
            }
        });
        return back()->with('status', 'Consultation options saved.');
    }

    public function saveWeekly(Request $request): RedirectResponse
    {
        $profile = $request->user()->doctorProfile()->firstOrFail();
        $data = $request->validate([
            'periods' => ['nullable', 'array', 'max:42'],
            'periods.*.day_of_week' => ['required', 'integer', 'between:0,6'],
            'periods.*.starts_at' => ['required', 'date_format:H:i'],
            'periods.*.ends_at' => ['required', 'date_format:H:i', 'after:periods.*.starts_at'],
            'periods.*.breaks' => ['nullable', 'array', 'max:8'],
            'periods.*.breaks.*.label' => ['nullable', 'string', 'max:100'],
            'periods.*.breaks.*.starts_at' => ['required_with:periods.*.breaks', 'date_format:H:i'],
            'periods.*.breaks.*.ends_at' => ['required_with:periods.*.breaks', 'date_format:H:i'],
        ]);
        $periods = array_values($data['periods'] ?? []);
        $activeBookings = $profile->appointments()->whereIn('status', \App\Models\Appointment::BLOCKING_STATUSES)->where('starts_at', '>', now('UTC'))->get();
        foreach ($activeBookings as $booking) {
            $localStart = $booking->localStartsAt();
            $localEnd = $booking->localEndsAt();
            $matches = collect($periods)->filter(fn ($period) => (int) $period['day_of_week'] === $localStart->dayOfWeek)->contains(function ($period) use ($localStart, $localEnd): bool {
                $start = CarbonImmutable::parse($localStart->toDateString().' '.$period['starts_at'], $localStart->timezone);
                $end = CarbonImmutable::parse($localStart->toDateString().' '.$period['ends_at'], $localStart->timezone);
                if ($localStart->lessThan($start) || $localEnd->greaterThan($end)) return false;
                foreach ($period['breaks'] ?? [] as $break) {
                    $breakStart = CarbonImmutable::parse($localStart->toDateString().' '.$break['starts_at'], $localStart->timezone);
                    $breakEnd = CarbonImmutable::parse($localStart->toDateString().' '.$break['ends_at'], $localStart->timezone);
                    if ($localStart->lessThan($breakEnd) && $localEnd->greaterThan($breakStart)) return false;
                }
                return true;
            });
            if (! $matches) throw ValidationException::withMessages(['periods' => 'These hours would make an existing appointment unavailable. Keep its working period and break coverage, or contact support to resolve the booking first.']);
        }
        $byDay = [];
        foreach ($periods as $index => $period) {
            $start = $this->minutes($period['starts_at']);
            $end = $this->minutes($period['ends_at']);
            if ($end <= $start) {
                throw ValidationException::withMessages(["periods.$index.ends_at" => 'The end time must be after the start time.']);
            }
            foreach ($byDay[$period['day_of_week']] ?? [] as [$otherStart, $otherEnd]) {
                if ($start < $otherEnd && $end > $otherStart) {
                    throw ValidationException::withMessages(["periods.$index.starts_at" => 'Working periods on the same weekday cannot overlap.']);
                }
            }
            $byDay[$period['day_of_week']][] = [$start, $end];
            $breaks = array_values($period['breaks'] ?? []);
            usort($breaks, fn ($a, $b) => $this->minutes($a['starts_at']) <=> $this->minutes($b['starts_at']));
            $lastEnd = $start;
            foreach ($breaks as $break) {
                $breakStart = $this->minutes($break['starts_at']);
                $breakEnd = $this->minutes($break['ends_at']);
                if ($breakEnd <= $breakStart || $breakStart < $start || $breakEnd > $end || $breakStart < $lastEnd) {
                    throw ValidationException::withMessages(["periods.$index.breaks" => 'Breaks must be ordered, non-overlapping, and within their working period.']);
                }
                $lastEnd = $breakEnd;
            }
        }
        DB::transaction(function () use ($profile, $periods): void {
            $profile->availabilityPeriods()->delete();
            foreach ($periods as $period) {
                $breaks = $period['breaks'] ?? [];
                $saved = $profile->availabilityPeriods()->create([
                    'day_of_week' => $period['day_of_week'], 'starts_at' => $period['starts_at'], 'ends_at' => $period['ends_at'],
                ]);
                foreach ($breaks as $break) {
                    $saved->breaks()->create($break);
                }
            }
            $profile->forceFill(['availability_published_at' => null])->save();
        });
        return back()->with('status', 'Weekly availability saved as a draft. Review and publish it to make bookable times visible.');
    }

    public function publishWeekly(Request $request): RedirectResponse
    {
        $profile = $request->user()->doctorProfile()->firstOrFail();
        abort_unless($profile->isPubliclyBookable(), 403);
        if (! $profile->availabilityPeriods()->exists() || ! $profile->consultationOptions()->where('is_active', true)->exists()) {
            throw ValidationException::withMessages(['availability' => 'Add at least one working period and one active consultation option before publishing.']);
        }
        $profile->forceFill(['availability_published_at' => now()])->save();
        return back()->with('status', 'Your current availability is published and can be booked.');
    }

    public function storeUnavailable(Request $request): RedirectResponse
    {
        $profile = $request->user()->doctorProfile()->firstOrFail();
        $data = $request->validate([
            'type' => ['required', 'in:holiday,leave,temporary'], 'label' => ['nullable', 'string', 'max:150'],
            'starts_at' => ['required', 'date'], 'ends_at' => ['required', 'date', 'after:starts_at'],
            'all_day' => ['nullable', 'boolean'],
        ]);
        $zone = $profile->timezone ?: config('app.timezone');
        $start = CarbonImmutable::parse($data['starts_at'], $zone);
        $end = CarbonImmutable::parse($data['ends_at'], $zone);
        abort_if($start->isPast(), 422, 'Unavailable periods must start in the future.');
        $conflict = $profile->unavailablePeriods()->where('starts_at', '<', $end->utc())->where('ends_at', '>', $start->utc())->exists();
        if ($conflict) {
            throw ValidationException::withMessages(['starts_at' => 'This period overlaps another saved unavailable period.']);
        }
        if ($profile->appointments()->whereIn('status', \App\Models\Appointment::BLOCKING_STATUSES)->where('starts_at', '<', $end->utc())->where('ends_at', '>', $start->utc())->exists()) {
            throw ValidationException::withMessages(['starts_at' => 'This time overlaps an active appointment. Resolve the booking before marking the doctor unavailable.']);
        }
        $profile->unavailablePeriods()->create([
            'type' => $data['type'], 'label' => $data['label'] ?? null, 'starts_at' => $start->utc(), 'ends_at' => $end->utc(),
            'all_day' => (bool) ($data['all_day'] ?? false), 'timezone' => $zone,
        ]);
        return back()->with('status', 'Unavailable period saved.');
    }

    public function destroyUnavailable(Request $request, int $period): RedirectResponse
    {
        $profile = $request->user()->doctorProfile()->firstOrFail();
        $record = $profile->unavailablePeriods()->whereKey($period)->firstOrFail();
        abort_if($record->hasStarted(), 422, 'Past unavailable periods are retained as history.');
        $record->delete();
        return back()->with('status', 'Future unavailable period removed.');
    }

    private function minutes(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', $time));
        return $hours * 60 + $minutes;
    }
}
