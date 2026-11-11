<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_lessons', function (Blueprint $table): void {
            $table->uuid('submission_key')->nullable()->after('activity_id');
            $table->unique(['activity_id', 'submission_key'], 'activity_lessons_submission_key_unique');
        });
    }

    public function down(): void
    {
        Schema::table('activity_lessons', function (Blueprint $table): void {
            $table->dropUnique('activity_lessons_submission_key_unique');
            $table->dropColumn('submission_key');
        });
    }
};
