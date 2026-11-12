<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorVerificationEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'actor_id', 'event_type', 'from_status', 'to_status', 'reason', 'details', 'created_at',
    ];

    protected function casts(): array
    {
        return ['details' => 'array', 'created_at' => 'datetime'];
    }

    public function doctorProfile(): BelongsTo { return $this->belongsTo(DoctorProfile::class); }
    public function actor(): BelongsTo { return $this->belongsTo(User::class, 'actor_id'); }
}
