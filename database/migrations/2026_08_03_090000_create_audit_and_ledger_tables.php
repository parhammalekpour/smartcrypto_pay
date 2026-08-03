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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('actor_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('action', 128)->index();
            $table->string('resource_type')->nullable();
            $table->string('resource_id')->nullable();
            $table->longText('diff')->nullable(); // JSON payload
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
        });

        Schema::create('transactions_ledger', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('tx_id')->unique();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('type', 64)->index(); // deposit/withdraw/send/fee
            $table->decimal('amount', 36, 18);
            $table->string('currency', 16)->default('USD');
            $table->string('from')->nullable();
            $table->string('to')->nullable();
            $table->string('status', 64)->default('pending');
            $table->decimal('pre_balance', 36, 18)->nullable();
            $table->decimal('post_balance', 36, 18)->nullable();
            $table->json('metadata')->nullable();
            $table->string('prev_hash', 128)->nullable();
            $table->string('chain_hash', 128)->nullable()->index();
            $table->timestamps();

            $table->index(['user_id', 'tx_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions_ledger');
        Schema::dropIfExists('audit_logs');
    }
};
