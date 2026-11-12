<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_section_progress', function (Blueprint $table): void {
            $table->string('completion_method', 32)->nullable()->after('status');
        });

        Schema::table('activity_progress_events', function (Blueprint $table): void {
            $table->string('completion_method', 32)->nullable()->after('status');
            $table->string('lesson_title_snapshot')->nullable()->after('section_title_snapshot');
        });

        Schema::create('activity_lesson_progress', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('child_id')->constrained()->cascadeOnDelete();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('activity_lesson_id')->constrained('activity_lessons')->cascadeOnDelete();
            $table->string('status')->default('not_started');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'child_id', 'activity_lesson_id'], 'lesson_progress_owner_unique');
            $table->index(['child_id', 'activity_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_lesson_progress');
        Schema::table('activity_progress_events', function (Blueprint $table): void {
            $table->dropColumn(['completion_method', 'lesson_title_snapshot']);
        });
        Schema::table('activity_section_progress', function (Blueprint $table): void {
            $table->dropColumn('completion_method');
        });
    }
};
