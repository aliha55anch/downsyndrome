<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivityLesson extends Model
{
    protected $fillable = ['activity_id', 'submission_key', 'title', 'description', 'position'];
    public function activity(): BelongsTo { return $this->belongsTo(Activity::class); }
    public function sections(): HasMany { return $this->hasMany(ActivitySection::class, 'lesson_id')->orderBy('position'); }
    public function progress(): HasMany { return $this->hasMany(ActivityLessonProgress::class, 'activity_lesson_id'); }
}
