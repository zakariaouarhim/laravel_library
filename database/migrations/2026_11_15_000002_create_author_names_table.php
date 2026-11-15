<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Plain-string alternate spellings of an author's name that have no
        // author row of their own (e.g. "البير كامي" for "ألبير كامو").
        // Rows with their own author record use authors.canonical_author_id.
        Schema::create('author_names', function (Blueprint $table) {
            $table->id();
            $table->foreignId('author_id')->constrained('authors')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();

            $table->unique(['author_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('author_names');
    }
};
