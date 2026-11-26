<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin control over the homepage carousel title link.
 *
 * Until now the destination was derived entirely in code
 * (HomeCarouselService::linkOf). These columns let an admin switch a title's
 * link off, or point it somewhere other than the derived target.
 *
 * link_enabled defaults to TRUE and link_type to 'auto' so every existing row
 * keeps behaving exactly as it does today - the nine titles that are currently
 * linked stay linked, and the rest stay plain text because 'auto' resolves to
 * null for them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('home_carousels', function (Blueprint $table) {
            $table->boolean('link_enabled')->default(true)->after('show_unavailable');

            // auto     = derive from the carousel's source (the previous behaviour)
            // category = link_category_id
            // path     = link_path, an internal path
            // Kept as a string rather than an enum: adding a kind later should not
            // need a column change on a table this small.
            $table->string('link_type', 16)->default('auto')->after('link_enabled');

            $table->unsignedBigInteger('link_category_id')->nullable()->after('link_type');
            $table->string('link_path')->nullable()->after('link_category_id');

            $table->foreign('link_category_id')->references('id')->on('categories')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('home_carousels', function (Blueprint $table) {
            $table->dropForeign(['link_category_id']);
            $table->dropColumn(['link_enabled', 'link_type', 'link_category_id', 'link_path']);
        });
    }
};
