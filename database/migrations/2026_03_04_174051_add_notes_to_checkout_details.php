<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNotesToCheckoutDetails extends Migration
{
    public function up()
    {
        Schema::table('checkout_details', function (Blueprint $table) {
            $table->text('notes')->nullable()->after('city');
        });
    }

    public function down()
    {
        Schema::table('checkout_details', function (Blueprint $table) {
            $table->dropColumn('notes');
        });
    }
}
