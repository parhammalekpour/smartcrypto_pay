<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('transactions', function (Blueprint $table) {
            // Change enum to include 'rejected'
            DB::statement("ALTER TABLE transactions MODIFY status ENUM('pending', 'completed', 'failed', 'rejected', 'cancelled') DEFAULT 'completed'");
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('transactions', function (Blueprint $table) {
            DB::statement("ALTER TABLE transactions MODIFY status ENUM('pending', 'completed', 'failed') DEFAULT 'completed'");
        });
    }
};
