<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            // Owner fields to support both User and Merchant
            $table->string('owner_type')->nullable()->after('user_id')->comment('User or Merchant');
            $table->unsignedBigInteger('owner_id')->nullable()->after('owner_type');

            // Encrypted private key — do NOT store raw private key
            $table->text('encrypted_private_key')->nullable()->after('wallet_address');

            // Network (e.g., ethereum, bitcoin). Keep flexible for future currencies
            $table->string('network')->default('ethereum')->after('encrypted_private_key');

            // Ensure quick lookup by owner
            $table->index(['owner_type', 'owner_id']);
        });
    }

    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropIndex(['owner_type', 'owner_id']);
            $table->dropColumn(['owner_type', 'owner_id', 'encrypted_private_key', 'network']);
        });
    }
};
