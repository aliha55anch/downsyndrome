<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentRefundCase extends Model
{
    public const PENDING_REVIEW = 'pending_review';
    public const PROCESSING = 'processing';
    public const SUCCEEDED = 'succeeded';
    public const FAILED = 'failed';
    public const REJECTED = 'rejected';

    protected $fillable = ['appointment_id', 'payment_transaction_id', 'requested_by', 'approved_by', 'idempotency_key', 'reason', 'amount_minor', 'amount_previously_refunded_minor', 'deduction_minor', 'currency', 'status', 'provider_reference', 'admin_note', 'requested_at', 'completed_at'];

    protected function casts(): array
    {
        return ['amount_minor' => 'integer', 'amount_previously_refunded_minor' => 'integer', 'deduction_minor' => 'integer', 'requested_at' => 'immutable_datetime', 'completed_at' => 'immutable_datetime'];
    }

    public function appointment(): BelongsTo { return $this->belongsTo(Appointment::class); }
    public function payment(): BelongsTo { return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_id'); }
    public function requester(): BelongsTo { return $this->belongsTo(User::class, 'requested_by'); }
}
