<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuoteLikesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quote_likes', function (Blueprint $table) {
             $table->id();
            $table->foreignId('quote_id')->constrained('quotes')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            // Prevent duplicate likes from same user
            $table->unique(['quote_id', 'user_id']);
            
            // Indexes for better performance
            $table->index('quote_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('quote_likes');
    }
}
