<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropCreditCardColumnsFromCheckoutDetails extends Migration
{
    public function up()
    {
        Schema::table('checkout_details', function (Blueprint $table) {
            $table->dropColumn(['card_number', 'expiry_date', 'cvv']);
        });
    }

    public function down()
    {
        Schema::table('checkout_details', function (Blueprint $table) {
            $table->string('card_number')->nullable();
            $table->string('expiry_date')->nullable();
            $table->string('cvv')->nullable();
        });
    }
}
