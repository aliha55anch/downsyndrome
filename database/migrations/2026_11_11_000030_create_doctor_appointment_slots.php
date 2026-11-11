<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('doctor_appointment_slots')) Schema::create('doctor_appointment_slots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('consultation_option_id')->constrained('doctor_consultation_options')->restrictOnDelete();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('timezone', 64);
            $table->string('meeting_url', 2048)->nullable();
            $table->string('clinic_location', 500)->nullable();
            $table->string('status', 20)->default('open')->index();
            $table->timestamps();
            $table->unique(['doctor_profile_id', 'starts_at'], 'doctor_slot_profile_start_unique');
            $table->index(['doctor_profile_id', 'status', 'starts_at'], 'doctor_slot_availability_idx');
        });
        if (Schema::hasTable('appointments') && ! Schema::hasColumn('appointments', 'doctor_appointment_slot_id')) {
            Schema::table('appointments', function (Blueprint $table): void {
                $table->foreignId('doctor_appointment_slot_id')->nullable()->after('consultation_option_id')->constrained('doctor_appointment_slots')->nullOnDelete();
            });
        }
        if (Schema::hasTable('appointment_slot_holds') && ! Schema::hasColumn('appointment_slot_holds', 'doctor_appointment_slot_id')) {
            Schema::table('appointment_slot_holds', function (Blueprint $table): void {
                $table->foreignId('doctor_appointment_slot_id')->nullable()->after('consultation_option_id')->constrained('doctor_appointment_slots')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('appointment_slot_holds', fn (Blueprint $table) => $table->dropConstrainedForeignId('doctor_appointment_slot_id'));
        Schema::table('appointments', fn (Blueprint $table) => $table->dropConstrainedForeignId('doctor_appointment_slot_id'));
        Schema::dropIfExists('doctor_appointment_slots');
    }
};
