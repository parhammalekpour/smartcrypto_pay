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
        if (!Schema::hasColumn('payment_requests', 'customer_id')) {
            Schema::table('payment_requests', function (Blueprint $table) {
                $table->foreignId('customer_id')->nullable()
                    ->after('recipient_user_id')
                    ->constrained('merchant_customers')
                    ->nullOnDelete();
            });
        } else {
            Schema::table('payment_requests', function (Blueprint $table) {
                $table->foreign('customer_id')
                    ->references('id')
                    ->on('merchant_customers')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn('customer_id');
        });
    }
};
