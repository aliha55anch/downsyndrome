<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityEnrollment extends Model
{
    protected $fillable = [
        'user_id',
        'activity_id',
        'child_id',
        'last_lesson_id',
        'last_section_id',
        'status',
        'progress_percent',
        'enrolled_at',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'progress_percent' => 'integer',
            'enrolled_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function activity(): BelongsTo { return $this->belongsTo(Activity::class); }
    public function child(): BelongsTo { return $this->belongsTo(Child::class); }
    public function lastLesson(): BelongsTo { return $this->belongsTo(ActivityLesson::class, 'last_lesson_id'); }
    public function lastSection(): BelongsTo { return $this->belongsTo(ActivitySection::class, 'last_section_id'); }
}
