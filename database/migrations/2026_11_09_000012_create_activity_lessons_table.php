<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('activity_lessons', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('position')->default(1);
            $table->timestamps();
            $table->index(['activity_id', 'position']);
        });

        Schema::table('activity_sections', function (Blueprint $table): void {
            $table->foreignId('lesson_id')->nullable()->after('activity_id')->constrained('activity_lessons')->cascadeOnDelete();
            $table->index(['lesson_id', 'position']);
        });

        Schema::table('activities', function (Blueprint $table): void {
            $table->string('icon_path')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table): void { $table->dropColumn('icon_path'); });
        Schema::table('activity_sections', function (Blueprint $table): void { $table->dropForeign(['lesson_id']); $table->dropColumn('lesson_id'); });
        Schema::dropIfExists('activity_lessons');
    }
};
