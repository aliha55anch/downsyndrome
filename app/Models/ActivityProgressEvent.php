<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityProgressEvent extends Model
{
    protected $fillable = ['user_id','child_id','child_name_snapshot','activity_id','activity_title_snapshot','activity_section_id','section_title_snapshot','lesson_title_snapshot','status','completion_method','notes','completed_at'];
    protected function casts(): array { return ['completed_at' => 'datetime']; }
    public function child(): BelongsTo { return $this->belongsTo(Child::class); }
    public function activity(): BelongsTo { return $this->belongsTo(Activity::class); }
}
