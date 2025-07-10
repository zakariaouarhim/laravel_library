<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddApiColumnsToBooksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('books', function (Blueprint $table) {
            if (!Schema::hasColumn('books', 'api_data_status')) {
                $table->string('api_data_status')->default('pending')->after('ISBN');
            }
            if (!Schema::hasColumn('books', 'api_source')) {
                $table->string('api_source')->nullable()->after('api_data_status');
            }
            if (!Schema::hasColumn('books', 'api_id')) {
                $table->string('api_id')->nullable()->after('api_source');
            }
            if (!Schema::hasColumn('books', 'api_last_updated')) {
                $table->timestamp('api_last_updated')->nullable()->after('api_id');
            }
            if (!Schema::hasColumn('books', 'api_error_message')) {
                $table->text('api_error_message')->nullable()->after('api_last_updated');
            }
            if (!Schema::hasColumn('books', 'original_image')) {
                $table->string('original_image')->nullable()->after('api_error_message');
            }
            if (!Schema::hasColumn('books', 'local_image_path')) {
                $table->string('local_image_path')->nullable()->after('original_image');
            }
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
            $columns = ['api_data_status', 'api_source', 'api_id', 'api_last_updated', 'api_error_message', 'original_image', 'local_image_path'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('books', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
