<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('kyc_verified')->default(false)->after('website_url');
            $table->json('kyc_documents')->nullable()->after('kyc_verified');
            $table->string('kyc_selfie')->nullable()->after('kyc_documents');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['kyc_verified', 'kyc_documents', 'kyc_selfie']);
        });
    }
};
