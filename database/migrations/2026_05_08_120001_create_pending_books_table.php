<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_books', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('author_name');
            $table->string('language', 16)->default('french');

            $table->enum('status', [
                'enriched',
                'failed',
                'duplicate',
                'approved',
                'discarded',
            ])->default('enriched');

            $table->json('fetched_data')->nullable();
            $table->string('api_source', 32)->nullable();
            $table->string('staging_image_path')->nullable();
            $table->text('error_message')->nullable();

            $table->unsignedBigInteger('existing_book_id')->nullable();
            $table->unsignedBigInteger('approved_book_id')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('language');

            $table->foreign('existing_book_id')->references('id')->on('books')->nullOnDelete();
            $table->foreign('approved_book_id')->references('id')->on('books')->nullOnDelete();
            $table->foreign('reviewed_by')->references('id')->on('user')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_books');
    }
};
