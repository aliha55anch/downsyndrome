<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class MedicalSpecialty extends Model
{
    protected $fillable = ['name', 'is_active'];

    protected function casts(): array { return ['is_active' => 'boolean']; }

    protected static function booted(): void
    {
        static::saving(function (self $specialty): void {
            $specialty->name = trim($specialty->name);
            $specialty->name_key = mb_strtolower($specialty->name);
        });
    }

    public function doctorProfiles(): BelongsToMany
    {
        return $this->belongsToMany(DoctorProfile::class, 'doctor_profile_specialty');
    }
}
