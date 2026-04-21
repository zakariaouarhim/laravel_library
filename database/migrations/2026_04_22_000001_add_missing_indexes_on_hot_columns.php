<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $indexes = [
        ['orders', 'tracking_number', 'orders_tracking_number_index'],
        ['books',  'created_at',      'books_created_at_index'],
        ['books',  'price',           'books_price_index'],
        ['books',  'type',            'books_type_index'],
        ['books',  'quantity',        'books_quantity_index'],
    ];

    private function indexExists(string $table, string $name): bool
    {
        return count(DB::select(
            "SELECT 1 FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?",
            [$table, $name]
        )) > 0;
    }

    public function up(): void
    {
        foreach ($this->indexes as [$table, $column, $name]) {
            if ($this->indexExists($table, $name)) {
                continue;
            }
            Schema::table($table, function (Blueprint $t) use ($column, $name) {
                $t->index($column, $name);
            });
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as [$table, $column, $name]) {
            if (! $this->indexExists($table, $name)) {
                continue;
            }
            Schema::table($table, function (Blueprint $t) use ($name) {
                $t->dropIndex($name);
            });
        }
    }
};
