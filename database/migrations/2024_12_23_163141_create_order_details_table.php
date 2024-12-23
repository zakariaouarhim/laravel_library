<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrderDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('order_details', function (Blueprint $table) {
        $table->bigIncrements('id');
        $table->unsignedBigInteger('order_id');
        $table->unsignedBigInteger('book_id');
        $table->integer('quantity');
        $table->decimal('price', 8, 2);
        $table->timestamps();

        // Foreign Key Constraints
        $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        $table->foreign('book_id')->references('id')->on('books')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_details');
    }
}
