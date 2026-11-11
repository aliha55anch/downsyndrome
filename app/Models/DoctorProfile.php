<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

class DoctorProfile extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_SUSPENDED = 'suspended';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_UNDER_REVIEW,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_SUSPENDED,
    ];

    protected $fillable = [
        'phone', 'professional_title', 'specialty', 'qualification', 'institution',
        'years_experience', 'workplace', 'city', 'biography', 'license_number',
        'issuing_authority', 'license_expires_at', 'status', 'submitted_at',
        'reviewed_at', 'reviewed_by', 'rejection_reason', 'suspension_reason',
        'profile_photo_path', 'workplace_address', 'languages', 'timezone', 'is_public', 'availability_published_at',
    ];

    protected function casts(): array
    {
        return ['license_expires_at' => 'date', 'submitted_at' => 'datetime', 'reviewed_at' => 'datetime', 'availability_published_at' => 'immutable_datetime', 'languages' => 'array', 'is_public' => 'boolean'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
    public function documents(): HasMany { return $this->hasMany(DoctorVerificationDocument::class); }
    public function currentDocuments(): HasMany { return $this->documents()->where('is_current', true); }
    public function history(): HasMany { return $this->hasMany(DoctorVerificationEvent::class)->latest(); }
    public function qualifications(): HasMany { return $this->hasMany(DoctorQualification::class)->orderBy('sort_order')->orderBy('id'); }
    public function consultationOptions(): HasMany { return $this->hasMany(DoctorConsultationOption::class); }
    public function availabilityPeriods(): HasMany { return $this->hasMany(DoctorAvailabilityPeriod::class)->orderBy('day_of_week')->orderBy('starts_at'); }
    public function unavailablePeriods(): HasMany { return $this->hasMany(DoctorUnavailablePeriod::class)->orderBy('starts_at'); }
    public function specialties(): BelongsToMany { return $this->belongsToMany(MedicalSpecialty::class, 'doctor_profile_specialty')->orderBy('name'); }
    public function appointments(): HasMany { return $this->hasMany(Appointment::class); }
    public function appointmentSlots(): HasMany { return $this->hasMany(DoctorAppointmentSlot::class); }

    public function scopePubliclyBookable(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED)
            ->where('is_public', true)
            ->whereHas('user', fn (Builder $users) => $users->where('role', User::ROLE_APPROVED_DOCTOR));
    }

    public function isPubliclyBookable(): bool
    {
        return $this->status === self::STATUS_APPROVED && $this->is_public && $this->user?->role === User::ROLE_APPROVED_DOCTOR;
    }

    public function hasPublishedAvailability(): bool
    {
        return $this->availability_published_at !== null
            || $this->appointmentSlots()->where('status', DoctorAppointmentSlot::OPEN)->where('starts_at', '>', now('UTC'))->exists();
    }

    public function hasRequiredDocuments(): bool
    {
        return $this->currentDocuments()->whereIn('document_type', ['license', 'qualification'])->whereIn('status', ['pending', 'approved'])->distinct('document_type')->count('document_type') === 2;
    }
}
