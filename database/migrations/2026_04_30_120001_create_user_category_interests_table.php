<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_category_interests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('category_id');
            $table->decimal('score', 10, 2)->default(0);
            $table->timestamp('last_interaction_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'category_id']);
            $table->index(['user_id', 'score']);

            $table->foreign('user_id')->references('id')->on('user')->cascadeOnDelete();
            $table->foreign('category_id')->references('id')->on('categories')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_category_interests');
    }
};
