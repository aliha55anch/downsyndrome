<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('pending_email')->nullable()->unique()->after('email');
            $table->string('email_change_token_hash')->nullable()->after('pending_email');
            $table->timestamp('email_change_token_expires_at')->nullable()->after('email_change_token_hash');
            $table->string('deletion_token_hash')->nullable()->after('email_change_token_expires_at');
            $table->timestamp('deletion_token_expires_at')->nullable()->after('deletion_token_hash');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['pending_email']);
            $table->dropColumn([
                'pending_email',
                'email_change_token_hash',
                'email_change_token_expires_at',
                'deletion_token_hash',
                'deletion_token_expires_at',
            ]);
        });
    }
};
