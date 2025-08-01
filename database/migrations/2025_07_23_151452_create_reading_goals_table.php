<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReadingGoalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reading_goals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->integer('target')->default(12); // default goal
            $table->year('year')->default(date('Y')); // for tracking year-based goals
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('usermodel')->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reading_goals');
    }
}
