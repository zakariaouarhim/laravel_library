<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->renameColumn('Page_Num', 'page_num');
            $table->renameColumn('Langue', 'language');
            $table->renameColumn('Quantity', 'quantity');
            $table->renameColumn('ISBN', 'isbn');
        });
    }

    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->renameColumn('page_num', 'Page_Num');
            $table->renameColumn('language', 'Langue');
            $table->renameColumn('quantity', 'Quantity');
            $table->renameColumn('isbn', 'ISBN');
        });
    }
};
