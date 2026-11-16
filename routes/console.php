<?php

use App\Jobs\SendAppointmentEmail;
use App\Jobs\SendConsultationRequestEmail;
use App\Models\Appointment;
use App\Models\AppointmentEmail;
use App\Models\ConsultationRequestEmail;
use App\Models\AppointmentSlotHold;
use App\Models\DoctorAppointmentSlot;
use App\Models\PaymentTransaction;
use App\Services\AppointmentEmailDispatcher;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('app:info', function () {
    $this->comment(config('app.name'));
})->purpose('Display the application name');

Artisan::command('appointments:expire-pending', function (): void {
    $expired = 0;
    $expiredSlotHolds = AppointmentSlotHold::where('status', AppointmentSlotHold::HELD)
        ->where('expires_at', '<=', now('UTC'))
        ->update(['status' => AppointmentSlotHold::EXPIRED, 'slot_lock_key' => null, 'updated_at' => now('UTC')]);
    Appointment::where('status', Appointment::PENDING_PAYMENT)->where('hold_expires_at', '<=', now('UTC'))->orderBy('id')->chunkById(100, function ($items) use (&$expired): void {
        foreach ($items as $item) {
            DB::transaction(function () use ($item, &$expired): void {
                $appointment = Appointment::whereKey($item->id)->lockForUpdate()->first();
                if (! $appointment || $appointment->status !== Appointment::PENDING_PAYMENT || $appointment->holdExpiresAtUtc()?->greaterThan(now('UTC'))) {
                    return;
                }
                $from = $appointment->status;
                $appointment->update(['status' => Appointment::EXPIRED, 'slot_lock_key' => null]);
                if ($appointment->doctor_appointment_slot_id) {
                    DoctorAppointmentSlot::whereKey($appointment->doctor_appointment_slot_id)->where('status', DoctorAppointmentSlot::BOOKED)->update(['status' => DoctorAppointmentSlot::OPEN]);
                }
                $appointment->payments()->where('status', PaymentTransaction::PENDING)->update(['status' => PaymentTransaction::EXPIRED, 'updated_at' => now()]);
                $appointment->events()->create(['event_type' => 'payment_hold_expired', 'from_status' => $from, 'to_status' => Appointment::EXPIRED, 'created_at' => now()]);
                $expired++;
            });
        }
    });
    $this->info("Expired {$expiredSlotHolds} slot-selection holds and {$expired} appointment payment holds.");
})->purpose('Release appointment slots whose Safepay checkout holds expired');

Artisan::command('appointments:send-reminders', function (): void {
    $dispatcher = app(AppointmentEmailDispatcher::class);
    $appointments = Appointment::with(['patient', 'doctorProfile.user', 'payments'])
        ->whereIn('status', [Appointment::CONFIRMED, Appointment::RESCHEDULED])
        ->where('starts_at', '>', now('UTC'))->where('starts_at', '<=', now('UTC')->addHours(25))->get();
    foreach ($appointments as $appointment) {
        $minutes = now('UTC')->diffInMinutes($appointment->startsAtUtc(), false);
        $column = $minutes <= 90 ? 'reminder_1h_sent_at' : ($minutes <= 1440 ? 'reminder_24h_sent_at' : null);
        if (! $column || $appointment->{$column}) {
            continue;
        }
        $claimed = Appointment::whereKey($appointment->id)->whereNull($column)->update([$column => now('UTC')]);
        if ($claimed) {
            $dispatcher->dispatch($appointment->fresh(), $column === 'reminder_1h_sent_at' ? 'reminder_1h' : 'reminder_24h');
        }
    }
    $this->info('Appointment reminder scan complete.');
})->purpose('Queue timezone-aware appointment reminders without duplicates');

Artisan::command('appointments:dispatch-email-outbox', function (): void {
    if (! config('services.google_apps_script.web_app_url') || ! config('services.google_apps_script.shared_secret')) {
        $this->warn('Google Apps Script is not configured; email outboxes remain pending.');

        return;
    }
    AppointmentEmail::whereIn('status', ['pending', 'failed'])->orderBy('id')->limit(100)->get()->each(function ($message): void {
        $claimed = AppointmentEmail::whereKey($message->id)->whereIn('status', ['pending', 'failed'])->update(['status' => 'queued', 'last_error' => null]);
        if ($claimed) {
            SendAppointmentEmail::dispatch($message->id)->afterCommit();
        }
    });
    ConsultationRequestEmail::whereIn('status', ['pending', 'failed'])->orderBy('id')->limit(100)->get()->each(function ($message): void {
        $claimed = ConsultationRequestEmail::whereKey($message->id)->whereIn('status', ['pending', 'failed'])->update(['status' => 'queued', 'last_error' => null]);
        if ($claimed) {
            SendConsultationRequestEmail::dispatch($message->id)->afterCommit();
        }
    });
    $this->info('Pending appointment and consultation request email messages queued.');
})->purpose('Dispatch pending appointment email outbox rows');

Schedule::command('appointments:expire-pending')->everyMinute()->withoutOverlapping();
Schedule::command('appointments:send-reminders')->everyMinute()->withoutOverlapping();
Schedule::command('appointments:dispatch-email-outbox')->everyFiveMinutes()->withoutOverlapping();