<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('patient_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('doctor_profile_id')->constrained()->restrictOnDelete();
            $table->foreignId('consultation_option_id')->nullable()->constrained('doctor_consultation_options')->nullOnDelete();
            $table->string('consultation_type', 20);
            $table->unsignedSmallInteger('duration_minutes');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('timezone', 64);
            $table->unsignedBigInteger('amount_minor')->default(0);
            $table->char('currency', 3);
            $table->string('status', 32)->index();
            $table->timestamp('hold_expires_at')->nullable()->index();
            $table->string('slot_lock_key', 110)->nullable()->unique();
            $table->string('idempotency_key', 64);
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable();
            $table->unsignedSmallInteger('reschedule_count')->default(0);
            $table->timestamp('reminder_24h_sent_at')->nullable();
            $table->timestamp('reminder_1h_sent_at')->nullable();
            $table->timestamps();
            $table->unique(['patient_user_id', 'idempotency_key'], 'appointment_patient_idem_unique');
            $table->index(['doctor_profile_id', 'status', 'starts_at'], 'appointment_doctor_status_start_idx');
            $table->index(['patient_user_id', 'status', 'starts_at'], 'appointment_patient_status_start_idx');
        });

        // Polymorphic billable records keep the provider integration reusable by later modules.
        Schema::create('payment_transactions', function (Blueprint $table): void {
            $table->id();
            $table->morphs('payable');
            $table->foreignId('payer_user_id')->constrained('users')->restrictOnDelete();
            $table->string('provider', 32)->default('safepay')->index();
            $table->string('status', 24)->default('pending')->index();
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3);
            $table->string('idempotency_key', 64)->unique();
            $table->string('provider_reference', 120)->nullable()->unique();
            $table->text('checkout_url')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('failure_code', 120)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['payable_type', 'payable_id', 'status'], 'payment_payable_status_idx');
        });

        Schema::create('payment_webhook_events', function (Blueprint $table): void {
            $table->id();
            $table->string('provider', 32);
            $table->string('event_id', 120);
            $table->string('event_type', 100);
            $table->string('provider_reference', 120)->nullable()->index();
            $table->char('body_sha256', 64);
            $table->string('processing_status', 24)->default('received')->index();
            $table->string('rejection_reason', 160)->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->unique(['provider', 'event_id'], 'payment_webhook_provider_event_unique');
        });

        Schema::create('appointment_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 60);
            $table->string('from_status', 32)->nullable();
            $table->string('to_status', 32)->nullable();
            $table->json('details')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['appointment_id', 'created_at']);
        });

        Schema::create('appointment_email_outbox', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recipient_user_id')->constrained('users')->restrictOnDelete();
            $table->string('event_key', 180)->unique();
            $table->string('email_type', 40);
            $table->string('status', 20)->default('pending')->index();
            $table->json('snapshot')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'updated_at']);
        });

        Schema::create('jobs', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('job_batches', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });

        Schema::create('failed_jobs', function (Blueprint $table): void {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('appointment_email_outbox');
        Schema::dropIfExists('appointment_events');
        Schema::dropIfExists('payment_webhook_events');
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('appointments');
    }
};
