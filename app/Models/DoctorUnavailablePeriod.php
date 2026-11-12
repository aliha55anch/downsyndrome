<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\CarbonImmutable;

class DoctorUnavailablePeriod extends Model
{
    protected $fillable = ['type', 'label', 'starts_at', 'ends_at', 'all_day', 'timezone'];
    protected function casts(): array { return ['starts_at' => 'immutable_datetime', 'ends_at' => 'immutable_datetime', 'all_day' => 'boolean']; }
    public function doctorProfile(): BelongsTo { return $this->belongsTo(DoctorProfile::class); }

    public function localStartsAt(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->getRawOriginal('starts_at'), 'UTC')->setTimezone($this->timezone);
    }

    public function localEndsAt(): CarbonImmutable
    {
        return CarbonImmutable::parse($this->getRawOriginal('ends_at'), 'UTC')->setTimezone($this->timezone);
    }

    public function hasStarted(): bool
    {
        return CarbonImmutable::parse($this->getRawOriginal('starts_at'), 'UTC')->lessThanOrEqualTo(CarbonImmutable::now('UTC'));
    }
}
