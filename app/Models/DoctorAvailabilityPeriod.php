<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DoctorAvailabilityPeriod extends Model
{
    protected $fillable = ['day_of_week', 'starts_at', 'ends_at'];
    public function doctorProfile(): BelongsTo { return $this->belongsTo(DoctorProfile::class); }
    public function breaks(): HasMany { return $this->hasMany(DoctorAvailabilityBreak::class, 'availability_period_id')->orderBy('starts_at'); }
}
