<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable implements MustVerifyEmail
{
    public const ROLE_PARENT = 'parent';

    public const ROLE_PENDING_DOCTOR = 'pending_doctor';

    public const ROLE_APPROVED_DOCTOR = 'approved_doctor';

    public const ROLE_ADMIN = 'admin';

    public const ROLES = [
        self::ROLE_PARENT,
        self::ROLE_PENDING_DOCTOR,
        self::ROLE_APPROVED_DOCTOR,
        self::ROLE_ADMIN,
    ];

    use Notifiable;

    protected $fillable = [
        'first_name',
        'last_name',
        'name',
        'email',
        'password',
    ];

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function hasRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function parentProfile(): HasOne
    {
        return $this->hasOne(ParentProfile::class);
    }

    public function doctorProfile(): HasOne
    {
        return $this->hasOne(DoctorProfile::class);
    }

    public function patientAppointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'patient_user_id');
    }

    public function sentAppointmentPayments(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class, 'payer_user_id');
    }

    public function doctorVerificationEvents(): HasMany
    {
        return $this->hasMany(DoctorVerificationEvent::class, 'actor_id');
    }

    public function reviewedDoctorProfiles(): HasMany
    {
        return $this->hasMany(DoctorProfile::class, 'reviewed_by');
    }

    public function reviewedDoctorDocuments(): HasMany
    {
        return $this->hasMany(DoctorVerificationDocument::class, 'reviewed_by');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Child::class);
    }

    public function activityProgress(): HasMany
    {
        return $this->hasMany(ActivityProgress::class);
    }

    public function activityEnrollments(): HasMany
    {
        return $this->hasMany(ActivityEnrollment::class);
    }

    public function activitySectionProgress(): HasMany
    {
        return $this->hasMany(ActivitySectionProgress::class);
    }

    public function activityProgressEvents(): HasMany
    {
        return $this->hasMany(ActivityProgressEvent::class);
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'email_change_token_expires_at' => 'datetime',
            'deletion_token_expires_at' => 'datetime',
        ];
    }
}
