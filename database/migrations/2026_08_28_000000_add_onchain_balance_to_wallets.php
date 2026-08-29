<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds onchain_balance column to wallets to store blockchain confirmed balance separately
     */
    public function up()
    {
        Schema::table('wallets', function (Blueprint $table) {
            // store as string to preserve decimal precision (same as balance)
            $table->string('onchain_balance')->nullable()->after('balance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn('onchain_balance');
        });
    }
};
