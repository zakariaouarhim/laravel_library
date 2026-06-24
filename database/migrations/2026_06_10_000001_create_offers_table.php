<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('banner_image')->nullable();
            // Single promo type for now; enum leaves room for future types.
            $table->enum('type', ['pick_n_for_price'])->default('pick_n_for_price');
            $table->unsignedInteger('quantity')->default(1);     // the N (e.g. 10 books)
            $table->decimal('fixed_price', 10, 2)->default(0);   // the bundle price (e.g. 350)
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('meta_title', 70)->nullable();
            $table->string('meta_description', 160)->nullable();
            $table->timestamps();

            $table->index(['is_active', 'starts_at', 'ends_at']);
        });

        Schema::create('offer_book', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['offer_id', 'book_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_book');
        Schema::dropIfExists('offers');
    }
};
