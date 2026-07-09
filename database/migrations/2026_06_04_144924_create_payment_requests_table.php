<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_requests', function (Blueprint $table) {

            $table->id();

            $table->foreignId('merchant_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('invoice_number');

            $table->decimal('amount', 18, 8);

            $table->enum('currency', [
                'BTC',
                'ETH',
                'USDT'
            ]);

            $table->string('token')->unique();

            $table->enum('status', [
                'pending',
                'paid',
                'expired',
                'rejected'
            ])->default('pending');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_requests');
    }
};