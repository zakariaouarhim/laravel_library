<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateBooksAddApiFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('books', function (Blueprint $table) {
            $table->string('api_data_status')->default('pending')->after('ISBN');
            $table->string('api_source')->nullable()->after('api_data_status');
            $table->string('api_id')->nullable()->after('api_source');
            $table->timestamp('api_last_updated')->nullable()->after('api_id');
            $table->text('api_error_message')->nullable()->after('api_last_updated');
            $table->string('original_image')->nullable()->after('local_image_path');
            $table->string('local_image_path')->nullable()->after('original_image');
        });
    }

    public function down()
    {
        Schema::table('books', function (Blueprint $table) {
            $table->dropColumn([
                'api_data_status',
                'api_source', 
                'api_id',
                'api_last_updated',
                'api_error_message',
                'original_image',
                'local_image_path'
            ]);
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    
}
