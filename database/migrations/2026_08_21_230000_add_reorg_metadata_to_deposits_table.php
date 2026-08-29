<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            if (!Schema::hasColumn('deposits', 'block_hash')) {
                $table->string('block_hash')->nullable()->after('tx_hash');
            }

            if (!Schema::hasColumn('deposits', 'transaction_index')) {
                $table->unsignedBigInteger('transaction_index')->nullable()->after('block_hash');
            }

            if (!Schema::hasColumn('deposits', 'receipt_status')) {
                $table->string('receipt_status')->nullable()->after('transaction_index');
            }

            if (!Schema::hasColumn('deposits', 'confirmed_at')) {
                $table->timestamp('confirmed_at')->nullable()->after('confirmations');
            }

            if (!Schema::hasColumn('deposits', 'reorged_at')) {
                $table->timestamp('reorged_at')->nullable()->after('confirmed_at');
            }

            if (!Schema::hasColumn('deposits', 'reorg_reason')) {
                $table->text('reorg_reason')->nullable()->after('reorged_at');
            }

            if (!Schema::hasColumn('deposits', 'canonical_checked_at')) {
                $table->timestamp('canonical_checked_at')->nullable()->after('reorg_reason');
            }
        });
    }

    public function down(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            $columns = [
                'block_hash',
                'transaction_index',
                'receipt_status',
                'confirmed_at',
                'reorged_at',
                'reorg_reason',
                'canonical_checked_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('deposits', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
