<?php
namespace App\Services;

use App\Models\InventoryLog;

class InventoryService
{
    public function logChange($bookId, $type, $quantityChange, $reference = null)
    {
        $book = Book::findOrFail($bookId);
        $quantityBefore = $book->Quantity;
        $quantityAfter = $quantityBefore + $quantityChange;

        InventoryLog::create([
            'book_id' => $bookId,
            'type' => $type,
            'quantity_change' => $quantityChange,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'reference_type' => 'shipment',
            'reference_id' => $reference
        ]);
    }
}