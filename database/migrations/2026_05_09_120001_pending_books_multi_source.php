<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop in-flight test rows — schema-incompatible with the new layout.
        DB::table('pending_books')->delete();

        Schema::table('pending_books', function (Blueprint $t) {
            $t->dropColumn(['fetched_data', 'api_source', 'staging_image_path']);
        });

        Schema::table('pending_books', function (Blueprint $t) {
            // {bnf: {...}, google_books: {...}, open_library: {...}}
            $t->json('api_results')->nullable()->after('status');
            // {bnf: 'images/books/staging/staging_xxx.webp', ...}
            $t->json('staging_images')->nullable()->after('api_results');
        });
    }

    public function down(): void
    {
        Schema::table('pending_books', function (Blueprint $t) {
            $t->dropColumn(['api_results', 'staging_images']);
        });

        Schema::table('pending_books', function (Blueprint $t) {
            $t->json('fetched_data')->nullable();
            $t->string('api_source', 32)->nullable();
            $t->string('staging_image_path')->nullable();
        });
    }
};
