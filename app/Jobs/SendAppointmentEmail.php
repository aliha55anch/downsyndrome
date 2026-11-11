<?php

namespace App\Jobs;

use App\Models\AppointmentEmail;
use App\Services\GoogleAppsScriptMailer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendAppointmentEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;
    public array $backoff = [60, 300, 900];

    public function __construct(public int $outboxId) {}

    public function handle(GoogleAppsScriptMailer $mailer): void
    {
        $message = AppointmentEmail::find($this->outboxId);
        if (! $message || $message->status === 'sent') {
            return;
        }
        $message->increment('attempts');
        $mailer->sendAppointmentMessage($message->fresh());
    }

    public function failed(?\Throwable $exception): void
    {
        AppointmentEmail::whereKey($this->outboxId)->where('status', '!=', 'sent')->update([
            'status' => 'failed', 'last_error' => mb_substr($exception?->getMessage() ?? 'Email delivery failed.', 0, 2000), 'updated_at' => now(),
        ]);
    }
}
