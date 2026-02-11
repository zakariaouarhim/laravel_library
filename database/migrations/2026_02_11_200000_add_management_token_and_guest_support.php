<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Add management_token to orders table
        Schema::table('orders', function (Blueprint $table) {
            $table->string('management_token', 64)->nullable()->unique()->after('tracking_number');
        });

        // Make user_id nullable + add guest_email to return_requests table
        Schema::table('return_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->string('guest_email')->nullable()->after('refund_amount');
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('management_token');
        });

        Schema::table('return_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->dropColumn('guest_email');
        });
    }
};
