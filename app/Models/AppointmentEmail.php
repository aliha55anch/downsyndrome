<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentEmail extends Model
{
    protected $table = 'appointment_email_outbox';
    protected $fillable = ['appointment_id', 'recipient_user_id', 'event_key', 'email_type', 'status', 'snapshot', 'attempts', 'last_error', 'sent_at'];
    protected function casts(): array { return ['snapshot' => 'array', 'sent_at' => 'immutable_datetime']; }
    public function appointment(): BelongsTo { return $this->belongsTo(Appointment::class); }
    public function recipient(): BelongsTo { return $this->belongsTo(User::class, 'recipient_user_id'); }
}
