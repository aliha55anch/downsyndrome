<?php

namespace App\Models;

use App\Casts\UtcDateTime;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentSlotHold extends Model
{
    public const HELD = 'held';
    public const RELEASED = 'released';
    public const EXPIRED = 'expired';
    public const CONVERTED = 'converted';

    protected $fillable = [
        'patient_user_id', 'doctor_profile_id', 'consultation_option_id', 'doctor_appointment_slot_id', 'hold_token', 'idempotency_key',
        'starts_at', 'ends_at', 'timezone', 'slot_lock_key', 'status', 'expires_at',
    ];

    protected function casts(): array
    {
        return ['starts_at' => UtcDateTime::class, 'ends_at' => UtcDateTime::class, 'expires_at' => UtcDateTime::class];
    }

    public function patient(): BelongsTo { return $this->belongsTo(User::class, 'patient_user_id'); }
    public function doctorProfile(): BelongsTo { return $this->belongsTo(DoctorProfile::class); }
    public function consultationOption(): BelongsTo { return $this->belongsTo(DoctorConsultationOption::class); }
    public function doctorSlot(): BelongsTo { return $this->belongsTo(DoctorAppointmentSlot::class, 'doctor_appointment_slot_id'); }

    public function localStartsAt(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->getRawOriginal('starts_at'), 'UTC')->setTimezone($this->timezone);
    }
}
