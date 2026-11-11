<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentCancellationRequest extends Model
{
    public const PENDING = 'pending';
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';

    protected $fillable = ['appointment_id', 'requested_by', 'reviewed_by', 'reason', 'response', 'appointment_status_before', 'refund_amount_minor', 'status', 'requested_at', 'reviewed_at'];

    protected function casts(): array
    {
        return ['refund_amount_minor' => 'integer', 'requested_at' => 'immutable_datetime', 'reviewed_at' => 'immutable_datetime'];
    }

    public function appointment(): BelongsTo { return $this->belongsTo(Appointment::class); }
    public function requester(): BelongsTo { return $this->belongsTo(User::class, 'requested_by'); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
}
