<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRewriteSafetyColumnsToBooksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('books', function (Blueprint $table) {
            $table->text('original_description')->nullable()->after('description');
            $table->enum('rewrite_status', ['pending', 'rewritten', 'skipped', 'failed'])
                ->default('pending')->index()->after('original_description');
            $table->text('rewrite_error')->nullable()->after('rewrite_status');
            $table->timestamp('rewritten_at')->nullable()->after('rewrite_error');
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
            $table->dropColumn(['original_description', 'rewrite_status', 'rewrite_error', 'rewritten_at']);
        });
    }
}
