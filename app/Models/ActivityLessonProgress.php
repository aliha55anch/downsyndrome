<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLessonProgress extends Model
{
    protected $fillable = [
        'user_id',
        'child_id',
        'activity_id',
        'activity_lesson_id',
        'status',
        'completed_at',
    ];

    protected function casts(): array
    {
        return ['completed_at' => 'datetime'];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(ActivityLesson::class, 'activity_lesson_id');
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
