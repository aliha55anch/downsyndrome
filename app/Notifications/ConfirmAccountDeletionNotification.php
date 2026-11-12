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
        return (new MailMessage)->subject('Confirm Little Steps account deletion')->line('If you requested account deletion, use the button below.')->action('Delete account', $this->url)->line('This link expires in 60 minutes. If you did not request this, ignore this message.');
    }
}
