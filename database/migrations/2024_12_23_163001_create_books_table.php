<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBooksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('books', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('title');
            $table->string('author');
            $table->decimal('price', 8, 2);
            $table->unsignedBigInteger('category_id');
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->integer('Page_Num');
            $table->string('Langue');
            $table->string('Publishing_House');
            $table->string('ISBN');
            $table->integer('Quantity')->default(0); // Fixed: removed duplicate ISBN column
            $table->timestamps();
            


        // Foreign Key Constraint
        $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('books');
    }
}
