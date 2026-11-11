<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_tracking_preferences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('child_id')->constrained()->cascadeOnDelete();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_tracking')->default(true);
            $table->timestamps();
            $table->unique(['user_id', 'child_id', 'activity_id'], 'tracking_preference_owner_unique');
            $table->index(['child_id', 'is_tracking']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_tracking_preferences');
    }
};
