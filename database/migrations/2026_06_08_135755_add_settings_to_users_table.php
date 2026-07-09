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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->boolean('show_balance')->default(true)->after('phone');
            $table->boolean('show_transactions')->default(true)->after('show_balance');
            $table->boolean('dark_mode')->default(false)->after('show_transactions');
            $table->boolean('notifications_enabled')->default(true)->after('dark_mode');
            $table->boolean('notifications_email')->default(true)->after('notifications_enabled');
            $table->boolean('notifications_2fa')->default(true)->after('notifications_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'show_balance',
                'show_transactions',
                'dark_mode',
                'notifications_enabled',
                'notifications_email',
                'notifications_2fa'
            ]);
        });
    }
};
