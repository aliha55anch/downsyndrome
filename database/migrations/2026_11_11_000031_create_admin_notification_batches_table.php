<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_notification_batches', function (Blueprint $table): void {
            $table->id();
            $table->uuid('send_key')->unique();
            $table->foreignId('admin_user_id')->constrained('users')->restrictOnDelete();
            $table->string('audience', 24);
            $table->unsignedInteger('recipient_count');
            $table->string('title', 160);
            $table->text('message');
            $table->string('internal_url', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_notification_batches');
    }
};
