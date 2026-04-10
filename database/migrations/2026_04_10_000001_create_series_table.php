<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('series', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('author_id')->nullable();
            $table->unsignedSmallInteger('total_volumes')->nullable();
            $table->string('cover_image')->nullable();
            $table->boolean('is_complete')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('author_id')->references('id')->on('authors')->onDelete('set null');
            $table->index('name', 'idx_series_name');
            $table->index('author_id', 'idx_series_author');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('series');
    }
};
