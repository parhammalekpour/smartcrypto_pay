<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->decimal('balance', 36, 18)->change();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('amount', 36, 18)->change();
        });

        Schema::table('payment_requests', function (Blueprint $table) {
            $table->decimal('amount', 36, 18)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->decimal('balance', 18, 8)->change();
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->decimal('amount', 18, 8)->change();
        });

        Schema::table('payment_requests', function (Blueprint $table) {
            $table->decimal('amount', 18, 8)->change();
        });
    }
};
