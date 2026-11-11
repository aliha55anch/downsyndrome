<?php

namespace App\Models;

use App\Casts\UtcDateTime;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorAppointmentSlot extends Model
{
    public const OPEN = 'open';
    public const BOOKED = 'booked';
    public const CLOSED = 'closed';

    protected $fillable = ['doctor_profile_id', 'consultation_option_id', 'starts_at', 'ends_at', 'timezone', 'meeting_url', 'clinic_location', 'status'];

    protected function casts(): array
    {
        return ['starts_at' => UtcDateTime::class, 'ends_at' => UtcDateTime::class];
    }

    public function doctorProfile(): BelongsTo { return $this->belongsTo(DoctorProfile::class); }
    public function consultationOption(): BelongsTo { return $this->belongsTo(DoctorConsultationOption::class); }
    public function appointment(): \Illuminate\Database\Eloquent\Relations\HasOne { return $this->hasOne(Appointment::class, 'doctor_appointment_slot_id'); }
    public function localStartsAt(): CarbonImmutable { return CarbonImmutable::parse($this->getRawOriginal('starts_at'), 'UTC')->setTimezone($this->timezone); }
    public function localEndsAt(): CarbonImmutable { return CarbonImmutable::parse($this->getRawOriginal('ends_at'), 'UTC')->setTimezone($this->timezone); }
}
