<?php
namespace App\Services;

use App\Models\Book;
use Illuminate\Support\Facades\DB;

class BookService
{
    public function enrichBookFromAPI(Book $book)
    {
        // Check if already enriched
        if ($book->api_data_status === 'enriched') {
            \Log::info('Book already enriched, skipping: ' . $book->id);
            return $book;
        }

        // Set status to processing to prevent concurrent processing
        $book->update(['api_data_status' => 'processing']);
    
        try {
            \Log::info('Starting book enrichment for book ID: ' . $book->id);
            \Log::info('Book title: ' . $book->title);
            
            // Get ISBN - handle both possible field names
            $isbn = $this->extractISBN($book);
            
            if (empty($isbn)) {
                throw new \Exception('Book has no valid ISBN for enrichment. Book ID: ' . $book->id);
            }
            
            \Log::info('Using ISBN for enrichment: ' . $isbn);
            
            // Fetch data from API
            $apiData = (new APIService())->fetchBookDataByISBN($isbn);
            
            if (!isset($apiData['items']) || empty($apiData['items'])) {
                throw new \Exception('No book data found for ISBN: ' . $isbn);
            }
            
            // Map and validate the data
            $mappedData = $this->mapApiData($apiData, $book);
            
            if (empty($mappedData)) {
                throw new \Exception('No useful data could be extracted from API response');
            }
            
            \Log::info('Mapped data fields: ' . implode(', ', array_keys($mappedData)));
            
            // Update book with transaction
            DB::transaction(function () use ($book, $mappedData) {
                // Use Scout's method to disable syncing during update
                Book::withoutSyncingToSearch(function () use ($book, $mappedData) {
                    $book->update($mappedData);
                    $book->api_data_status = 'enriched';
                    $book->api_last_updated = now();
                    $book->api_error_message = null; // Clear any previous error
                    $book->save();
                });
            });
            
            \Log::info('Book enrichment completed successfully for book ID: ' . $book->id);
            
        } catch (\Exception $e) {
            \Log::error('Error in enrichBookFromAPI for book ID ' . $book->id . ': ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            // Update status to failed with error message
            try {
                Book::withoutSyncingToSearch(function () use ($book, $e) {
                    $book->api_data_status = 'failed';
                    $book->api_last_updated = now();
                    $book->api_error_message = substr($e->getMessage(), 0, 500); // Limit error message length
                    $book->save();
                });
            } catch (\Exception $updateError) {
                \Log::error('Failed to update book status after enrichment error: ' . $updateError->getMessage());
            }
            
            throw $e;
        }
    
        return $book->fresh(); // Return fresh instance from database
    }
    
    protected function extractISBN(Book $book)
    {
        // Get ISBN from various possible fields
        $isbn = $book->ISBN ?? $book->isbn ?? null;
        \Log::info('Raw ISBN from database: ' . var_export($isbn, true));
        
        if (empty($isbn) || trim($isbn) === '') {
            return null;
        }
        
        // Clean the ISBN (remove any spaces, hyphens, but keep X for ISBN-10)
        $cleanIsbn = preg_replace('/[^0-9X]/', '', strtoupper($isbn));
        \Log::info('Cleaned ISBN: ' . $cleanIsbn);
        
        // Validate ISBN length (should be 10 or 13 digits)
        if (strlen($cleanIsbn) !== 10 && strlen($cleanIsbn) !== 13) {
            \Log::warning('Invalid ISBN length: ' . strlen($cleanIsbn) . ' for ISBN: ' . $cleanIsbn);
            return null;
        }
        
        return $cleanIsbn;
    }
    
    protected function mapApiData($apiData, Book $book)
    {
        // Add error handling for empty API response
        if (!isset($apiData['items']) || empty($apiData['items'])) {
            \Log::warning('API returned no items for book: ' . ($book->ISBN ?? $book->isbn ?? 'Unknown'));
            throw new \Exception('No data found in API response');
        }
        
        $bookInfo = $apiData['items'][0]['volumeInfo'] ?? [];
        
        if (empty($bookInfo)) {
            throw new \Exception('No volume info found in API response');
        }
        
        // Prepare mapped data with fallbacks
        $mappedData = [];
        
        // Only update fields if API has better/more complete data
        if (!empty($bookInfo['title'])) {
            $apiTitle = trim($bookInfo['title']);
            $currentTitle = trim($book->title ?? '');
            
            // Update if API title is longer or current title is empty/placeholder
            if (strlen($apiTitle) > strlen($currentTitle) || 
                empty($currentTitle) || 
                in_array(strtolower($currentTitle), ['untitled', 'no title', 'title'])) {
                $mappedData['title'] = $apiTitle;
            }
        }
        
        if (!empty($bookInfo['authors']) && is_array($bookInfo['authors'])) {
            $apiAuthors = implode(', ', array_filter($bookInfo['authors']));
            $currentAuthor = trim($book->author ?? '');
            
            if (strlen($apiAuthors) > strlen($currentAuthor) || 
                empty($currentAuthor) || 
                in_array(strtolower($currentAuthor), ['unknown', 'no author', 'author'])) {
                $mappedData['author'] = $apiAuthors;
            }
        }
        
        if (!empty($bookInfo['description'])) {
            $apiDescription = trim(strip_tags($bookInfo['description']));
            $currentDescription = trim($book->description ?? '');
            
            // Update if API description is significantly longer or current is empty
            if (strlen($apiDescription) > strlen($currentDescription) * 1.5 || 
                strlen($currentDescription) < 50) {
                $mappedData['description'] = $apiDescription;
            }
        }
        
        if (!empty($bookInfo['pageCount']) && is_numeric($bookInfo['pageCount'])) {
            $apiPageCount = (int)$bookInfo['pageCount'];
            $currentPageCount = (int)($book->Page_Num ?? 0);
            
            // Update if current page count is 0 or API has a reasonable page count
            if ($currentPageCount == 0 || ($apiPageCount > 0 && $apiPageCount < 10000)) {
                $mappedData['Page_Num'] = $apiPageCount;
            }
        }
        
        if (!empty($bookInfo['publisher'])) {
            $apiPublisher = trim($bookInfo['publisher']);
            $currentPublisher = trim($book->Publishing_House ?? '');
            
            if (strlen($apiPublisher) > strlen($currentPublisher) || empty($currentPublisher)) {
                $mappedData['Publishing_House'] = $apiPublisher;
            }
        }
        
        if (!empty($bookInfo['language'])) {
            $mappedLanguage = $this->mapLanguageCode($bookInfo['language']);
            $currentLanguage = trim($book->Langue ?? '');
            
            if (empty($currentLanguage) || $currentLanguage === 'Unknown') {
                $mappedData['Langue'] = $mappedLanguage;
            }
        }
        
        // Handle image URL
        $imageUrl = APIService::getBookCoverUrl($apiData);
        if ($imageUrl && filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            $mappedData['original_image'] = $imageUrl;
            
            // Optionally download and store locally
            try {
                if (class_exists('App\Services\ImageService')) {
                    $localImagePath = (new ImageService())->downloadAndStoreImage($imageUrl);
                    if ($localImagePath) {
                        $mappedData['local_image_path'] = $localImagePath;
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('Failed to download image for book ID ' . $book->id . ': ' . $e->getMessage());
            }
        }
        
        // Log what data will be updated
        if (!empty($mappedData)) {
            \Log::info('Will update fields: ' . implode(', ', array_keys($mappedData)));
        } else {
            \Log::info('No fields need updating - current data is already good');
        }
        
        return $mappedData;
    }
    
    protected function mapLanguageCode($langCode)
    {
        $languageMap = [
            'en' => 'English',
            'fr' => 'French',
            'es' => 'Spanish',
            'de' => 'German',
            'ar' => 'Arabic',
            'it' => 'Italian',
            'pt' => 'Portuguese',
            'ru' => 'Russian',
            'ja' => 'Japanese',
            'zh' => 'Chinese',
            'ko' => 'Korean',
            'nl' => 'Dutch',
            'sv' => 'Swedish',
            'da' => 'Danish',
            'no' => 'Norwegian',
            'fi' => 'Finnish',
            'pl' => 'Polish',
            'tr' => 'Turkish',
            'he' => 'Hebrew',
            'hi' => 'Hindi'
        ];
        
        return $languageMap[strtolower($langCode)] ?? ucfirst($langCode);
    }
}