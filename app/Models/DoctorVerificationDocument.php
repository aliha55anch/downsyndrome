<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorVerificationDocument extends Model
{
    protected $fillable = [
        'document_type', 'original_name', 'storage_path', 'mime_type', 'size_bytes',
        'version', 'status', 'is_current', 'reviewed_by', 'reviewed_at', 'rejection_reason',
    ];

    protected function casts(): array
    {
        return ['is_current' => 'boolean', 'reviewed_at' => 'datetime'];
    }

    public function doctorProfile(): BelongsTo { return $this->belongsTo(DoctorProfile::class); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
}
