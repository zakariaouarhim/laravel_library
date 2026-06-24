<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            // Book ids explicitly excluded from the offer's eligible set (e.g. a few
            // books the admin doesn't want even though they match the price rule).
            $table->json('excluded_book_ids')->nullable()->after('max_price');
        });
    }

    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn('excluded_book_ids');
        });
    }
};
