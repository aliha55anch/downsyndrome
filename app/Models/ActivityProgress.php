<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityProgress extends Model
{
    protected $table = 'activity_progress';

    protected $fillable = [
        'user_id',
        'child_id',
        'activity_id',
        'status',
        'progress_percent',
        'sessions_count',
        'minutes_practiced',
        'engagement',
        'last_practiced_on',
        'notes',
    ];

    protected function casts(): array
    {
        return ['last_practiced_on' => 'date'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
