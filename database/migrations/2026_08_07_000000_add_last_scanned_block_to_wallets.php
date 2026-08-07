<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            if (!Schema::hasColumn('wallets', 'last_scanned_block')) {
                $table->unsignedBigInteger('last_scanned_block')->nullable()->after('balance');
            }
        });
    }

    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            if (Schema::hasColumn('wallets', 'last_scanned_block')) {
                $table->dropColumn('last_scanned_block');
            }
        });
    }
};
