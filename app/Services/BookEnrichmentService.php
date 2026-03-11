<?php

namespace App\Services;

use App\Models\Book;
use Illuminate\Support\Facades\DB;

class BookEnrichmentService
{
    public function __construct(
        private APIService $apiService,
        private ImageService $imageService,
    ) {}

    /**
     * Enrich a single book with status guards and timeout detection.
     * Called from the controller's enrichBook() action.
     *
     * @return array{success: bool, message: string, book?: Book, status?: int}
     */
    public function enrichBook(Book $book): array
    {
        // Check if book is already being processed
        if ($book->api_data_status === 'processing') {
            $processingTimeout = 5; // minutes
            if ($book->api_last_updated && $book->api_last_updated->diffInMinutes(now()) > $processingTimeout) {
                \Log::warning("Book ID {$book->id} was stuck in processing state for over {$processingTimeout} minutes. Resetting status.");
                $book->update(['api_data_status' => 'pending']);
            } else {
                return [
                    'success' => false,
                    'message' => 'Book enrichment is already in progress. Please wait and try again later.',
                    'status'  => 409,
                ];
            }
        }

        // Check if book is already enriched
        if ($book->api_data_status === 'enriched') {
            return [
                'success' => true,
                'message' => 'Book is already enriched.',
                'book'    => $book->fresh(),
            ];
        }

        \Log::info('Starting enrichment for book ID: ' . $book->id);

        DB::beginTransaction();
        try {
            $enrichedBook = $this->enrichBookFromAPI($book);
            DB::commit();

            \Log::info('Enrichment completed successfully for book ID: ' . $book->id);

            return [
                'success' => true,
                'message' => 'Book enriched successfully!',
                'book'    => $enrichedBook,
            ];
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollback();
            \Log::error('Database error during enrichment for book ID ' . $book->id . ': ' . $e->getMessage());

            try {
                $book->update(['api_data_status' => 'pending']);
            } catch (\Exception $resetError) {
                \Log::error('Failed to reset book status: ' . $resetError->getMessage());
            }

            return [
                'success' => false,
                'message' => 'Database error occurred during enrichment. Please try again.',
                'status'  => 500,
            ];
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error enriching book ID ' . $book->id . ': ' . $e->getMessage());

            try {
                $book->update(['api_data_status' => 'pending', 'api_error_message' => substr($e->getMessage(), 0, 500)]);
            } catch (\Exception $resetError) {
                \Log::error('Failed to reset book status after error: ' . $resetError->getMessage());
            }

            $message = 'Failed to enrich book';
            if (strpos($e->getMessage(), 'ISBN') !== false) {
                $message = 'Book has invalid or missing ISBN';
            } elseif (strpos($e->getMessage(), 'API') !== false) {
                $message = 'External API is currently unavailable';
            } elseif (strpos($e->getMessage(), 'No book data found') !== false) {
                $message = 'No additional data found for this book';
            } elseif (strpos($e->getMessage(), 'timeout') !== false) {
                $message = 'Request timed out. Please try again';
            }

            return [
                'success'       => false,
                'message'       => $message,
                'debug_message' => config('app.debug') ? $e->getMessage() : null,
                'status'        => 500,
            ];
        }
    }

