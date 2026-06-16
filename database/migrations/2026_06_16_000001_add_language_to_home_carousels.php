<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('home_carousels', function (Blueprint $table) {
            // Optional language filter for custom carousels (author/categories sources).
            // NULL = all languages. Matches Book.language values (arabic|english|french).
            $table->string('language')->nullable()->after('source_type');
        });
    }

    public function down(): void
    {
        Schema::table('home_carousels', function (Blueprint $table) {
            $table->dropColumn('language');
        });
    }
};
