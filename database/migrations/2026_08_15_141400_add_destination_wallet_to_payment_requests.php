<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds a nullable destination_wallet_id to payment_requests so merchants can
     * select which of their wallets should receive funds for a given payment request.
     *
     * Existing payment_requests are left unchanged (column is nullable).
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('destination_wallet_id')->nullable()->after('token');
            $table->foreign('destination_wallet_id')->references('id')->on('wallets')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropForeign(['destination_wallet_id']);
            $table->dropColumn('destination_wallet_id');
        });
    }
};
