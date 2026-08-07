<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Note: Changing column types requires doctrine/dbal dependency.
        Schema::table('wallets', function (Blueprint $table) {
            $table->string('wallet_address', 255)->change();
        });
    }

    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            // Revert to 42-char length (standard ETH address length) if necessary
            $table->string('wallet_address', 42)->change();
        });
    }
};
