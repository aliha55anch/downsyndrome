<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('app:info', function () {
    $this->comment(config('app.name'));
})->purpose('Display the application name');

Artisan::command('appointments:expire-pending', function (): void {
    $expired = 0;
    $expiredSlotHolds = \App\Models\AppointmentSlotHold::where('status', \App\Models\AppointmentSlotHold::HELD)
        ->where('expires_at', '<=', now('UTC'))
        ->update(['status' => \App\Models\AppointmentSlotHold::EXPIRED, 'slot_lock_key' => null, 'updated_at' => now('UTC')]);
    \App\Models\Appointment::where('status', \App\Models\Appointment::PENDING_PAYMENT)->where('hold_expires_at', '<=', now('UTC'))->orderBy('id')->chunkById(100, function ($items) use (&$expired): void {
        foreach ($items as $item) {
            \Illuminate\Support\Facades\DB::transaction(function () use ($item, &$expired): void {
                $appointment = \App\Models\Appointment::whereKey($item->id)->lockForUpdate()->first();
                if (! $appointment || $appointment->status !== \App\Models\Appointment::PENDING_PAYMENT || $appointment->holdExpiresAtUtc()?->greaterThan(now('UTC'))) return;
                $from = $appointment->status;
                $appointment->update(['status' => \App\Models\Appointment::EXPIRED, 'slot_lock_key' => null]);
                if ($appointment->doctor_appointment_slot_id) \App\Models\DoctorAppointmentSlot::whereKey($appointment->doctor_appointment_slot_id)->where('status', \App\Models\DoctorAppointmentSlot::BOOKED)->update(['status' => \App\Models\DoctorAppointmentSlot::OPEN]);
                $appointment->payments()->where('status', \App\Models\PaymentTransaction::PENDING)->update(['status' => \App\Models\PaymentTransaction::EXPIRED, 'updated_at' => now()]);
                $appointment->events()->create(['event_type' => 'payment_hold_expired', 'from_status' => $from, 'to_status' => \App\Models\Appointment::EXPIRED, 'created_at' => now()]);
                $expired++;
            });
        }
    });
    $this->info("Expired {$expiredSlotHolds} slot-selection holds and {$expired} appointment payment holds.");
})->purpose('Release appointment slots whose Safepay checkout holds expired');

Artisan::command('appointments:send-reminders', function (): void {
    $dispatcher = app(\App\Services\AppointmentEmailDispatcher::class);
    $appointments = \App\Models\Appointment::with(['patient', 'doctorProfile.user', 'payments'])
        ->whereIn('status', [\App\Models\Appointment::CONFIRMED, \App\Models\Appointment::RESCHEDULED])
        ->where('starts_at', '>', now('UTC'))->where('starts_at', '<=', now('UTC')->addHours(25))->get();
    foreach ($appointments as $appointment) {
        $minutes = now('UTC')->diffInMinutes($appointment->startsAtUtc(), false);
        $column = $minutes <= 90 ? 'reminder_1h_sent_at' : ($minutes <= 1440 ? 'reminder_24h_sent_at' : null);
        if (! $column || $appointment->{$column}) continue;
        $claimed = \App\Models\Appointment::whereKey($appointment->id)->whereNull($column)->update([$column => now('UTC')]);
        if ($claimed) $dispatcher->dispatch($appointment->fresh(), $column === 'reminder_1h_sent_at' ? 'reminder_1h' : 'reminder_24h');
    }
    $this->info('Appointment reminder scan complete.');
})->purpose('Queue timezone-aware appointment reminders without duplicates');

Artisan::command('appointments:dispatch-email-outbox', function (): void {
    if (! config('services.google_apps_script.web_app_url') || ! config('services.google_apps_script.shared_secret')) {
        $this->warn('Google Apps Script is not configured; appointment email outbox remains pending.');
        return;
    }
    \App\Models\AppointmentEmail::whereIn('status', ['pending', 'failed'])->orderBy('id')->limit(100)->get()->each(function ($message): void {
        $claimed = \App\Models\AppointmentEmail::whereKey($message->id)->whereIn('status', ['pending', 'failed'])->update(['status' => 'queued', 'last_error' => null]);
        if ($claimed) \App\Jobs\SendAppointmentEmail::dispatch($message->id)->afterCommit();
    });
    $this->info('Pending appointment email messages queued.');
})->purpose('Dispatch pending appointment email outbox rows');

Schedule::command('appointments:expire-pending')->everyMinute()->withoutOverlapping();
Schedule::command('appointments:send-reminders')->everyMinute()->withoutOverlapping();
Schedule::command('appointments:dispatch-email-outbox')->everyFiveMinutes()->withoutOverlapping();
