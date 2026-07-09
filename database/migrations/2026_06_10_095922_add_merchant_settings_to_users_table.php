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
            $table->string('shop_name')->nullable()->after('phone');
            $table->text('shop_description')->nullable()->after('shop_name');
            $table->string('business_email')->nullable()->after('shop_description');
            $table->string('business_phone')->nullable()->after('business_email');
            $table->text('business_address')->nullable()->after('business_phone');
            $table->string('website_url')->nullable()->after('business_address');
            $table->string('business_license')->nullable()->after('website_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'shop_name',
                'shop_description',
                'business_email',
                'business_phone',
                'business_address',
                'website_url',
                'business_license',
            ]);
        });
    }
};
