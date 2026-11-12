<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_slot_holds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('patient_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('doctor_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('consultation_option_id')->constrained('doctor_consultation_options')->cascadeOnDelete();
            $table->string('hold_token', 36)->unique();
            $table->string('idempotency_key', 64);
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('timezone', 64);
            $table->string('slot_lock_key', 110)->nullable()->unique();
            $table->string('status', 20)->default('held')->index();
            $table->timestamp('expires_at')->index();
            $table->timestamps();
            $table->unique(['patient_user_id', 'idempotency_key'], 'appointment_slot_hold_patient_idem_unique');
            $table->index(['doctor_profile_id', 'status', 'starts_at', 'ends_at'], 'appointment_slot_hold_doctor_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_slot_holds');
    }
};
