<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Child extends Model
{
    protected $fillable = [
        'name',
        'date_of_birth',
        'pronouns',
        'communication_preferences',
        'interests',
        'strengths',
        'support_preferences',
        'learning_goals',
    ];

    protected function casts(): array
    {
        return ['date_of_birth' => 'date'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(ActivityProgress::class);
    }

    public function sectionProgress(): HasMany
    {
        return $this->hasMany(ActivitySectionProgress::class);
    }

    public function progressEvents(): HasMany
    {
        return $this->hasMany(ActivityProgressEvent::class);
    }
}
