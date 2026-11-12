<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityTrackingPreference extends Model
{
    protected $fillable = ['user_id', 'child_id', 'activity_id', 'is_tracking'];

    protected function casts(): array
    {
        return ['is_tracking' => 'boolean'];
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
