<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAuthorIdToBooksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('books', function (Blueprint $table) {
            // Add author_id column after the author column
            $table->bigInteger('author_id')->unsigned()->nullable()->after('author');
            
            // Add foreign key constraint
            $table->foreign('author_id')->references('id')->on('authors')->onDelete('set null');
            
            // Add index for better performance
            $table->index('author_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('books', function (Blueprint $table) {
            // Drop foreign key constraint first
            $table->dropForeign(['author_id']);
            
            // Drop the column
            $table->dropColumn('author_id');
        });
    }
}
