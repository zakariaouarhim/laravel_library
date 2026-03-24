<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->index('status', 'idx_books_status');
            $table->index('api_data_status', 'idx_books_api_data_status');
        });

        Schema::table('wishlists', function (Blueprint $table) {
            $table->index('book_id', 'idx_wishlists_book_id');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->index('created_at', 'idx_reviews_created_at');
        });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropIndex('idx_books_status');
            $table->dropIndex('idx_books_api_data_status');
        });

        Schema::table('wishlists', function (Blueprint $table) {
            $table->dropIndex('idx_wishlists_book_id');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex('idx_reviews_created_at');
        });
    }
};
