<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->unsignedBigInteger('series_id')->nullable()->after('publishing_house_id');
            $table->unsignedSmallInteger('volume_number')->nullable()->after('series_id');

            $table->foreign('series_id')->references('id')->on('series')->onDelete('set null');
            $table->index(['series_id', 'volume_number'], 'idx_series_volume');
        });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropForeign(['series_id']);
            $table->dropIndex('idx_series_volume');
            $table->dropColumn(['series_id', 'volume_number']);
        });
    }
};
