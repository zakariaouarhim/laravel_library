<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Richer per-book review fields: ISBN, page count, publisher, multi-category
 * (ids + primary), an optional admin-replaced cover, and a flag for whether the
 * description was AI-rewritten (so the created Book is marked rewrite_status=rewritten).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reader_staging_books', function (Blueprint $table) {
            $table->string('isbn', 20)->nullable()->after('author');
            $table->integer('page_num')->nullable()->after('isbn');
            $table->string('publisher', 300)->nullable()->after('page_num');
            $table->json('category_ids')->nullable()->after('suggested_category_id');
            $table->unsignedBigInteger('primary_category_id')->nullable()->after('category_ids');
            $table->string('custom_image')->nullable()->after('image_exists'); // public images/books/…webp when replaced
            $table->boolean('description_rewritten')->default(false)->after('custom_image');
            $table->longText('original_description')->nullable()->after('description_rewritten'); // pre-rewrite text
        });
    }

    public function down(): void
    {
        Schema::table('reader_staging_books', function (Blueprint $table) {
            $table->dropColumn([
                'isbn', 'page_num', 'publisher', 'category_ids',
                'primary_category_id', 'custom_image', 'description_rewritten',
            ]);
        });
    }
};
