<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateShipmentItems extends Migration
{
    public function up()
    {
        Schema::table('shipment_items', function (Blueprint $table) {

            // Drop existing foreign key if it exists
            $table->dropForeign(['author_id']);
            $table->dropForeign(['publishing_house_id']);

            // Make sure columns are nullable
            $table->unsignedBigInteger('author_id')->nullable()->change();
            $table->unsignedBigInteger('publishing_house_id')->nullable()->change();

            // Re-add foreign keys
            $table->foreign('author_id')
                ->references('id')
                ->on('authors')
                ->nullOnDelete();

            $table->foreign('publishing_house_id')
                ->references('id')
                ->on('publishing_houses')
                ->nullOnDelete();

            // Remove old string column
            if (Schema::hasColumn('shipment_items', 'author')) {
                $table->dropColumn('author');
            }
        });
    }

    public function down()
    {
        Schema::table('shipment_items', function (Blueprint $table) {
            $table->dropForeign(['author_id']);
            $table->dropForeign(['publishing_house_id']);

            $table->string('author')->nullable();
        });
    }
}

