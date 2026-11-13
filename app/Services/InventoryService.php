<?php
namespace App\Services;

use App\Models\Book;
use App\Models\InventoryLog;

class InventoryService
{
    /**
     * Record a stock movement in inventory_logs. Does NOT change the stock
     * itself — call it BEFORE mutating Book::quantity so before/after are true.
     */
    public function logChange($bookId, $type, $quantityChange, $referenceId = null, string $referenceType = 'shipment')
    {
        $book = Book::findOrFail($bookId);
        $quantityBefore = $book->quantity;
        $quantityAfter = $quantityBefore + $quantityChange;

        InventoryLog::create([
            'book_id' => $bookId,
            'type' => $type,
            'quantity_change' => $quantityChange,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
        ]);
    }
}
