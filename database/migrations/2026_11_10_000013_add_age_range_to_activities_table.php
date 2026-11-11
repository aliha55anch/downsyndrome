<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table): void {
            $table->unsignedTinyInteger('min_age')->nullable()->after('age_group');
            $table->unsignedTinyInteger('max_age')->nullable()->after('min_age');
        });

        $ranges = [
            '0 to 6 months' => [0, 0],
            '6 to 12 months' => [0, 0],
            '1 to 2 years' => [1, 2],
            '2 to 3 years' => [2, 3],
            '3 to 5 years' => [3, 5],
            '5+ years' => [5, 18],
        ];

        foreach ($ranges as $group => [$min, $max]) {
            Schema::getConnection()->table('activities')->where('age_group', $group)->update([
                'min_age' => $min,
                'max_age' => $max,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table): void {
            $table->dropColumn(['min_age', 'max_age']);
        });
    }
};
