<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('children', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->date('date_of_birth');
            $table->timestamps();
            $table->index(['user_id', 'date_of_birth']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('children');
    }
};
