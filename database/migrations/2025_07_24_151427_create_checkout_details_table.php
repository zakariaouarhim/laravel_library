<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCheckoutDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('checkout_details', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone');
            $table->text('address');
            $table->string('city');
            $table->string('zip_code');
            $table->enum('payment_method', ['cod', 'credit_card'])->default('cod');
            $table->string('card_number')->nullable();
            $table->string('expiry_date')->nullable();
            $table->string('cvv')->nullable();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('shipping', 10, 2)->default(25.00);
            $table->decimal('discount', 10, 2)->default(0.00);
            $table->decimal('total', 10, 2);
            $table->enum('status', ['pending', 'processing', 'completed', 'cancelled'])->default('pending');
            $table->json('cart_items'); // Store cart items as JSON
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('checkout_details');
    }
}
