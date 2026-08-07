<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'sender_wallet_address')) {
                $table->string('sender_wallet_address')->nullable()->after('sender_id');
            }
        });

        Schema::table('deposits', function (Blueprint $table) {
            if (!Schema::hasColumn('deposits', 'sender_wallet_address')) {
                $table->string('sender_wallet_address')->nullable()->after('tx_hash');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'sender_wallet_address')) {
                $table->dropColumn('sender_wallet_address');
            }
        });

        Schema::table('deposits', function (Blueprint $table) {
            if (Schema::hasColumn('deposits', 'sender_wallet_address')) {
                $table->dropColumn('sender_wallet_address');
            }
        });
    }
};
