<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            // Live price-range rule: every standard book whose price falls in
            // [min_price, max_price] is automatically eligible for the offer.
            // Either bound is optional; NULL means "unbounded on that side".
            $table->decimal('min_price', 10, 2)->nullable()->after('fixed_price');
            $table->decimal('max_price', 10, 2)->nullable()->after('min_price');
        });
    }

    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn(['min_price', 'max_price']);
        });
    }
};
