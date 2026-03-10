<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('shipment_items')) return;

        Schema::create('shipment_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shipment_id');
            $table->unsignedBigInteger('book_id')->nullable()->comment('NULL if book not yet created');
            $table->string('isbn', 17);
            $table->string('title');
            $table->unsignedBigInteger('author_id')->nullable()->comment('Reference to authors table');
            $table->integer('quantity_received');
            $table->decimal('cost_price', 8, 2)->nullable()->comment('What you paid for the book');
            $table->unsignedBigInteger('publishing_house_id')->nullable()->comment('Reference to publishing_houses table');
            $table->decimal('selling_price', 8, 2)->nullable()->comment('Your set selling price');
            $table->enum('processing_status', ['pending', 'api_enriching', 'completed', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->index('isbn', 'idx_isbn');
            $table->index('processing_status', 'idx_processing_status');
            $table->index('author_id', 'idx_author_id');
            $table->index('publishing_house_id', 'idx_publishing_house_id');

            $table->foreign('shipment_id')->references('id')->on('shipments')->onDelete('cascade');
            $table->foreign('book_id')->references('id')->on('books')->onDelete('set null');
            $table->foreign('author_id')->references('id')->on('authors')->onDelete('set null');
            $table->foreign('publishing_house_id')->references('id')->on('publishing_houses')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_items');
    }
};
