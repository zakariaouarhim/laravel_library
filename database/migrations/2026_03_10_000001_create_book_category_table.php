<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('book_category', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('book_id');
            $table->unsignedBigInteger('category_id');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->unique(['book_id', 'category_id']);
            $table->index('category_id');
            $table->index('is_primary');

            $table->foreign('book_id')->references('id')->on('books')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
        });

        // Seed pivot from existing category_id data
        DB::statement('
            INSERT INTO book_category (book_id, category_id, is_primary, created_at, updated_at)
            SELECT id, category_id, 1, NOW(), NOW()
            FROM books
            WHERE category_id IS NOT NULL
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('book_category');
    }
};
