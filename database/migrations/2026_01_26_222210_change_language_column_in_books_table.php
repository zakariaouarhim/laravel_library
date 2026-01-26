<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeLanguageColumnInBooksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        
            DB::statement("
            ALTER TABLE books 
            MODIFY Langue ENUM(
                'arabic',
                'english',
                'french',
                'spanish',
                'german'
            ) DEFAULT 'arabic'
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        
            DB::statement("
            ALTER TABLE books 
            MODIFY Langue VARCHAR(255)
        ");
    }
}
