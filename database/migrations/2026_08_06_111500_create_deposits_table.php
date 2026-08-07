<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('wallets')->cascadeOnDelete();

            // Optional references to user or merchant
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('merchant_id')->nullable()->index();

            $table->string('currency', 16)->default('ETH');
            // Amount in native token units (ether) stored as decimal string
            $table->decimal('amount', 30, 18);

            $table->string('tx_hash');
            $table->unsignedBigInteger('block_number')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedInteger('confirmations')->default(0);

            // Composite unique index so same tx_hash can be used for multiple currencies
            $table->unique(['tx_hash', 'currency']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposits');
    }
};
