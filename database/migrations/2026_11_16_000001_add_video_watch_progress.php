<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_progress', function (Blueprint $table): void {
            $table->unsignedTinyInteger('video_progress_percent')->default(0)->after('progress_percent');
        });

        Schema::table('activity_section_progress', function (Blueprint $table): void {
            $table->unsignedTinyInteger('watched_percent')->default(0)->after('completion_method');
        });

        DB::table('activity_section_progress')->where('completion_method', 'video_ended')->update(['watched_percent' => 100]);
    }

    public function down(): void
    {
        Schema::table('activity_section_progress', function (Blueprint $table): void {
            $table->dropColumn('watched_percent');
        });

        Schema::table('activity_progress', function (Blueprint $table): void {
            $table->dropColumn('video_progress_percent');
        });
    }
};
