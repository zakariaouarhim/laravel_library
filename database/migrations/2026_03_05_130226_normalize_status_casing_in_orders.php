<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class NormalizeStatusCasingInOrders extends Migration
{
    public function up()
    {
        // Step 1: Convert to VARCHAR to allow free-form updates
        DB::statement("ALTER TABLE orders MODIFY COLUMN status VARCHAR(20) NOT NULL DEFAULT 'pending'");

        // Step 2: Normalize to lowercase
        DB::statement("UPDATE orders SET status = 'failed' WHERE BINARY status = 'Failed'");
        DB::statement("UPDATE orders SET status = 'refunded' WHERE BINARY status = 'Refunded'");

        // Step 3: Convert back to ENUM with all-lowercase values
        DB::statement("ALTER TABLE orders MODIFY COLUMN status
            ENUM('pending','processing','shipped','delivered','cancelled','failed','refunded','returned')
            NOT NULL DEFAULT 'pending'");
    }

    public function down()
    {
        DB::statement("ALTER TABLE orders MODIFY COLUMN status VARCHAR(20) NOT NULL DEFAULT 'pending'");
        DB::statement("UPDATE orders SET status = 'Failed' WHERE status = 'failed'");
        DB::statement("UPDATE orders SET status = 'Refunded' WHERE status = 'refunded'");
        DB::statement("ALTER TABLE orders MODIFY COLUMN status
            ENUM('pending','processing','shipped','delivered','cancelled','Failed','Refunded','returned')
            NOT NULL DEFAULT 'pending'");
    }
}
