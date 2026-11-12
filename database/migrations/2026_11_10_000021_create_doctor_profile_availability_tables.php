<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctor_profiles', function (Blueprint $table): void {
            $table->string('profile_photo_path', 500)->nullable();
            $table->string('workplace_address', 255)->nullable();
            $table->json('languages')->nullable();
            $table->string('timezone', 64)->default('Asia/Karachi');
            $table->boolean('is_public')->default(false)->index();
        });

        Schema::create('medical_specialties', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120)->unique();
            $table->string('name_key', 120)->unique();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('doctor_profile_specialty', function (Blueprint $table): void {
            $table->foreignId('doctor_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('medical_specialty_id')->constrained()->restrictOnDelete();
            $table->primary(['doctor_profile_id', 'medical_specialty_id']);
        });

        Schema::create('doctor_qualifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_profile_id')->constrained()->cascadeOnDelete();
            $table->string('degree', 180);
            $table->string('institution', 180);
            $table->unsignedSmallInteger('completion_year')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['doctor_profile_id', 'sort_order']);
        });

        Schema::create('doctor_consultation_options', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_profile_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->unsignedSmallInteger('duration_minutes')->default(30);
            $table->decimal('fee', 10, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['doctor_profile_id', 'type']);
        });

        Schema::create('doctor_availability_periods', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_profile_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->timestamps();
            $table->index(['doctor_profile_id', 'day_of_week']);
        });

        Schema::create('doctor_availability_breaks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('availability_period_id')->constrained('doctor_availability_periods')->cascadeOnDelete();
            $table->string('label', 100)->nullable();
            $table->time('starts_at');
            $table->time('ends_at');
            $table->timestamps();
        });

        Schema::create('doctor_unavailable_periods', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_profile_id')->constrained()->cascadeOnDelete();
            $table->string('type', 24)->default('leave');
            $table->string('label', 150)->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->boolean('all_day')->default(false);
            $table->string('timezone', 64);
            $table->timestamps();
            $table->index(['doctor_profile_id', 'starts_at', 'ends_at'], 'doctor_unavailable_range_idx');
        });

        // Migrate existing free-text specialties and qualifications without declaring them verified.
        DB::table('doctor_profiles')->select(['id', 'specialty', 'qualification', 'institution'])->orderBy('id')->get()
            ->each(function ($profile): void {
                $name = trim((string) $profile->specialty);
                if ($name !== '') {
                    $specialtyId = DB::table('medical_specialties')->where('name_key', mb_strtolower($name))->value('id');
                    if (! $specialtyId) {
                        $specialtyId = DB::table('medical_specialties')->insertGetId([
                            'name' => $name, 'name_key' => mb_strtolower($name), 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
                        ]);
                    }
                    DB::table('doctor_profile_specialty')->insertOrIgnore([
                        'doctor_profile_id' => $profile->id, 'medical_specialty_id' => $specialtyId,
                    ]);
                }
                if (trim((string) $profile->qualification) !== '' && trim((string) $profile->institution) !== '') {
                    DB::table('doctor_qualifications')->insertOrIgnore([
                        'doctor_profile_id' => $profile->id, 'degree' => $profile->qualification,
                        'institution' => $profile->institution, 'sort_order' => 0,
                        'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_unavailable_periods');
        Schema::dropIfExists('doctor_availability_breaks');
        Schema::dropIfExists('doctor_availability_periods');
        Schema::dropIfExists('doctor_consultation_options');
        Schema::dropIfExists('doctor_qualifications');
        Schema::dropIfExists('doctor_profile_specialty');
        Schema::dropIfExists('medical_specialties');
        Schema::table('doctor_profiles', function (Blueprint $table): void {
            $table->dropIndex(['is_public']);
            $table->dropColumn(['profile_photo_path', 'workplace_address', 'languages', 'timezone', 'is_public']);
        });
    }
};
