<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table): void {
            $table->unsignedBigInteger('gateway_fee_minor')->nullable()->after('amount_minor');
            $table->unsignedBigInteger('gateway_net_minor')->nullable()->after('gateway_fee_minor');
        });
    }
    public function down(): void
    {
        Schema::table('payment_transactions', function (Blueprint $table): void {
            $table->dropColumn(['gateway_fee_minor', 'gateway_net_minor']);
        });
    }
};
