<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('shipments')) return;

        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->string('shipment_reference', 100)->unique();
            $table->string('supplier_name')->nullable();
            $table->date('arrival_date');
            $table->enum('status', ['pending', 'processing', 'completed', 'cancelled'])->default('pending');
            $table->integer('total_books')->default(0);
            $table->integer('processed_books')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status', 'idx_shipment_status');
            $table->index('arrival_date', 'idx_arrival_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
