<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
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

        VerifyEmail::toMailUsing(function (object $notifiable, string $url): MailMessage {
            return (new MailMessage)
                ->subject('Verify your Little Steps email')
                ->view('emails.action', [
                    'brandName' => config('app.name', 'Little Steps'),
                    'title' => 'Verify your email address',
                    'eyebrow' => 'WELCOME TO LITTLE STEPS',
                    'intro' => 'Confirm your email address to finish setting up your Little Steps account.',
                    'actionLabel' => 'Verify email address',
                    'actionUrl' => $url,
                    'expiryText' => 'This secure verification link expires in '.config('auth.verification.expire', 60).' minutes.',
                    'safetyText' => 'If you did not create a Little Steps account, you can safely ignore this email.',
                ]);
        });

        ResetPassword::toMailUsing(function (object $notifiable, string $token): MailMessage {
            $url = route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]);
            $expiry = config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

            return (new MailMessage)
                ->subject('Reset your Little Steps password')
                ->view('emails.action', [
                    'brandName' => config('app.name', 'Little Steps'),
                    'title' => 'Reset your password',
                    'eyebrow' => 'ACCOUNT SECURITY',
                    'intro' => 'We received a request to reset the password for your Little Steps account.',
                    'actionLabel' => 'Choose a new password',
                    'actionUrl' => $url,
                    'expiryText' => 'This secure password reset link expires in '.$expiry.' minutes.',
                    'safetyText' => 'If you did not request a password reset, ignore this email. Your password will not change.',
                ]);
        });
    }
}