    /**
     * Preview API enrichment data without applying it.
     *
     * @return array{success: bool, message: string, ...}
     */
    public function previewEnrichment(Book $book): array
    {
        try {
            \Log::info('Previewing enrichment for book ID: ' . $book->id);

            $apiData = null;
            $searchMethod = null;

            $isbn = $this->extractISBN($book);

            if (!empty($isbn)) {
                \Log::info('Trying ISBN search first: ' . $isbn);
                try {
                    $apiData = $this->apiService->fetchBookDataByISBN($isbn);
                    if (isset($apiData['items']) && !empty($apiData['items'])) {
                        $searchMethod = 'ISBN';
                    } else {
                        $apiData = null;
                    }
                } catch (\Exception $e) {
                    \Log::warning('ISBN search failed: ' . $e->getMessage());
                    $apiData = null;
                }
            }

            if (empty($apiData) || !isset($apiData['items']) || empty($apiData['items'])) {
                $title = $book->title;
                $author = $book->author_name;

                if (empty($title)) {
                    return ['success' => false, 'message' => 'الكتاب ليس له عنوان أو ISBN للبحث', 'status' => 422];
                }

                \Log::info('Trying title+author search: ' . $title);
                $apiData = $this->apiService->fetchBookDataByTitle($title, $author);

                if (!isset($apiData['items']) || empty($apiData['items'])) {
                    return ['success' => false, 'message' => 'لم يتم العثور على بيانات لهذا الكتاب في API', 'status' => 404];
                }

                $searchMethod = 'title+author';
            }

            $bookInfo = $apiData['items'][0]['volumeInfo'] ?? [];

            $imageUrl = null;
            if (isset($apiData['items'][0]['volumeInfo']['imageLinks'])) {
                $imageLinks = $apiData['items'][0]['volumeInfo']['imageLinks'];
                $imageUrl = $imageLinks['thumbnail'] ?? $imageLinks['smallThumbnail'] ?? null;
                if ($imageUrl) {
                    $imageUrl = str_replace('zoom=1', 'zoom=2', $imageUrl);
                    $imageUrl = str_replace('&edge=curl', '', $imageUrl);
                }
            }

            $previewData = [
                'title' => [
                    'current' => $book->title,
                    'api' => $bookInfo['title'] ?? null,
                    'will_update' => !empty($bookInfo['title']) && strlen(trim($bookInfo['title'])) > strlen(trim($book->title ?? ''))
                ],
                'author' => [
                    'current' => $book->author_name,
                    'api' => isset($bookInfo['authors']) ? implode(', ', $bookInfo['authors']) : null,
                    'will_update' => !empty($bookInfo['authors']) && strlen(implode(', ', $bookInfo['authors'] ?? [])) > strlen(trim($book->author_name ?? ''))
                ],
                'description' => [
                    'current' => $book->description ? substr($book->description, 0, 200) . '...' : null,
                    'api' => isset($bookInfo['description']) ? substr(strip_tags($bookInfo['description']), 0, 200) . '...' : null,
                    'will_update' => !empty($bookInfo['description']) && (strlen($bookInfo['description']) > strlen($book->description ?? '') * 1.5 || strlen($book->description ?? '') < 50)
                ],
                'page_count' => [
                    'current' => $book->page_num,
                    'api' => $bookInfo['pageCount'] ?? null,
                    'will_update' => !empty($bookInfo['pageCount']) && (empty($book->page_num) || $book->page_num == 0)
                ],
                'publisher' => [
                    'current' => $book->publishing_house_name,
                    'api' => $bookInfo['publisher'] ?? null,
                    'will_update' => !empty($bookInfo['publisher']) && strlen($bookInfo['publisher']) > strlen($book->publishing_house_name ?? '')
                ],
                'language' => [
                    'current' => $book->language,
                    'api' => isset($bookInfo['language']) ? $this->mapLanguageCode($bookInfo['language']) : null,
                    'will_update' => !empty($bookInfo['language']) && (empty($book->language) || $book->language === 'Unknown')
                ],
                'image' => [
                    'current' => $book->image,
                    'api' => $imageUrl,
                    'will_update' => $imageUrl && (empty($book->image) || $book->image === 'images/books/default-book.png')
                ]
            ];

            return [
                'success' => true,
                'book' => [
                    'id' => $book->id,
                    'title' => $book->title,
                    'author' => $book->author_name
                ],
                'search_method' => $searchMethod,
                'preview' => $previewData,
                'api_book_title' => $bookInfo['title'] ?? 'Unknown',
                'message' => 'تمت معاينة البيانات بنجاح. يرجى مراجعة البيانات قبل التأكيد.'
            ];

        } catch (\Exception $e) {
            \Log::error('Error previewing enrichment for book ID ' . $book->id . ': ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء معاينة البيانات: ' . $e->getMessage(),
                'status'  => 500,
            ];
        }
    }

