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

                // NOTE: no stock change here — quantities were already added
                // when the shipment was created (ShipmentController@store);
                // adding again here double-counted the stock.

            } catch (\Throwable $e) {
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
        $coverUrl = APIService::getBookCoverUrl($apiData);

        return [
            'title' => $apiData['items'][0]['volumeInfo']['title'] ?? $item->title,
            'description' => $apiData['items'][0]['volumeInfo']['description'] ?? 'No description available',
            'image' => $coverUrl
                ? (new ImageService())->downloadFromUrl($coverUrl, 'images/books', 'shipment_' . ($item->isbn ?: $item->id))
                : null,
            'api_source' => 'google_books',
            'api_id' => $apiData['items'][0]['id'] ?? null,
            'api_last_updated' => now(),
            'api_data_status' => 'enriched'
        ];
    }
}