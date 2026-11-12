<?php

namespace App\Models;

use App\Casts\UtcDateTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentTransaction extends Model
{
    public const PENDING = 'pending';
    public const PAID = 'paid';
    public const FAILED = 'failed';
    public const CANCELLED = 'cancelled';
    public const EXPIRED = 'expired';

    protected $fillable = [
        'payer_user_id', 'provider', 'status', 'amount_minor', 'currency', 'idempotency_key',
        'provider_reference', 'checkout_url', 'expires_at', 'paid_at', 'failed_at', 'failure_code', 'metadata', 'gateway_fee_minor', 'gateway_net_minor',
    ];

    protected function casts(): array
    {
        return ['amount_minor' => 'integer', 'gateway_fee_minor' => 'integer', 'gateway_net_minor' => 'integer', 'expires_at' => UtcDateTime::class, 'paid_at' => UtcDateTime::class, 'failed_at' => UtcDateTime::class, 'metadata' => 'array'];
    }

    public function payable(): MorphTo { return $this->morphTo(); }
    public function payer(): BelongsTo { return $this->belongsTo(User::class, 'payer_user_id'); }
    public function refunds(): HasMany { return $this->hasMany(PaymentRefund::class, 'payment_transaction_id')->latest(); }
}
