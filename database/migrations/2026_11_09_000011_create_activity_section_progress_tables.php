<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('activity_section_progress', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('child_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('activity_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('activity_section_id')->nullable()->constrained('activity_sections')->nullOnDelete();
            $table->string('section_title_snapshot');
            $table->string('status')->default('not_started');
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'child_id', 'activity_section_id'], 'section_progress_owner_unique');
            $table->index(['child_id', 'activity_id', 'status']);
        });

        Schema::create('activity_progress_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('child_id')->nullable()->constrained()->nullOnDelete();
            $table->string('child_name_snapshot');
            $table->foreignId('activity_id')->nullable()->constrained()->nullOnDelete();
            $table->string('activity_title_snapshot');
            $table->foreignId('activity_section_id')->nullable()->constrained('activity_sections')->nullOnDelete();
            $table->string('section_title_snapshot')->nullable();
            $table->string('status');
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'child_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_progress_events');
        Schema::dropIfExists('activity_section_progress');
    }
};
