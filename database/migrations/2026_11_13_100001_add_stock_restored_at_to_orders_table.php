<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStockRestoredAtToOrdersTable extends Migration
{
    /**
     * Marks that this order's books were put back into stock (set when the
     * order becomes cancelled/failed/returned, cleared if it is reactivated).
     * Keeps restocking idempotent now that any status can move to any other.
     */
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('stock_restored_at')->nullable();
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('stock_restored_at');
        });
    }
}
