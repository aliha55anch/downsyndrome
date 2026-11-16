<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyNewEmailNotification extends Notification
{
    use Queueable;

    public function __construct(public string $url) {}

    public function via(object $notifiable): array { return ['mail']; }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Confirm your new Little Steps email')
            ->view('emails.action', [
                'brandName' => config('app.name', 'Little Steps'),
                'title' => 'Confirm your new email',
                'eyebrow' => 'EMAIL UPDATE',
                'intro' => 'Confirm this address to finish updating the email on your Little Steps account.',
                'actionLabel' => 'Confirm email address',
                'actionUrl' => $this->url,
                'expiryText' => 'This secure link expires in 60 minutes.',
                'safetyText' => 'If you did not request this email change, ignore this message and your current email will remain on your account.',
            ]);
    }
}
