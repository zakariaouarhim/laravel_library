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

        // Use DB transaction to ensure atomicity
        return DB::transaction(function () use ($book) {
            // Set status to processing with timestamp
            $book->update([
                'api_data_status' => 'processing',
                'api_last_updated' => now()
            ]);

            try {
                \Log::info('Starting book enrichment for book ID: ' . $book->id);
                \Log::info('Book title: ' . $book->title);

                $apiService = new APIService();
                $apiData = null;
                $searchMethod = null;

                // Try ISBN first if available
                $isbn = $this->extractISBN($book);

                if (!empty($isbn)) {
                    \Log::info('Trying ISBN search first: ' . $isbn);
                    try {
                        $apiData = $apiService->fetchBookDataByISBN($isbn);
                        if (isset($apiData['items']) && !empty($apiData['items'])) {
                            $searchMethod = 'ISBN';
                            \Log::info('Found data using ISBN: ' . $isbn);
                        } else {
                            \Log::info('No results from ISBN search, will try title search');
                            $apiData = null;
                        }
                    } catch (\Exception $e) {
                        \Log::warning('ISBN search failed: ' . $e->getMessage() . ', will try title search');
                        $apiData = null;
                    }
                }

                // Fallback to title+author search if ISBN failed or not available
                if (empty($apiData) || !isset($apiData['items']) || empty($apiData['items'])) {
                    $title = $book->title;
                    $author = $book->author;

                    if (empty($title)) {
                        throw new \Exception('Book has no ISBN and no title for enrichment. Book ID: ' . $book->id);
                    }

                    \Log::info('Trying title+author search: ' . $title . ' by ' . ($author ?? 'unknown'));
                    $apiData = $apiService->fetchBookDataByTitle($title, $author);

                    if (!isset($apiData['items']) || empty($apiData['items'])) {
                        throw new \Exception('No book data found for title: ' . $title);
                    }

                    $searchMethod = 'title+author';
                    \Log::info('Found data using title+author search');
                }

                // Map and validate the data
                $mappedData = $this->mapApiData($apiData, $book);

                if (empty($mappedData)) {
                    \Log::info('No fields need updating - current data is already good');
                }

                \Log::info('Mapped data fields: ' . implode(', ', array_keys($mappedData)));

                // Update book with enriched data
                if (!empty($mappedData)) {
                    $book->update($mappedData);
                }

                // Set final status
                $book->update([
                    'api_data_status' => 'enriched',
                    'api_last_updated' => now(),
                    'api_error_message' => null, // Clear any previous error
                    'api_search_method' => $searchMethod // Track which method was used
                ]);

                \Log::info('Book enrichment completed successfully for book ID: ' . $book->id . ' using ' . $searchMethod);

                return $book->fresh(); // Return fresh instance from database

            } catch (\Exception $e) {
                \Log::error('Error in enrichBookFromAPI for book ID ' . $book->id . ': ' . $e->getMessage());
                \Log::error('Stack trace: ' . $e->getTraceAsString());

                // Update status to failed with error message
                $book->update([
                    'api_data_status' => 'failed',
                    'api_last_updated' => now(),
                    'api_error_message' => substr($e->getMessage(), 0, 500)
                ]);

                throw $e; // Re-throw the exception to be handled by the controller
            }
        });
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
        
        // Handle image URL - only download if book doesn't have an image
        $currentImage = trim($book->image ?? '');
        if (empty($currentImage) || $currentImage === 'images/books/default-book.png') {
            $imageUrl = APIService::getBookCoverUrl($apiData);
            if ($imageUrl && filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                // Store original URL for reference
                $mappedData['original_image'] = $imageUrl;

                // Download and store the image locally
                try {
                    $localImagePath = $this->downloadAndStoreBookImage($imageUrl, $book->id);
                    if ($localImagePath) {
                        $mappedData['image'] = $localImagePath; // This is the field used for display
                        \Log::info('Downloaded and saved image for book ID ' . $book->id . ': ' . $localImagePath);
                    }
                } catch (\Exception $e) {
                    \Log::warning('Failed to download image for book ID ' . $book->id . ': ' . $e->getMessage());
                }
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
    
    /**
     * Download and store book cover image locally
     */
    protected function downloadAndStoreBookImage($imageUrl, $bookId)
    {
        try {
            // Get higher quality image by modifying Google Books URL
            $highQualityUrl = str_replace('zoom=1', 'zoom=2', $imageUrl);
            $highQualityUrl = str_replace('&edge=curl', '', $highQualityUrl);

            // Download the image
            $imageContent = @file_get_contents($highQualityUrl);
            if ($imageContent === false) {
                // Fallback to original URL
                $imageContent = @file_get_contents($imageUrl);
            }

            if ($imageContent === false) {
                throw new \Exception('Failed to download image from URL');
            }

            // Determine file extension from content type or URL
            $extension = 'jpg';
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($imageContent);

            if (strpos($mimeType, 'png') !== false) {
                $extension = 'png';
            } elseif (strpos($mimeType, 'webp') !== false) {
                $extension = 'webp';
            }

            // Generate unique filename
            $filename = 'api_' . $bookId . '_' . time() . '.' . $extension;
            $destinationPath = public_path('images/books');

            // Create directory if it doesn't exist
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            // Save the image
            $fullPath = $destinationPath . '/' . $filename;
            if (file_put_contents($fullPath, $imageContent) === false) {
                throw new \Exception('Failed to save image to disk');
            }

            // Return relative path for database storage
            return 'images/books/' . $filename;

        } catch (\Exception $e) {
            \Log::error('Error downloading book image: ' . $e->getMessage());
            return null;
        }
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