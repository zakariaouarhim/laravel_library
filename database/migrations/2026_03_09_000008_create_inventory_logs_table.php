<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('inventory_logs')) return;

        Schema::create('inventory_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('book_id');
            $table->enum('type', ['stock_in', 'stock_out', 'adjustment', 'sale', 'return']);
            $table->integer('quantity_change')->comment('Positive for in, negative for out');
            $table->integer('quantity_before');
            $table->integer('quantity_after');
            $table->enum('reference_type', ['shipment', 'order', 'manual', 'return'])->nullable();
            $table->unsignedBigInteger('reference_id')->nullable()->comment('ID of shipment, order, etc.');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->comment('Who made the change');
            $table->timestamp('created_at')->nullable();

            $table->index('book_id', 'inventory_logs_book_id_foreign');
            $table->index('user_id', 'inventory_logs_user_id_foreign');
            $table->index(['type', 'reference_type', 'reference_id'], 'idx_type_reference');
            $table->index('created_at', 'idx_created_at');

            $table->foreign('book_id')->references('id')->on('books')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('user')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_logs');
    }
};
