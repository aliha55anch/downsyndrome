<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Activity extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUSES = [self::STATUS_DRAFT, self::STATUS_PUBLISHED];

    public const AGE_GROUPS = [
        '0 to 6 months',
        '6 to 12 months',
        '1 to 2 years',
        '2 to 3 years',
        '3 to 5 years',
        '5+ years',
    ];

    public const DEVELOPMENT_AREAS = [
        'Communication and language',
        'Cognitive development',
        'Motor skills',
        'Social interaction',
        'Daily living skills',
        'Sensory learning',
    ];

    protected $fillable = [
        'title',
        'icon_path',
        'short_description',
        'detailed_description',
        'age_group',
        'min_age',
        'max_age',
        'development_area',
        'difficulty',
        'estimated_minutes',
        'materials',
        'learning_objective',
        'instructions',
        'parent_tips',
        'disclaimer',
        'content_source',
        'reviewer_name',
        'status',
        'featured_image_path',
        'pptx_path',
        'pdf_path',
        'youtube_url',
        'content_blocks',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'content_blocks' => 'array',
            'published_at' => 'datetime',
        ];
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('status', self::STATUS_PUBLISHED)
            ->whereNotNull('published_at');
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED && $this->published_at !== null;
    }

    public function sections(): HasMany
    {
        return $this->hasMany(ActivitySection::class)->orderBy('position');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(ActivityLesson::class)->orderBy('position');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(ActivityEnrollment::class);
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
