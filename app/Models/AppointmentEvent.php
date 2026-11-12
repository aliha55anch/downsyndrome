<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentEvent extends Model
{
    public $timestamps = false;
    protected $fillable = ['actor_user_id', 'event_type', 'from_status', 'to_status', 'details', 'created_at'];
    protected function casts(): array { return ['details' => 'array', 'created_at' => 'immutable_datetime']; }
    public function appointment(): BelongsTo { return $this->belongsTo(Appointment::class); }
    public function actor(): BelongsTo { return $this->belongsTo(User::class, 'actor_user_id'); }
}
