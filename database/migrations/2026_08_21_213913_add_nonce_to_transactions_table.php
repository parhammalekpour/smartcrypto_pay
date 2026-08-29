<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'nonce')) {
                // nullable to avoid destructive change; this will be used by NonceManager later
                $table->unsignedBigInteger('nonce')->nullable()->after('tx_hash');
                $table->index('nonce');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'nonce')) {
                $table->dropIndex(['nonce']);
                $table->dropColumn('nonce');
            }
        });
    }
};
