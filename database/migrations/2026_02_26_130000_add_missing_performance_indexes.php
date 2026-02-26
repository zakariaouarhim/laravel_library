<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddMissingPerformanceIndexes extends Migration
{
    public function up()
    {
        // Add indexes only if they don't already exist (some may exist via foreign keys)
        $this->addIndexIfMissing('reviews', 'book_id', 'reviews_book_id_index');
        $this->addIndexIfMissing('reviews', 'user_id', 'reviews_user_id_index');
        $this->addIndexIfMissing('cart_items', 'cart_id', 'cart_items_cart_id_index');
        $this->addIndexIfMissing('cart_items', 'book_id', 'cart_items_book_id_index');
        $this->addIndexIfMissing('orders', 'user_id', 'orders_user_id_index');
        $this->addIndexIfMissing('order_details', 'book_id', 'order_details_book_id_index');
    }

    public function down()
    {
        // Only drop indexes we explicitly added (not foreign key indexes)
        $this->dropIndexIfExists('reviews', 'reviews_book_id_index');
        $this->dropIndexIfExists('reviews', 'reviews_user_id_index');
        $this->dropIndexIfExists('cart_items', 'cart_items_cart_id_index');
        $this->dropIndexIfExists('cart_items', 'cart_items_book_id_index');
        $this->dropIndexIfExists('orders', 'orders_user_id_index');
        $this->dropIndexIfExists('order_details', 'order_details_book_id_index');
    }

    private function addIndexIfMissing(string $table, string $column, string $indexName)
    {
        $exists = collect(DB::select("SHOW INDEX FROM {$table}"))
            ->pluck('Key_name')
            ->contains(function ($name) use ($column) {
                return str_contains($name, $column);
            });

        if (!$exists) {
            Schema::table($table, function (Blueprint $t) use ($column, $indexName) {
                $t->index($column, $indexName);
            });
        }
    }

    private function dropIndexIfExists(string $table, string $indexName)
    {
        $exists = collect(DB::select("SHOW INDEX FROM {$table}"))
            ->pluck('Key_name')
            ->contains($indexName);

        if ($exists) {
            Schema::table($table, function (Blueprint $t) use ($indexName) {
                $t->dropIndex($indexName);
            });
        }
    }
}
