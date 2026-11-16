<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConfirmAccountDeletionNotification extends Notification
{
    use Queueable;

    public function __construct(public string $url) {}

    public function via(object $notifiable): array { return ['mail']; }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Confirm Little Steps account deletion')
            ->view('emails.action', [
                'brandName' => config('app.name', 'Little Steps'),
                'title' => 'Confirm account deletion',
                'eyebrow' => 'ACCOUNT SECURITY',
                'intro' => 'A request was made to delete your Little Steps account. Confirm only if you want to continue.',
                'actionLabel' => 'Delete my account',
                'actionUrl' => $this->url,
                'actionColor' => '#a43e3a',
                'expiryText' => 'This secure confirmation link expires in 60 minutes.',
                'safetyText' => 'If you did not request account deletion, ignore this email. Your account will remain active.',
            ]);
    }
}
