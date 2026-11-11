<?php

namespace App\Models;

use App\Casts\UtcDateTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentRefund extends Model
{
    protected $fillable = ['payment_transaction_id', 'provider_event_id', 'provider_reference', 'amount_minor', 'currency', 'status', 'refunded_at'];

    protected function casts(): array
    {
        return ['amount_minor' => 'integer', 'refunded_at' => UtcDateTime::class];
    }

    public function payment(): BelongsTo { return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_id'); }
}
