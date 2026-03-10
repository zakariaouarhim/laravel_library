<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('book_authors')) return;

        Schema::create('book_authors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('book_id');
            $table->unsignedBigInteger('author_id');
            $table->enum('author_type', ['primary', 'co-author', 'editor', 'translator', 'illustrator'])->default('primary');
            $table->timestamps();

            $table->unique(['book_id', 'author_id', 'author_type'], 'unique_book_author');
            $table->index('author_type', 'idx_author_type');

            $table->foreign('book_id')->references('id')->on('books')->onDelete('cascade');
            $table->foreign('author_id')->references('id')->on('authors')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_authors');
    }
};
