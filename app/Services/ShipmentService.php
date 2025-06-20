<?php
namespace App\Services;

use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\Book;
use App\Services\APIService;
use App\Services\ImageService;

class ShipmentService
{
    public function processShipment(Shipment $shipment)
    {
        $shipment->update(['status' => 'processing']);

        foreach ($shipment->items as $item) {
            try {
                // Fetch data from API
                $apiData = (new APIService())->fetchBookDataByISBN($item->isbn);

                // Enrich book data
                $book = Book::updateOrCreate(
                    ['isbn' => $item->isbn],
                    $this->mapApiDataToBook($apiData, $item)
                );

                // Update shipment item status
                $item->update([
                    'book_id' => $book->id,
                    'processing_status' => 'completed'
                ]);

                // Update inventory
                $this->updateInventory($book, $item->quantity_received);

            } catch (\Exception $e) {
                $item->update([
                    'processing_status' => 'failed',
                    'error_message' => $e->getMessage()
                ]);
            }
        }

        $shipment->update([
            'processed_books' => $shipment->items()->where('processing_status', 'completed')->count(),
            'status' => 'completed'
        ]);
    }

    protected function mapApiDataToBook($apiData, ShipmentItem $item)
    {
        return [
            'title' => $apiData['items'][0]['volumeInfo']['title'] ?? $item->title,
            'author' => $apiData['items'][0]['volumeInfo']['authors'][0] ?? $item->author,
            'description' => $apiData['items'][0]['volumeInfo']['description'] ?? 'No description available',
            'image' => (new ImageService())->downloadAndStoreImage(
                APIService::getBookCoverUrl($apiData)
            ),
            'api_source' => $this->apiSource,
            'api_id' => $apiData['items'][0]['id'] ?? null,
            'api_last_updated' => now(),
            'api_data_status' => 'enriched'
        ];
    }

    protected function updateInventory(Book $book, $quantity)
    {
        $book->increment('Quantity', $quantity);
        (new InventoryService())->logChange(
            $book->id,
            'stock_in',
            $quantity,
            'Shipment received'
        );
    }
}