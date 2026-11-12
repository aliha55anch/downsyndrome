<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('appointment_cancellation_requests', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('appointment_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason');
            $table->string('appointment_status_before', 32);
            $table->text('response')->nullable();
            $table->unsignedBigInteger('refund_amount_minor')->default(0);
            $table->string('status', 24)->default('pending')->index();
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'requested_at']);
        });

        Schema::create('appointment_refund_cases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('appointment_id')->constrained()->restrictOnDelete();
            $table->foreignId('payment_transaction_id')->constrained('payment_transactions')->restrictOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('idempotency_key', 100)->unique();
            $table->string('reason', 500);
            $table->unsignedBigInteger('amount_minor');
            $table->unsignedBigInteger('amount_previously_refunded_minor')->default(0);
            $table->unsignedBigInteger('deduction_minor')->default(0);
            $table->char('currency', 3);
            $table->string('status', 24)->default('pending_review')->index();
            $table->string('provider_reference', 120)->nullable()->index();
            $table->text('admin_note')->nullable();
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'requested_at']);
        });

        Schema::create('appointment_prescriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('appointment_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_profile_id')->constrained()->restrictOnDelete();
            $table->foreignId('patient_user_id')->constrained('users')->restrictOnDelete();
            $table->string('status', 20)->default('draft')->index();
            $table->text('diagnosis')->nullable();
            $table->json('medicines')->nullable();
            $table->text('instructions')->nullable();
            $table->text('notes')->nullable();
            $table->text('follow_up')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->index(['patient_user_id', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_prescriptions');
        Schema::dropIfExists('appointment_refund_cases');
        Schema::dropIfExists('appointment_cancellation_requests');
    }
};
