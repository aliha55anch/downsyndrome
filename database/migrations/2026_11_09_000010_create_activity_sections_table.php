<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_sections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('icon_path')->nullable();
            $table->string('color')->default('#DCEEF7');
            $table->string('content_type');
            $table->json('content')->nullable();
            $table->unsignedInteger('position')->default(1);
            $table->timestamps();
            $table->index(['activity_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_sections');
    }
};
