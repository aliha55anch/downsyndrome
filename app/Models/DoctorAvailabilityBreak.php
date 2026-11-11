<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorAvailabilityBreak extends Model
{
    protected $fillable = ['label', 'starts_at', 'ends_at'];
    public function period(): BelongsTo { return $this->belongsTo(DoctorAvailabilityPeriod::class, 'availability_period_id'); }
}
