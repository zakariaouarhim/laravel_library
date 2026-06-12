<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_carousels', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            // Where the books come from.
            $table->enum('source_type', ['categories', 'author', 'manual'])->default('categories');
            $table->unsignedBigInteger('author_id')->nullable();
            $table->unsignedInteger('book_limit')->default(12);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('author_id')->references('id')->on('authors')->nullOnDelete();
            $table->index(['is_active', 'sort_order']);
        });

        // Categories source (one or many).
        Schema::create('home_carousel_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('home_carousel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->unique(['home_carousel_id', 'category_id']);
        });

        // Manual hand-picked source.
        Schema::create('home_carousel_book', function (Blueprint $table) {
            $table->id();
            $table->foreignId('home_carousel_id')->constrained()->cascadeOnDelete();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->unique(['home_carousel_id', 'book_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_carousel_book');
        Schema::dropIfExists('home_carousel_category');
        Schema::dropIfExists('home_carousels');
    }
};
