<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * All foreign keys to add: [table, column, referenced_table, onDelete]
     */
    private array $foreignKeys = [
        ['orders', 'user_id', 'user', 'cascade'],
        ['order_details', 'order_id', 'orders', 'cascade'],
        ['order_details', 'book_id', 'books', 'restrict'],
        ['checkout_details', 'order_id', 'orders', 'cascade'],
        ['cart', 'user_id', 'user', 'cascade'],
        ['cart_items', 'cart_id', 'cart', 'cascade'],
        ['cart_items', 'book_id', 'books', 'cascade'],
        ['categories', 'parent_id', 'categories', 'set null'],
        ['wishlists', 'user_id', 'user', 'cascade'],
        ['wishlists', 'book_id', 'books', 'cascade'],
        ['hidden_recommendations', 'user_id', 'user', 'cascade'],
        ['hidden_recommendations', 'book_id', 'books', 'cascade'],
        ['return_requests', 'order_id', 'orders', 'cascade'],
        ['return_requests', 'user_id', 'user', 'set null'],
        ['quotes', 'book_id', 'books', 'cascade'],
        ['quotes', 'user_id', 'user', 'cascade'],
        ['review_likes', 'review_id', 'reviews', 'cascade'],
        ['review_likes', 'user_id', 'user', 'cascade'],
        ['stock_notifications', 'book_id', 'books', 'cascade'],
        ['stock_notifications', 'user_id', 'user', 'cascade'],
        ['user_notifications', 'user_id', 'user', 'cascade'],
        ['follows', 'user_id', 'user', 'cascade'],
        ['order_status_history', 'order_id', 'orders', 'cascade'],
    ];

    private function hasForeignKey(string $table, string $constraintName): bool
    {
        return count(DB::select(
            "SELECT 1 FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY' AND CONSTRAINT_NAME = ?",
            [$table, $constraintName]
        )) > 0;
    }

    public function up(): void
    {
        foreach ($this->foreignKeys as [$table, $column, $refTable, $onDelete]) {
            $constraintName = "{$table}_{$column}_foreign";

            if ($this->hasForeignKey($table, $constraintName)) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($column, $refTable, $onDelete) {
                $t->foreign($column)->references('id')->on($refTable)->onDelete($onDelete);
            });
        }
    }

    public function down(): void
    {
        foreach ($this->foreignKeys as [$table, $column, $refTable, $onDelete]) {
            $constraintName = "{$table}_{$column}_foreign";

            if (! $this->hasForeignKey($table, $constraintName)) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) use ($constraintName) {
                $t->dropForeign($constraintName);
            });
        }
    }
};
