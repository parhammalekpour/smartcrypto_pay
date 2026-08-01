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
        Schema::table('merchant_customers', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('merchant_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->unique(['merchant_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('merchant_customers', function (Blueprint $table) {
            $table->dropUnique(['merchant_id', 'user_id']);
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
