<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {

            $table->id();

            $table->foreignId('wallet_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->enum('type', [
                'deposit',
                'withdraw',
                'transfer',
                'payment'
            ]);

            $table->decimal('amount', 18, 8);

            $table->enum('status', [
                'pending',
                'completed',
                'failed'
            ])->default('completed');

            $table->string('reference')
                ->nullable();

            $table->text('description')
                ->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};