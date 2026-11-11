<?php

namespace App\Models;

use App\Casts\UtcDateTime;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Appointment extends Model
{
    public const PENDING_PAYMENT = 'pending_payment';
    public const PENDING_CONFIRMATION = 'pending_confirmation';
    public const PENDING_CANCELLATION = 'pending_cancellation';
    public const CONFIRMED = 'confirmed';
    public const RESCHEDULED = 'rescheduled';
    public const CANCELLED = 'cancelled';
    public const COMPLETED = 'completed';
    public const NO_SHOW = 'no_show';
    public const EXPIRED = 'expired';

    public const BLOCKING_STATUSES = [self::PENDING_PAYMENT, self::PENDING_CONFIRMATION, self::PENDING_CANCELLATION, self::CONFIRMED, self::RESCHEDULED];
    public const STATUSES = [self::PENDING_PAYMENT, self::PENDING_CONFIRMATION, self::PENDING_CANCELLATION, self::CONFIRMED, self::RESCHEDULED, self::CANCELLED, self::COMPLETED, self::NO_SHOW, self::EXPIRED];

    protected $fillable = [
        'patient_user_id', 'doctor_profile_id', 'consultation_option_id', 'doctor_appointment_slot_id', 'consultation_type', 'duration_minutes',
        'starts_at', 'ends_at', 'timezone', 'meeting_link', 'amount_minor', 'currency', 'status', 'hold_expires_at',
        'slot_lock_key', 'idempotency_key', 'cancelled_by', 'cancellation_reason', 'reschedule_count',
        'reminder_24h_sent_at', 'reminder_1h_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => UtcDateTime::class, 'ends_at' => UtcDateTime::class,
            'hold_expires_at' => UtcDateTime::class, 'reminder_24h_sent_at' => UtcDateTime::class,
            'reminder_1h_sent_at' => UtcDateTime::class, 'amount_minor' => 'integer',
        ];
    }

    public function patient(): BelongsTo { return $this->belongsTo(User::class, 'patient_user_id'); }
    public function doctorProfile(): BelongsTo { return $this->belongsTo(DoctorProfile::class); }
    public function consultationOption(): BelongsTo { return $this->belongsTo(DoctorConsultationOption::class); }
    public function doctorSlot(): BelongsTo { return $this->belongsTo(DoctorAppointmentSlot::class, 'doctor_appointment_slot_id'); }
    public function events(): HasMany { return $this->hasMany(AppointmentEvent::class)->orderByDesc('created_at'); }
    public function payments(): MorphMany { return $this->morphMany(PaymentTransaction::class, 'payable')->latest(); }
    public function emailOutbox(): HasMany { return $this->hasMany(AppointmentEmail::class); }
    public function cancellationRequest(): HasOne { return $this->hasOne(AppointmentCancellationRequest::class); }
    public function prescription(): HasOne { return $this->hasOne(AppointmentPrescription::class); }
    public function refundCases(): HasMany { return $this->hasMany(AppointmentRefundCase::class); }

    public function localStartsAt(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->getRawOriginal('starts_at'), 'UTC')->setTimezone($this->timezone);
    }

    public function startsAtUtc(): CarbonImmutable { return CarbonImmutable::parse($this->getRawOriginal('starts_at'), 'UTC'); }

    public function localEndsAt(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->getRawOriginal('ends_at'), 'UTC')->setTimezone($this->timezone);
    }

    public function endsAtUtc(): CarbonImmutable { return CarbonImmutable::parse($this->getRawOriginal('ends_at'), 'UTC'); }

    public function holdExpiresAtUtc(): ?CarbonImmutable
    {
        $raw = $this->getRawOriginal('hold_expires_at');
        return $raw ? CarbonImmutable::parse($raw, 'UTC') : null;
    }

    public function isActive(): bool
    {
        return in_array($this->status, [self::PENDING_PAYMENT, self::PENDING_CONFIRMATION, self::CONFIRMED, self::RESCHEDULED], true);
    }
}