    /**
     * Apply selected enrichment fields from API to a book.
     *
     * @return array{success: bool, message: string, updated_fields?: array, book?: Book, status?: int}
     */
    public function applySelectedFields(Book $book, array $selectedFields): array
    {
        try {
            if (empty($selectedFields)) {
                return ['success' => false, 'message' => 'لم يتم اختيار أي حقول للتحديث', 'status' => 400];
            }

            \Log::info('Applying selected enrichment for book ID: ' . $book->id . ', fields: ' . implode(', ', $selectedFields));

            // Fetch API data
            $apiData = null;
            $isbn = $this->extractISBN($book);

            if (!empty($isbn)) {
                try {
                    $apiData = $this->apiService->fetchBookDataByISBN($isbn);
                    if (!isset($apiData['items']) || empty($apiData['items'])) {
                        $apiData = null;
                    }
                } catch (\Exception $e) {
                    $apiData = null;
                }
            }

            if (empty($apiData) || !isset($apiData['items']) || empty($apiData['items'])) {
                $apiData = $this->apiService->fetchBookDataByTitle($book->title, $book->author_name);
            }

            if (!isset($apiData['items']) || empty($apiData['items'])) {
                return ['success' => false, 'message' => 'لم يتم العثور على بيانات من API', 'status' => 404];
            }

            $bookInfo = $apiData['items'][0]['volumeInfo'] ?? [];
            $updateData = [];
            $updatedFields = [];

            foreach ($selectedFields as $field) {
                if ($field === 'title' && isset($bookInfo['title'])) {
                    $updateData['title'] = $bookInfo['title'];
                    $updatedFields[] = 'title';
                } elseif ($field === 'description' && isset($bookInfo['description'])) {
                    $updateData['description'] = strip_tags($bookInfo['description']);
                    $updatedFields[] = 'description';
                } elseif ($field === 'page_count' && isset($bookInfo['pageCount'])) {
                    $updateData['page_num'] = $bookInfo['pageCount'];
                    $updatedFields[] = 'page_count';
                } elseif ($field === 'language' && isset($bookInfo['language'])) {
                    $updateData['language'] = $this->mapLanguageCode($bookInfo['language']);
                    $updatedFields[] = 'language';
                } elseif ($field === 'image') {
                    $imageUrl = null;
                    if (isset($bookInfo['imageLinks'])) {
                        $imageLinks = $bookInfo['imageLinks'];
                        $imageUrl = $imageLinks['thumbnail'] ?? $imageLinks['smallThumbnail'] ?? null;
                        if ($imageUrl) {
                            $imageUrl = str_replace('zoom=1', 'zoom=2', $imageUrl);
                            $imageUrl = str_replace('&edge=curl', '', $imageUrl);
                        }
                    }

                    if ($imageUrl) {
                        try {
                            $localPath = $this->imageService->downloadFromUrl($imageUrl, 'images/books', 'api_' . $book->id);
                            if ($localPath) {
                                $updateData['image'] = $localPath;
                                $updatedFields[] = 'image';
                            }
                        } catch (\Exception $e) {
                            \Log::warning('Failed to download image: ' . $e->getMessage());
                        }
                    }
                }
            }

            if (empty($updateData)) {
                return ['success' => false, 'message' => 'لم يتم العثور على بيانات للحقول المحددة', 'status' => 400];
            }

            $updateData['api_data_status'] = 'enriched';
            $updateData['api_last_updated'] = now();
            $book->update($updateData);

            \Log::info('Successfully updated book ID: ' . $book->id . ' with fields: ' . implode(', ', $updatedFields));

            return [
                'success'        => true,
                'message'        => 'تم تحديث الحقول المحددة بنجاح',
                'updated_fields' => $updatedFields,
                'book'           => $book->fresh(),
            ];

        } catch (\Exception $e) {
            \Log::error('Error applying selected enrichment for book ID ' . $book->id . ': ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'حدث خطأ أثناء تطبيق البيانات: ' . $e->getMessage(),
                'status'  => 500,
            ];
        }
    }

    /**
     * Auto-enrich a book from the API (full automatic enrichment).
     */
    public function enrichBookFromAPI(Book $book): Book
    {
        if ($book->api_data_status === 'enriched') {
            \Log::info('Book already enriched, skipping: ' . $book->id);
            return $book;
        }

        return DB::transaction(function () use ($book) {
            $book->update([
                'api_data_status' => 'processing',
                'api_last_updated' => now()
            ]);

            try {
                \Log::info('Starting book enrichment for book ID: ' . $book->id);

                $apiData = null;
                $searchMethod = null;

                $isbn = $this->extractISBN($book);

                if (!empty($isbn)) {
                    \Log::info('Trying ISBN search first: ' . $isbn);
                    try {
                        $apiData = $this->apiService->fetchBookDataByISBN($isbn);
                        if (isset($apiData['items']) && !empty($apiData['items'])) {
                            $searchMethod = 'ISBN';
                        } else {
                            $apiData = null;
                        }
                    } catch (\Exception $e) {
                        \Log::warning('ISBN search failed: ' . $e->getMessage());
                        $apiData = null;
                    }
                }

                if (empty($apiData) || !isset($apiData['items']) || empty($apiData['items'])) {
                    $title = $book->title;
                    $author = $book->author_name;

                    if (empty($title)) {
                        throw new \Exception('Book has no ISBN and no title for enrichment. Book ID: ' . $book->id);
                    }

                    $apiData = $this->apiService->fetchBookDataByTitle($title, $author);

                    if (!isset($apiData['items']) || empty($apiData['items'])) {
                        throw new \Exception('No book data found for title: ' . $title);
                    }

                    $searchMethod = 'title+author';
                }

                $mappedData = $this->mapApiData($apiData, $book);

                if (!empty($mappedData)) {
                    $book->update($mappedData);
                }

                $book->update([
                    'api_data_status' => 'enriched',
                    'api_last_updated' => now(),
                    'api_error_message' => null,
                    'api_search_method' => $searchMethod
                ]);

                \Log::info('Book enrichment completed for book ID: ' . $book->id . ' using ' . $searchMethod);

                return $book->fresh();

            } catch (\Exception $e) {
                \Log::error('Error in enrichBookFromAPI for book ID ' . $book->id . ': ' . $e->getMessage());

                $book->update([
                    'api_data_status' => 'failed',
                    'api_last_updated' => now(),
                    'api_error_message' => substr($e->getMessage(), 0, 500)
                ]);

                throw $e;
            }
        });
    }

