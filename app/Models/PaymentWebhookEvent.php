<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentWebhookEvent extends Model
{
    protected $fillable = ['provider', 'event_id', 'event_type', 'provider_reference', 'body_sha256', 'processing_status', 'rejection_reason', 'processed_at'];
    protected function casts(): array { return ['processed_at' => 'immutable_datetime']; }
}
