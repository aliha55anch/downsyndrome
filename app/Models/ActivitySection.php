<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivitySection extends Model
{
    protected $fillable = [
        'activity_id',
        'lesson_id',
        'title',
        'icon_path',
        'color',
        'content_type',
        'content',
        'position',
    ];

    protected function casts(): array
    {
        return ['content' => 'array'];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(ActivityLesson::class, 'lesson_id');
    }
}
