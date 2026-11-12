<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorConsultationOption extends Model
{
    protected $fillable = ['type', 'duration_minutes', 'fee', 'is_active'];
    protected function casts(): array { return ['fee' => 'decimal:2', 'is_active' => 'boolean']; }
    public function doctorProfile(): BelongsTo { return $this->belongsTo(DoctorProfile::class); }
}
