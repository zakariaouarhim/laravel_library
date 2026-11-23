<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateSchoolOrdersTable extends Migration
{
    public function up()
    {
        Schema::create('school_orders', function (Blueprint $table) {
            // The id IS the order number staff writes on the bag.
            $table->id();
            $table->string('customer_name', 150);
            // Stored digits-only (normalized in CounterController) so partial
            // search matches regardless of how staff typed spaces/dashes.
            $table->string('phone', 30)->index();
            $table->string('shelf', 50)->nullable(); // e.g. "A" or "1-50"
            $table->enum('status', ['pending', 'collected'])->default('pending')->index();
            $table->timestamp('collected_at')->nullable();
            $table->timestamps();
        });

        // First order number is 50 (staff-facing, written on the bag).
        DB::statement('ALTER TABLE school_orders AUTO_INCREMENT = 50;');
    }

    public function down()
    {
        Schema::dropIfExists('school_orders');
    }
}
