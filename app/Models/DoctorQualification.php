<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorQualification extends Model
{
    protected $fillable = ['degree', 'institution', 'completion_year', 'sort_order'];
    public function doctorProfile(): BelongsTo { return $this->belongsTo(DoctorProfile::class); }
}
