<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSearchQueriesTable extends Migration
{
    public function up()
    {
        Schema::create('search_queries', function (Blueprint $table) {
            $table->id();
            // Raw text as the user typed it. Capped at 200 to match BookController::searchResults validation.
            $table->string('query', 200);
            // Tashkeel-stripped + lowercased form, for grouping "نجيب محفوظ" with "نَجِيب مَحفوظ".
            $table->string('normalized_query', 200)->index();
            $table->unsignedInteger('result_count')->default(0)->index();
            // 'page' = /search-results, 'autocomplete' = /search-books AJAX
            $table->string('source', 16)->default('page')->index();
            $table->foreignId('user_id')->nullable()->constrained('user')->nullOnDelete();
            $table->timestamps();

            $table->index(['source', 'created_at']);
            $table->index(['normalized_query', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('search_queries');
    }
}
