<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_progress', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('child_id')->constrained()->cascadeOnDelete();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('not_started');
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->unsignedSmallInteger('sessions_count')->default(0);
            $table->unsignedSmallInteger('minutes_practiced')->default(0);
            $table->string('engagement')->default('medium');
            $table->date('last_practiced_on')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'child_id', 'activity_id']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_progress');
    }
};
