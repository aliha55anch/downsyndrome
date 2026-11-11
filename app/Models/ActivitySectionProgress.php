<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivitySectionProgress extends Model
{
    protected $table = 'activity_section_progress';
    protected $fillable = ['user_id','child_id','activity_id','activity_section_id','section_title_snapshot','status','completion_method','completed_at','notes'];
    protected function casts(): array { return ['completed_at' => 'datetime']; }
    public function child(): BelongsTo { return $this->belongsTo(Child::class); }
    public function activity(): BelongsTo { return $this->belongsTo(Activity::class); }
    public function section(): BelongsTo { return $this->belongsTo(ActivitySection::class, 'activity_section_id'); }
}
