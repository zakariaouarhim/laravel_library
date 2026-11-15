<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('authors', function (Blueprint $table) {
            // Alias rows point at their canonical author row (Goodreads-style
            // "alternate name"). NULL = this row IS the canonical author.
            $table->foreignId('canonical_author_id')
                ->nullable()
                ->after('slug')
                ->constrained('authors')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('authors', function (Blueprint $table) {
            $table->dropConstrainedForeignId('canonical_author_id');
        });
    }
};
