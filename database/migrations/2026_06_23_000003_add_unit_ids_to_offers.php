<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            // Series / bundles added to the offer as whole UNITS. The customer picks a
            // unit all-or-nothing and it counts toward N by its member-book count.
            $table->json('series_ids')->nullable()->after('excluded_book_ids');
            $table->json('bundle_ids')->nullable()->after('series_ids');
        });
    }

    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn(['series_ids', 'bundle_ids']);
        });
    }
};
