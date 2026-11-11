<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentPrescription extends Model
{
    public const DRAFT = 'draft';
    public const PUBLISHED = 'published';

    protected $fillable = ['appointment_id', 'doctor_profile_id', 'patient_user_id', 'status', 'diagnosis', 'medicines', 'instructions', 'notes', 'follow_up', 'published_at'];

    protected function casts(): array { return ['medicines' => 'array', 'published_at' => 'immutable_datetime']; }

    public function appointment(): BelongsTo { return $this->belongsTo(Appointment::class); }
    public function doctorProfile(): BelongsTo { return $this->belongsTo(DoctorProfile::class); }
    public function patient(): BelongsTo { return $this->belongsTo(User::class, 'patient_user_id'); }
}
