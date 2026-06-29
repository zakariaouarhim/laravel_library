<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Staging table for the one-by-one admin review of scraped "reader" books
 * before they become real `books`. Seeded by `php artisan reader:stage`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reader_staging_books', function (Blueprint $table) {
            $table->id();
            $table->string('external_id')->unique();      // source UUID == image filename
            $table->string('name', 500);
            $table->string('author', 300)->nullable();
            $table->string('language', 20)->nullable();    // normalized: arabic|english|french
            $table->decimal('price', 10, 2)->default(40);
            $table->longText('description')->nullable();
            $table->string('local_image')->nullable();     // path under reader_DB/
            $table->boolean('image_exists')->default(false);
            $table->json('source_categories')->nullable(); // raw labels from the source site
            $table->unsignedBigInteger('suggested_category_id')->nullable();
            $table->integer('stock')->default(0);
            $table->enum('status', ['pending', 'imported', 'skipped'])->default('pending');
            $table->unsignedBigInteger('book_id')->nullable(); // set once imported
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reader_staging_books');
    }
};
