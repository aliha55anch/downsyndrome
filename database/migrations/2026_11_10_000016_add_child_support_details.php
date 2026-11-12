<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('children', function (Blueprint $table): void {
            $table->string('pronouns', 80)->nullable();
            $table->text('communication_preferences')->nullable();
            $table->text('interests')->nullable();
            $table->text('strengths')->nullable();
            $table->text('support_preferences')->nullable();
            $table->text('learning_goals')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('children', function (Blueprint $table): void {
            $table->dropColumn([
                'pronouns',
                'communication_preferences',
                'interests',
                'strengths',
                'support_preferences',
                'learning_goals',
            ]);
        });
    }
};
