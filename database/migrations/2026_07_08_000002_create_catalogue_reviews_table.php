<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-row review state for the catalogue import browser. The catalogue_reference
 * table is read-only and re-importable from a dump, so its review state lives
 * here instead. A row exists only once an item is acted on; no row = pending.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogue_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('catalogue_reference_id')->unique();
            $table->string('status', 20)->default('imported'); // imported | skipped
            $table->unsignedBigInteger('book_id')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogue_reviews');
    }
};
