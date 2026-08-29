<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_nonces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('wallets')->cascadeOnDelete();
            $table->string('address')->nullable()->index();
            // next_nonce is the next available nonce to assign (nullable until first reconcile)
            $table->unsignedBigInteger('next_nonce')->nullable()->index();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();

            $table->unique('wallet_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_nonces');
    }
};
