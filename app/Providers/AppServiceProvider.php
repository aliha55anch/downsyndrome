<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Child;
use App\Policies\ChildPolicy;
use App\Contracts\MeetingProvider;
use App\Services\ManualMeetingProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MeetingProvider::class, function (): MeetingProvider {
            return match (config('services.meeting.provider', 'manual')) {
                'manual' => new ManualMeetingProvider(),
                default => throw new RuntimeException('The configured meeting provider is not installed. Use MEETING_PROVIDER=manual until a provider adapter is configured.'),
            };
        });
    }

    public function boot(): void
    {
        Gate::policy(Child::class, ChildPolicy::class);
    }
}
