<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFollowsTable extends Migration
{
    public function up()
    {
        Schema::create('follows', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('followable_id');
            $table->string('followable_type', 50);  // 'author' or 'publisher'
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('user')->onDelete('cascade');
            $table->unique(['user_id', 'followable_id', 'followable_type'], 'follows_unique');
            $table->index(['followable_id', 'followable_type']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('follows');
    }
}
