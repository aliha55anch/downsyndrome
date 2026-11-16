<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ConsultationRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const PAYMENT_PENDING = 'pending';
    public const PAYMENT_PAID = 'paid';
    public const PAYMENT_FAILED = 'failed';
    public const PAYMENT_NOT_REQUIRED = 'not_required';

    protected $fillable = [
        'doctor_profile_id',
        'patient_user_id',
        'consultation_type',
        'consultation_fee',
        'payment_method',
        'payment_status',
        'request_date',
        'status',
        'patient_notes',
        'admin_notes',
    ];

    protected function casts(): array
    {
        return [
            'consultation_fee' => 'decimal:2',
            'request_date' => 'datetime',
        ];
    }

    public function doctorProfile(): BelongsTo
    {
        return $this->belongsTo(DoctorProfile::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'patient_user_id');
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(PaymentTransaction::class, 'payable')->latest();
    }
}
