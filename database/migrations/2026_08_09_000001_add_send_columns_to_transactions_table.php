<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('wallet_id')->index();
            }

            if (!Schema::hasColumn('transactions', 'merchant_id')) {
                $table->unsignedBigInteger('merchant_id')->nullable()->after('user_id')->index();
            }

            if (!Schema::hasColumn('transactions', 'currency')) {
                $table->string('currency', 16)->nullable()->default('ETH')->after('merchant_id');
            }

            if (!Schema::hasColumn('transactions', 'sender_wallet_address')) {
                $table->string('sender_wallet_address')->nullable()->after('sender_id');
            }

            if (!Schema::hasColumn('transactions', 'receiver_wallet_address')) {
                $table->string('receiver_wallet_address')->nullable()->after('sender_wallet_address');
            }

            if (!Schema::hasColumn('transactions', 'tx_hash')) {
                $table->string('tx_hash')->nullable()->after('receiver_wallet_address');
            }

            if (!Schema::hasColumn('transactions', 'block_number')) {
                $table->unsignedBigInteger('block_number')->nullable()->after('tx_hash');
            }

            if (!Schema::hasColumn('transactions', 'confirmations')) {
                $table->unsignedInteger('confirmations')->default(0)->after('block_number');
            }

            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                // Keep compatibility with older status enum while allowing confirmed to be written.
                $table->string('status')->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'receiver_wallet_address')) {
                $table->dropColumn('receiver_wallet_address');
            }

            if (Schema::hasColumn('transactions', 'tx_hash')) {
                $table->dropColumn('tx_hash');
            }

            if (Schema::hasColumn('transactions', 'block_number')) {
                $table->dropColumn('block_number');
            }

            if (Schema::hasColumn('transactions', 'confirmations')) {
                $table->dropColumn('confirmations');
            }

            if (Schema::hasColumn('transactions', 'currency')) {
                $table->dropColumn('currency');
            }

            if (Schema::hasColumn('transactions', 'user_id')) {
                $table->dropColumn('user_id');
            }

            if (Schema::hasColumn('transactions', 'merchant_id')) {
                $table->dropColumn('merchant_id');
            }
        });
    }
};