    protected function extractISBN(Book $book): ?string
    {
        $isbn = $book->isbn ?? null;

        if (empty($isbn) || trim($isbn) === '') {
            return null;
        }

        $cleanIsbn = preg_replace('/[^0-9X]/', '', strtoupper($isbn));

        if (strlen($cleanIsbn) !== 10 && strlen($cleanIsbn) !== 13) {
            return null;
        }

        return $cleanIsbn;
    }

    protected function mapApiData(array $apiData, Book $book): array
    {
        if (!isset($apiData['items']) || empty($apiData['items'])) {
            throw new \Exception('No data found in API response');
        }

        $bookInfo = $apiData['items'][0]['volumeInfo'] ?? [];

        if (empty($bookInfo)) {
            throw new \Exception('No volume info found in API response');
        }

        $mappedData = [];

        if (!empty($bookInfo['title'])) {
            $apiTitle = trim($bookInfo['title']);
            $currentTitle = trim($book->title ?? '');

            if (strlen($apiTitle) > strlen($currentTitle) ||
                empty($currentTitle) ||
                in_array(strtolower($currentTitle), ['untitled', 'no title', 'title'])) {
                $mappedData['title'] = $apiTitle;
            }
        }

        if (!empty($bookInfo['authors']) && is_array($bookInfo['authors'])) {
            $primaryAuthorName = trim($bookInfo['authors'][0] ?? '');
            if (!empty($primaryAuthorName) && !$book->author_id) {
                $author = \App\Models\Author::firstOrCreate(
                    ['name' => $primaryAuthorName],
                    ['status' => 'active']
                );
                $mappedData['author_id'] = $author->id;
            }
        }

        if (!empty($bookInfo['description'])) {
            $apiDescription = trim(strip_tags($bookInfo['description']));
            $currentDescription = trim($book->description ?? '');

            if (strlen($apiDescription) > strlen($currentDescription) * 1.5 ||
                strlen($currentDescription) < 50) {
                $mappedData['description'] = $apiDescription;
            }
        }

        if (!empty($bookInfo['pageCount']) && is_numeric($bookInfo['pageCount'])) {
            $apiPageCount = (int) $bookInfo['pageCount'];
            $currentPageCount = (int) ($book->page_num ?? 0);

            if ($currentPageCount == 0 || ($apiPageCount > 0 && $apiPageCount < 10000)) {
                $mappedData['page_num'] = $apiPageCount;
            }
        }

        if (!empty($bookInfo['publisher'])) {
            $apiPublisher = trim($bookInfo['publisher']);
            $currentPublisher = trim($book->publishing_house_name ?? '');

            if (strlen($apiPublisher) > strlen($currentPublisher) || empty($currentPublisher)) {
                $ph = \App\Models\PublishingHouse::firstOrCreate(
                    ['name' => $apiPublisher],
                    ['status' => 'active']
                );
                $mappedData['publishing_house_id'] = $ph->id;
            }
        }

        if (!empty($bookInfo['language'])) {
            $mappedLanguage = $this->mapLanguageCode($bookInfo['language']);
            $currentLanguage = trim($book->language ?? '');

            if (empty($currentLanguage) || $currentLanguage === 'Unknown') {
                $mappedData['language'] = $mappedLanguage;
            }
        }

        // Handle image — download via ImageService
        $currentImage = trim($book->image ?? '');
        if (empty($currentImage) || $currentImage === 'images/books/default-book.png') {
            $imageUrl = APIService::getBookCoverUrl($apiData);
            if ($imageUrl && filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                $mappedData['original_image'] = $imageUrl;

                try {
                    $localImagePath = $this->imageService->downloadFromUrl($imageUrl, 'images/books', 'api_' . $book->id);
                    if ($localImagePath) {
                        $mappedData['image'] = $localImagePath;
                    }
                } catch (\Exception $e) {
                    \Log::warning('Failed to download image for book ID ' . $book->id . ': ' . $e->getMessage());
                }
            }
        }

        return $mappedData;
    }

    protected function mapLanguageCode(string $langCode): string
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
