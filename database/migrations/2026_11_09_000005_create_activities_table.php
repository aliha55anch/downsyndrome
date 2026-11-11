<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('short_description');
            $table->text('detailed_description')->nullable();
            $table->string('age_group')->index();
            $table->string('development_area')->index();
            $table->string('difficulty')->nullable();
            $table->unsignedSmallInteger('estimated_minutes')->nullable();
            $table->text('materials')->nullable();
            $table->text('learning_objective')->nullable();
            $table->longText('instructions')->nullable();
            $table->text('parent_tips')->nullable();
            $table->text('disclaimer')->nullable();
            $table->string('content_source')->nullable();
            $table->string('reviewer_name')->nullable();
            $table->string('status')->default('draft')->index();
            $table->string('featured_image_path')->nullable();
            $table->string('pptx_path')->nullable();
            $table->string('pdf_path')->nullable();
            $table->string('youtube_url')->nullable();
            $table->json('content_blocks')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
