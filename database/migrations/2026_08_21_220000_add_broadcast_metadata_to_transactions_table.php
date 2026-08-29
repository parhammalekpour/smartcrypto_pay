<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'from_address')) {
                $table->string('from_address')->nullable()->after('sender_wallet_address');
            }

            if (!Schema::hasColumn('transactions', 'to_address')) {
                $table->string('to_address')->nullable()->after('receiver_wallet_address');
            }

            if (!Schema::hasColumn('transactions', 'block_hash')) {
                $table->string('block_hash')->nullable()->after('block_number');
            }

            if (!Schema::hasColumn('transactions', 'transaction_index')) {
                $table->unsignedBigInteger('transaction_index')->nullable()->after('block_hash');
            }

            if (!Schema::hasColumn('transactions', 'receipt_status')) {
                $table->string('receipt_status')->nullable()->after('transaction_index');
            }

            if (!Schema::hasColumn('transactions', 'gas_used')) {
                $table->string('gas_used')->nullable()->after('receipt_status');
            }

            if (!Schema::hasColumn('transactions', 'gas_price')) {
                $table->string('gas_price')->nullable()->after('gas_used');
            }

            if (!Schema::hasColumn('transactions', 'max_fee_per_gas')) {
                $table->string('max_fee_per_gas')->nullable()->after('gas_price');
            }

            if (!Schema::hasColumn('transactions', 'max_priority_fee_per_gas')) {
                $table->string('max_priority_fee_per_gas')->nullable()->after('max_fee_per_gas');
            }

            if (!Schema::hasColumn('transactions', 'confirmed_at')) {
                $table->timestamp('confirmed_at')->nullable()->after('max_priority_fee_per_gas');
            }

            if (!Schema::hasColumn('transactions', 'failed_at')) {
                $table->timestamp('failed_at')->nullable()->after('confirmed_at');
            }

            if (!Schema::hasColumn('transactions', 'replaced_by')) {
                $table->string('replaced_by')->nullable()->after('failed_at');
            }

            if (!Schema::hasColumn('transactions', 'replacement_of')) {
                $table->string('replacement_of')->nullable()->after('replaced_by');
            }

            if (!Schema::hasColumn('transactions', 'failure_reason')) {
                $table->text('failure_reason')->nullable()->after('replacement_of');
            }

            if (!Schema::hasColumn('transactions', 'broadcasted_at')) {
                $table->timestamp('broadcasted_at')->nullable()->after('failure_reason');
            }

            if (!Schema::hasColumn('transactions', 'last_checked_at')) {
                $table->timestamp('last_checked_at')->nullable()->after('broadcasted_at');
            }

            if (!Schema::hasIndex('transactions', ['tx_hash'])) {
                $table->index('tx_hash');
            }

            if (!Schema::hasIndex('transactions', ['replaced_by'])) {
                $table->index('replaced_by');
            }

            if (!Schema::hasIndex('transactions', ['replacement_of'])) {
                $table->index('replacement_of');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $columns = [
                'from_address',
                'to_address',
                'block_hash',
                'transaction_index',
                'receipt_status',
                'gas_used',
                'gas_price',
                'max_fee_per_gas',
                'max_priority_fee_per_gas',
                'confirmed_at',
                'failed_at',
                'replaced_by',
                'replacement_of',
                'failure_reason',
                'broadcasted_at',
                'last_checked_at',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('transactions', $column)) {
                    $table->dropColumn($column);
                }
            }

            if (Schema::hasIndex('transactions', ['tx_hash'])) {
                $table->dropIndex(['tx_hash']);
            }

            if (Schema::hasIndex('transactions', ['replaced_by'])) {
                $table->dropIndex(['replaced_by']);
            }

            if (Schema::hasIndex('transactions', ['replacement_of'])) {
                $table->dropIndex(['replacement_of']);
            }
        });
    }
};
