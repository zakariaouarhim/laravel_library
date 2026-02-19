<?php

namespace App\Http\Controllers;

use Intervention\Image\Laravel\Facades\Image;
use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\Author;
use App\Models\BookAuthor;
use App\Models\PublishingHouse;
use App\Models\Category;
use App\Services\BookService;
use App\Services\APIService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class BookController extends Controller
{
    protected $bookService;
    protected $apiService;

    public function __construct(BookService $bookService, APIService $apiService)
    {
        $this->bookService = $bookService;
        $this->apiService = $apiService;
    }
   
    public function show($id)
    {
        // Get the book with its category, category's parent, and author relationship
        $book = Book::with(['category.parent', 'primaryAuthor', 'publishingHouse'])->findOrFail($id);
        
        // Get all authors
        $authors = Author::active()->get();
        
        // Get all active publishing houses
        $publishingHouses = PublishingHouse::active()->get();
        
        // Get the primary author of this book (if using relationship)
        $primaryAuthor = $book->primaryAuthor;
        
        // Get other books by the same author
        $authorBooks = collect();
        if ($primaryAuthor) {
            $authorBooks = Book::where('author_id', $primaryAuthor->id)
                ->where('id', '!=', $book->id)
                ->take(10)
                ->get();
        } else {
            // If no author relationship, try to find books by author name
            $authorBooks = Book::where('author', $book->author)
                ->where('id', '!=', $book->id)
                ->take(10)
                ->get();
        }
        
        // Get related books from the same category with author relationship
        $relatedBooks = Book::with('primaryAuthor')
            ->where('category_id', $book->category_id)
            ->where('id', '!=', $book->id)
            ->take(10)
            ->get();
        
        // If no related books found in same category, try to get books from parent category
        if ($relatedBooks->isEmpty() && $book->category && $book->category->parent_id) {
            $relatedBooks = Book::with('primaryAuthor')
                ->where('category_id', $book->category->parent_id)
                ->where('id', '!=', $book->id)
                ->take(10)
                ->get();
        }
        
        // If still no related books, get random books from same author
        if ($relatedBooks->isEmpty()) {
            $relatedBooks = $authorBooks->take(10);
        }
        
        // If still empty, get latest books (last resort)
        if ($relatedBooks->isEmpty()) {
            $relatedBooks = Book::with('primaryAuthor')
                ->where('id', '!=', $book->id)
                ->latest()
                ->take(10)
                ->get();
        }
        
        // Get other books from the same publisher
        $publisherBooks = collect();
        if ($book->publishing_house_id) {
            $publisherBooks = Book::with('primaryAuthor')
                ->where('publishing_house_id', $book->publishing_house_id)
                ->where('id', '!=', $book->id)
                ->where('type', 'book')
                ->take(10)
                ->get();
        }

        // Track recently viewed books in session
        $recentlyViewed = session()->get('recently_viewed', []);
        $recentlyViewed = array_diff($recentlyViewed, [$id]);
        array_unshift($recentlyViewed, (int) $id);
        $recentlyViewed = array_slice($recentlyViewed, 0, 10);
        session()->put('recently_viewed', $recentlyViewed);

        return view('moredetail', compact(
            'book',
            'relatedBooks',
            'authors',
            'publishingHouses',
            'primaryAuthor',
            'authorBooks',
            'publisherBooks'
        ));
    }

    public function showV2($id)
    {
        $data = $this->getBookPageData($id);
        return view('moredetail2', $data);
    }

    private function getBookPageData($id)
    {
        $book = Book::with(['category.parent', 'primaryAuthor', 'publishingHouse'])->findOrFail($id);
        $authors = Author::active()->get();
        $publishingHouses = PublishingHouse::active()->get();
        $primaryAuthor = $book->primaryAuthor;

        $authorBooks = collect();
        if ($primaryAuthor) {
            $authorBooks = Book::where('author_id', $primaryAuthor->id)->where('id', '!=', $book->id)->take(10)->get();
        } else {
            $authorBooks = Book::where('author', $book->author)->where('id', '!=', $book->id)->take(10)->get();
        }

        $relatedBooks = Book::with('primaryAuthor')->where('category_id', $book->category_id)->where('id', '!=', $book->id)->take(10)->get();
        if ($relatedBooks->isEmpty() && $book->category && $book->category->parent_id) {
            $relatedBooks = Book::with('primaryAuthor')->where('category_id', $book->category->parent_id)->where('id', '!=', $book->id)->take(10)->get();
        }
        if ($relatedBooks->isEmpty()) $relatedBooks = $authorBooks->take(10);
        if ($relatedBooks->isEmpty()) {
            $relatedBooks = Book::with('primaryAuthor')->where('id', '!=', $book->id)->latest()->take(10)->get();
        }

        $publisherBooks = collect();
        if ($book->publishing_house_id) {
            $publisherBooks = Book::with('primaryAuthor')->where('publishing_house_id', $book->publishing_house_id)->where('id', '!=', $book->id)->where('type', 'book')->take(10)->get();
        }

        $recentlyViewed = session()->get('recently_viewed', []);
        $recentlyViewed = array_diff($recentlyViewed, [$id]);
        array_unshift($recentlyViewed, (int) $id);
        session()->put('recently_viewed', array_slice($recentlyViewed, 0, 10));

        return compact('book', 'relatedBooks', 'authors', 'publishingHouses', 'primaryAuthor', 'authorBooks', 'publisherBooks');
    }

    
    
    public function getProducts()
    {
        $products = Book::with('category')->get();
        return response()->json($products); 
    }
    public function getProductsApi(Request $request)
    {
        // Get search and status filters from request
        $search = $request->query('search', '');
        $status = $request->query('status', '');

        // Start query
        $query = Book::with('category');

        // Apply search filter (title, author, ISBN)
        if (!empty($search)) {
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%$search%")
                  ->orWhere('author', 'like', "%$search%")
                  ->orWhere('ISBN', 'like', "%$search%");
            });
        }

        // Apply status filter (api_data_status)
        if (!empty($status)) {
            $query->where('api_data_status', $status);
        }

        // Paginate results (10 per page)
        $products = $query->paginate(10);

        // Get stats for the cards
        $stats = [
            'total' => Book::count(),
            'enriched' => Book::where('api_data_status', 'enriched')->count(),
            'pending' => Book::where('api_data_status', 'pending')->orWhereNull('api_data_status')->count(),
        ];

        // Format the response
        return response()->json([
            'success' => true,
            'data' => $products,
            'stats' => $stats,
        ]);
    }

    /**
     * Get API stats for dashboard cards
     */
    public function getProductsApiStats()
    {
        $stats = [
            'total' => Book::count(),
            'enriched' => Book::where('api_data_status', 'enriched')->count(),
            'pending' => Book::where('api_data_status', 'pending')->orWhereNull('api_data_status')->count(),
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }
    
    public function getProductById($id)
{
    try {
        $product = Book::with('category')->find($id);

        if ($product) {
            return response()->json([
                'success' => true,
                'data' => $product
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }
    } catch (\Exception $e) {
        \Log::error('Get Product Error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Server error occurred'
        ], 500);
    }
}



public function addProduct(Request $request) 
{
    $validated = $request->validate([
        'productName' => 'required|string|max:255',
        'productauthor' => 'required|string|max:255',
        'productDescription' => 'required|string',
        'productPrice' => 'required|numeric|min:0',
        'productNumPages' => 'nullable|integer|min:1',
        'productLanguage' => 'nullable|string|max:100',
        'ProductPublishingHouse' => 'nullable|string|max:255',
        'productIsbn' => 'nullable|string|max:50',
        'Productcategorie' => 'required|integer|exists:categories,id',
        'productQuantity' => 'required|integer|min:0',
        'productImage' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        'auto_enrich' => 'nullable|boolean'
    ]);

    // 1. FIX: Default to null, NOT the product name
    $imagePath = null; 

    // 2. Handle Image Upload
    if ($request->hasFile('productImage')) {
        try {
            $file = $request->file('productImage');
            $imageName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $destinationPath = public_path('images/books');
            
            if ($request->hasFile('productImage')) {
            $file = $request->file('productImage');
            $imageName = time() . '_' . uniqid() . '.webp';
            $destinationPath = public_path('images/books');

            // 1. Read the image
            $image = Image::read($file);

            // 2. Resize it (Scale down to 400px width, height follows aspect ratio)
            $image->scale(width: 400);

            // 3. Encode as WebP and Save
            // The bridge allows you to chain the save directly after encoding
            $image->toWebp(80)->save($destinationPath . '/' . $imageName);

    $imagePath = 'images/books/' . $imageName;
}
        } catch (\Exception $e) {
            \Log::error('Image upload failed: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Image upload failed'], 500);
        }
    }

    try {
        // 3. Find or create Author
        $authorName = trim($validated['productauthor']);
        $author = Author::firstOrCreate(
            ['name' => $authorName],
            ['status' => 'active']
        );

        // 4. Find or create Publishing House
        $publishingHouseId = null;
        $publishingHouseName = trim($validated['ProductPublishingHouse'] ?? '');
        if (!empty($publishingHouseName)) {
            $publishingHouse = PublishingHouse::firstOrCreate(
                ['name' => $publishingHouseName],
                ['status' => 'active']
            );
            $publishingHouseId = $publishingHouse->id;
        }

        // 5. Create the object but don't save yet
        $product = new Book();
        $product->title = $validated['productName'];
        $product->author = $authorName;
        $product->author_id = $author->id;
        $product->price = $validated['productPrice'];
        $product->category_id = $validated['Productcategorie'];
        $product->description = $validated['productDescription'];
        $product->image = $imagePath; // Will be null or the valid path
        $product->Page_Num = $validated['productNumPages'] ?? null;
        $product->Langue = $validated['productLanguage'] ?? null;
        $product->Publishing_House = $publishingHouseName ?: null;
        $product->publishing_house_id = $publishingHouseId;
        $product->ISBN = $validated['productIsbn'] ?? null;
        $product->Quantity = $validated['productQuantity'];
        $product->api_data_status = 'pending';

        // 6. FIX: Handle Algolia/Scout Crash Gracefully
        // We wrap save() in a specific try-catch to allow DB success even if Search fails
        try {
            $product->save();
        } catch (\Exception $e) {
            // Check if the error is related to Algolia/Scout
            if (str_contains($e->getMessage(), 'Algolia') || str_contains($e->getMessage(), 'scout')) {
                \Log::warning('Product saved to DB, but Algolia sync failed: ' . $e->getMessage());
                // Do NOT re-throw the error. Let the code continue.
            } else {
                // If it's a real Database error (SQL), re-throw it so the outer catch block handles it
                throw $e;
            }
        }

        // 7. Create book_authors pivot entry
        $product->authors()->syncWithoutDetaching([
            $author->id => ['author_type' => 'primary']
        ]);

        // 5. Enrichment Logic (Only runs if save was successful)
        $message = 'Product added successfully!';
        if ($request->boolean('auto_enrich')) {
            try {
                $this->bookService->enrichBookFromAPI($product);
                $message = 'Product added and enriched successfully!';
            } catch (\Exception $e) {
                \Log::warning('Enrichment failed: ' . $e->getMessage());
                // We don't fail the whole request just because enrichment failed
            }
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'product' => $product
            ]);
        }
        
        return redirect()->route('admin.Dashbord_Admin.product')->with('success', $message);
        
    } catch (\Exception $e) {
        \Log::error('CRITICAL Error adding product: ' . $e->getMessage());
        
        // 6. Only delete the image if the PRODUCT FAILED TO SAVE to the DB
        // If $product exists and has an ID, it means it was saved, so don't delete the image!
        if (!isset($product->id) && $imagePath && file_exists(public_path($imagePath))) {
            unlink(public_path($imagePath));
        }
        
        if ($request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في إضافة المنتج، يرجى المحاولة لاحقاً.',
            ], 500);
        }

        return redirect()->back()->withErrors(['error' => 'فشل في إضافة المنتج، يرجى المحاولة لاحقاً.'])->withInput();
    }
}
    // New method to enrich a single book
    public function enrichBook(Book $book)
{
    try {
        // Check if book is already being processed
        if ($book->api_data_status === 'processing') {
            // Check if the processing has been stuck for too long (more than 5 minutes)
            $processingTimeout = 5; // minutes
            if ($book->api_last_updated && $book->api_last_updated->diffInMinutes(now()) > $processingTimeout) {
                \Log::warning("Book ID {$book->id} was stuck in processing state for over {$processingTimeout} minutes. Resetting status.");
                $book->update(['api_data_status' => 'pending']);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Book enrichment is already in progress. Please wait and try again later.'
                ], 409);
            }
        }

        // Check if book is already enriched
        if ($book->api_data_status === 'enriched') {
            return response()->json([
                'success' => true,
                'message' => 'Book is already enriched.',
                'book' => $book->fresh()
            ]);
        }

        \Log::info('Starting enrichment for book ID: ' . $book->id);
        
        // Perform enrichment using DB transaction to ensure atomicity
        DB::beginTransaction();
        
        try {
            $enrichedBook = $this->bookService->enrichBookFromAPI($book);
            DB::commit();
            
            \Log::info('Enrichment completed successfully for book ID: ' . $book->id);
            
            return response()->json([
                'success' => true,
                'message' => 'Book enriched successfully!',
                'book' => $enrichedBook
            ]);
            
        } catch (\Exception $enrichmentError) {
            DB::rollback();
            throw $enrichmentError;
        }
                 
    } catch (\Illuminate\Database\QueryException $e) {
        \Log::error('Database error during enrichment for book ID ' . $book->id . ': ' . $e->getMessage());
        
        // Reset processing status if database error occurs
        try {
            $book->update(['api_data_status' => 'pending']);
        } catch (\Exception $resetError) {
            \Log::error('Failed to reset book status: ' . $resetError->getMessage());
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Database error occurred during enrichment. Please try again.'
        ], 500);
        
    } catch (\Exception $e) {
        \Log::error('Error enriching book ID ' . $book->id . ': ' . $e->getMessage());
        \Log::error('Stack trace: ' . $e->getTraceAsString());
        
        // Make sure to reset the status if enrichment fails
        try {
            $book->update(['api_data_status' => 'pending', 'api_error_message' => substr($e->getMessage(), 0, 500)]);
        } catch (\Exception $resetError) {
            \Log::error('Failed to reset book status after error: ' . $resetError->getMessage());
        }
        
        // Provide more specific error messages based on the error type
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
        
        return response()->json([
            'success' => false,
            'message' => $message,
            'debug_message' => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }
}

    /**
     * Preview API enrichment data without applying it
     * Allows user to review and confirm before applying changes
     */
    public function previewEnrichment(Book $book)
    {
        try {
            \Log::info('Previewing enrichment for book ID: ' . $book->id);

            $apiData = null;
            $searchMethod = null;

            // Try ISBN first if available
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

            // Fallback to title+author search
            if (empty($apiData) || !isset($apiData['items']) || empty($apiData['items'])) {
                $title = $book->title;
                $author = $book->author;

                if (empty($title)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'الكتاب ليس له عنوان أو ISBN للبحث'
                    ], 422);
                }

                \Log::info('Trying title+author search: ' . $title);
                $apiData = $this->apiService->fetchBookDataByTitle($title, $author);

                if (!isset($apiData['items']) || empty($apiData['items'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'لم يتم العثور على بيانات لهذا الكتاب في API'
                    ], 404);
                }

                $searchMethod = 'title+author';
            }

            // Extract the book info from API response
            $bookInfo = $apiData['items'][0]['volumeInfo'] ?? [];

            // Get image URL
            $imageUrl = null;
            if (isset($apiData['items'][0]['volumeInfo']['imageLinks'])) {
                $imageLinks = $apiData['items'][0]['volumeInfo']['imageLinks'];
                $imageUrl = $imageLinks['thumbnail'] ?? $imageLinks['smallThumbnail'] ?? null;
                // Get higher quality image
                if ($imageUrl) {
                    $imageUrl = str_replace('zoom=1', 'zoom=2', $imageUrl);
                    $imageUrl = str_replace('&edge=curl', '', $imageUrl);
                }
            }

            // Prepare preview data comparing current vs API values
            $previewData = [
                'title' => [
                    'current' => $book->title,
                    'api' => $bookInfo['title'] ?? null,
                    'will_update' => !empty($bookInfo['title']) && strlen(trim($bookInfo['title'])) > strlen(trim($book->title ?? ''))
                ],
                'author' => [
                    'current' => $book->author,
                    'api' => isset($bookInfo['authors']) ? implode(', ', $bookInfo['authors']) : null,
                    'will_update' => !empty($bookInfo['authors']) && strlen(implode(', ', $bookInfo['authors'] ?? [])) > strlen(trim($book->author ?? ''))
                ],
                'description' => [
                    'current' => $book->description ? substr($book->description, 0, 200) . '...' : null,
                    'api' => isset($bookInfo['description']) ? substr(strip_tags($bookInfo['description']), 0, 200) . '...' : null,
                    'will_update' => !empty($bookInfo['description']) && (strlen($bookInfo['description']) > strlen($book->description ?? '') * 1.5 || strlen($book->description ?? '') < 50)
                ],
                'page_count' => [
                    'current' => $book->Page_Num,
                    'api' => $bookInfo['pageCount'] ?? null,
                    'will_update' => !empty($bookInfo['pageCount']) && (empty($book->Page_Num) || $book->Page_Num == 0)
                ],
                'publisher' => [
                    'current' => $book->Publishing_House,
                    'api' => $bookInfo['publisher'] ?? null,
                    'will_update' => !empty($bookInfo['publisher']) && strlen($bookInfo['publisher']) > strlen($book->Publishing_House ?? '')
                ],
                'language' => [
                    'current' => $book->Langue,
                    'api' => isset($bookInfo['language']) ? $this->mapLanguageCode($bookInfo['language']) : null,
                    'will_update' => !empty($bookInfo['language']) && (empty($book->Langue) || $book->Langue === 'Unknown')
                ],
                'image' => [
                    'current' => $book->image,
                    'api' => $imageUrl,
                    'will_update' => $imageUrl && (empty($book->image) || $book->image === 'images/books/default-book.png')
                ]
            ];

            return response()->json([
                'success' => true,
                'book' => [
                    'id' => $book->id,
                    'title' => $book->title,
                    'author' => $book->author
                ],
                'search_method' => $searchMethod,
                'preview' => $previewData,
                'api_book_title' => $bookInfo['title'] ?? 'Unknown',
                'message' => 'تمت معاينة البيانات بنجاح. يرجى مراجعة البيانات قبل التأكيد.'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error previewing enrichment for book ID ' . $book->id . ': ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء معاينة البيانات: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Apply selected enrichment fields from API
     * Allows user to choose which fields to update
     */
    public function applySelectedEnrichment(Request $request, Book $book)
    {
        try {
            $selectedFields = $request->input('selected_fields', []);

            if (empty($selectedFields)) {
                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم اختيار أي حقول للتحديث'
                ], 400);
            }

            \Log::info('Applying selected enrichment for book ID: ' . $book->id . ', fields: ' . implode(', ', $selectedFields));

            // Fetch API data again
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

            // Fallback to title search
            if (empty($apiData) || !isset($apiData['items']) || empty($apiData['items'])) {
                $apiData = $this->apiService->fetchBookDataByTitle($book->title, $book->author);
            }

            if (!isset($apiData['items']) || empty($apiData['items'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم العثور على بيانات من API'
                ], 404);
            }

            $bookInfo = $apiData['items'][0]['volumeInfo'] ?? [];
            $updateData = [];
            $updatedFields = [];

            // Map selected fields to database columns
            $fieldMapping = [
                'title' => 'title',
                'author' => 'author',
                'description' => 'description',
                'page_count' => 'Page_Num',
                'publisher' => 'Publishing_House',
                'language' => 'Langue'
            ];

            foreach ($selectedFields as $field) {
                if ($field === 'title' && isset($bookInfo['title'])) {
                    $updateData['title'] = $bookInfo['title'];
                    $updatedFields[] = 'title';
                }
                elseif ($field === 'author' && isset($bookInfo['authors'])) {
                    $updateData['author'] = implode(', ', $bookInfo['authors']);
                    $updatedFields[] = 'author';
                }
                elseif ($field === 'description' && isset($bookInfo['description'])) {
                    $updateData['description'] = strip_tags($bookInfo['description']);
                    $updatedFields[] = 'description';
                }
                elseif ($field === 'page_count' && isset($bookInfo['pageCount'])) {
                    $updateData['Page_Num'] = $bookInfo['pageCount'];
                    $updatedFields[] = 'page_count';
                }
                elseif ($field === 'publisher' && isset($bookInfo['publisher'])) {
                    $updateData['Publishing_House'] = $bookInfo['publisher'];
                    $updatedFields[] = 'publisher';
                }
                elseif ($field === 'language' && isset($bookInfo['language'])) {
                    $updateData['Langue'] = $this->mapLanguageCode($bookInfo['language']);
                    $updatedFields[] = 'language';
                }
                elseif ($field === 'image') {
                    // Handle image download
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
                            $localPath = $this->downloadAndStoreBookImage($imageUrl, $book->id);
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
                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم العثور على بيانات للحقول المحددة'
                ], 400);
            }

            // Update the book
            $updateData['api_data_status'] = 'enriched';
            $updateData['api_last_updated'] = now();
            $book->update($updateData);

            \Log::info('Successfully updated book ID: ' . $book->id . ' with fields: ' . implode(', ', $updatedFields));

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الحقول المحددة بنجاح',
                'updated_fields' => $updatedFields,
                'book' => $book->fresh()
            ]);

        } catch (\Exception $e) {
            \Log::error('Error applying selected enrichment for book ID ' . $book->id . ': ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تطبيق البيانات: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download and store book cover image locally
     */
    protected function downloadAndStoreBookImage($imageUrl, $bookId)
    {
        try {
            $highQualityUrl = str_replace('zoom=1', 'zoom=2', $imageUrl);
            $highQualityUrl = str_replace('&edge=curl', '', $highQualityUrl);

            $imageContent = @file_get_contents($highQualityUrl);
            if ($imageContent === false) {
                $imageContent = @file_get_contents($imageUrl);
            }

            if ($imageContent === false) {
                throw new \Exception('Failed to download image from URL');
            }

            $extension = 'jpg';
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->buffer($imageContent);

            if (strpos($mimeType, 'png') !== false) {
                $extension = 'png';
            } elseif (strpos($mimeType, 'webp') !== false) {
                $extension = 'webp';
            }

            $filename = 'api_' . $bookId . '_' . time() . '.' . $extension;
            $destinationPath = public_path('images/books');

            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            $fullPath = $destinationPath . '/' . $filename;
            if (file_put_contents($fullPath, $imageContent) === false) {
                throw new \Exception('Failed to save image to disk');
            }

            return 'images/books/' . $filename;

        } catch (\Exception $e) {
            \Log::error('Error downloading book image: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Extract ISBN from book (helper method)
     */
    protected function extractISBN(Book $book)
    {
        $isbn = $book->ISBN ?? $book->isbn ?? null;

        if (empty($isbn) || trim($isbn) === '') {
            return null;
        }

        $cleanIsbn = preg_replace('/[^0-9X]/', '', strtoupper($isbn));

        if (strlen($cleanIsbn) !== 10 && strlen($cleanIsbn) !== 13) {
            return null;
        }

        return $cleanIsbn;
    }

    /**
     * Map language code to full name
     */
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
            'ko' => 'Korean'
        ];

        return $languageMap[strtolower($langCode)] ?? ucfirst($langCode);
    }

    // New method to get books that need enrichment
    public function getPendingEnrichment()
    {
        $books = Book::needsEnrichment()->with('category')->get();
        return response()->json($books);
    }

    // New method to bulk enrich books
    public function bulkEnrichBooks(Request $request)
    {
        // Accept both 'book_ids' and 'product_ids' for compatibility
        $bookIds = $request->input('book_ids', $request->input('product_ids', []));

        if (empty($bookIds)) {
            return response()->json(['success' => false, 'message' => 'No books selected'], 400);
        }

        $enriched = 0;
        $failed = 0;
        $errors = [];

        foreach ($bookIds as $bookId) {
            try {
                $book = Book::findOrFail($bookId);
                $this->bookService->enrichBookFromAPI($book);
                $enriched++;
            } catch (\Exception $e) {
                $failed++;
                $errors[] = "Book ID {$bookId}: " . $e->getMessage();
                \Log::error('Failed to enrich book ID ' . $bookId . ': ' . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'enriched' => $enriched,
            'enriched_count' => $enriched, // Alias for JS compatibility
            'failed' => $failed,
            'errors' => $errors,
            'message' => "Enrichment completed. Enriched: {$enriched}, Failed: {$failed}"
        ]);
    }

    public function searchproductBooks(Request $request)
    {
        $request->validate(['query' => 'nullable|string|max:200']);
        $query = $request->input('query', '');

        try {
            // First try exact match
            $books = Book::where('title', 'LIKE', "%{$query}%")
                        ->orWhere('author', 'LIKE', "%{$query}%")
                        ->orWhere('ISBN', 'LIKE', "%{$query}%")
                        ->get();
            
            // If no results, try n-gram approach with DB query
            if ($books->isEmpty()) {
                $tokens = [];
                for ($i = 0; $i < mb_strlen($query) - 2; $i++) {
                    $tokens[] = mb_substr($query, $i, 3);
                }

                if (!empty($tokens)) {
                    $ngramQuery = Book::where(function ($q) use ($tokens) {
                        foreach ($tokens as $token) {
                            $q->orWhere('title', 'LIKE', "%{$token}%")
                              ->orWhere('author', 'LIKE', "%{$token}%");
                        }
                    })->take(10)->get();
                    $books = $ngramQuery;
                }
            }

            return response()->json([
                'success' => true,
                'books' => $books
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in searchBooks:', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'An error occurred'], 500);
        }
    }
    // Method to show search results page
public function searchResults(Request $request)
{
    $request->validate([
        'query'     => 'nullable|string|max:200',
        'category'  => 'nullable|integer',
        'sort'      => 'nullable|in:newest,price_asc,price_desc,title',
        'language'  => 'nullable|string|max:50',
        'price_min' => 'nullable|numeric|min:0',
        'price_max' => 'nullable|numeric|min:0',
        'page'      => 'nullable|integer|min:1',
    ]);

    $query      = $request->input('query', '');
    $categoryId = $request->input('category');
    $sort       = $request->input('sort');

    $categories = Category::whereNull('parent_id')->with('children')->get();
    $publishingHouses = PublishingHouse::active()->get();

    // 🔍 1. Search
    $books = $this->searchBooks2($query);

    // 🎛 2. Apply filters
    if ($categoryId) {
        $books = $books->where('category_id', (int) $categoryId);
    }
    if ($request->filled('language')) {
        $books = $books->where('Langue', $request->input('language'));
    }
    if ($request->filled('price_min')) {
        $books = $books->where('price', '>=', (float) $request->input('price_min'));
    }
    if ($request->filled('price_max')) {
        $books = $books->where('price', '<=', (float) $request->input('price_max'));
    }

    // 🔀 3. Sort
    $books = match ($sort) {
        'newest'     => $books->sortByDesc('created_at')->values(),
        'price_asc'  => $books->sortBy('price')->values(),
        'price_desc' => $books->sortByDesc('price')->values(),
        'title'      => $books->sortBy('title')->values(),
        default      => $books->values(),
    };

    // 🔗 4. Related books
    $relatedBooks = $this->getRelatedBooks($books);
    $totalCount = $books->count() + $relatedBooks->count();

    // 📄 5. Paginate the collection
    $perPage = 12;
    $page = $request->input('page', 1);
    $paginatedBooks = new \Illuminate\Pagination\LengthAwarePaginator(
        $books->forPage($page, $perPage)->values(),
        $books->count(),
        $perPage,
        $page,
        ['path' => $request->url(), 'query' => $request->query()]
    );

    // 🏷 6. Related categories
    $relatedCategories = collect();
    if ($categoryId) {
        $relatedCategories = $this->relatedCategories($categoryId);
    }
    if ($relatedCategories->isEmpty()) {
        $relatedCategories = $this->popularCategories();
    }

    // 📂 7. Detect primary category from results and reorder sidebar
    $primaryCategoryId = $books->groupBy('category_id')
        ->sortByDesc(fn($group) => $group->count())
        ->keys()
        ->first();

    // Find the parent category ID (in case primaryCategoryId is a child)
    $primaryParentId = null;
    if ($primaryCategoryId) {
        $primaryCat = Category::find($primaryCategoryId);
        $primaryParentId = $primaryCat?->parent_id ?? $primaryCategoryId;
    }

    // Reorder: put the primary parent category first
    if ($primaryParentId) {
        $categories = $categories->sortByDesc(fn($cat) => $cat->id == $primaryParentId)->values();
    }

    return view('search-results', [
        'books'              => $paginatedBooks,
        'allBooksCount'      => $books->count(),
        'query'              => $query,
        'relatedBooks'       => $relatedBooks,
        'count_relatedBooks' => $totalCount,
        'categories'         => $categories,
        'relatedCategories'  => $relatedCategories,
        'publishingHouses'   => $publishingHouses,
        'primaryParentId'    => $primaryParentId,
    ]);
}
private function searchBooks2(?string $query)
{
    if (!$query) {
        return collect();
    }

    $books = Book::where('title', 'LIKE', "%{$query}%")
        ->orWhere('author', 'LIKE', "%{$query}%")
        ->orWhere('ISBN', 'LIKE', "%{$query}%")
        ->get();

    if ($books->isNotEmpty()) {
        return $books;
    }

    return $this->smartFallbackSearch($query);
}
private function smartFallbackSearch(string $query)
{
    $tokens = [];

    for ($i = 0; $i < mb_strlen($query) - 2; $i++) {
        $tokens[] = mb_substr($query, $i, 3);
    }

    if (empty($tokens)) {
        return collect();
    }

    return Book::where(function ($q) use ($tokens) {
        foreach ($tokens as $token) {
            $q->orWhere('title', 'LIKE', "%{$token}%")
              ->orWhere('author', 'LIKE', "%{$token}%");
        }
    })->take(20)->get();
}
private function applyFilters($books, ?string $filter, ?int $categoryId)
{
    // Category filter
    if ($categoryId) {
        $books = $books->where('category_id', $categoryId);
    }

    // Sorting
    return match ($filter) {
        'price_low'  => $books->sortBy('price')->values(),
        'price_high' => $books->sortByDesc('price')->values(),
        'author'     => $books->sortBy('author')->values(),
        default      => $books
    };
}
private function getRelatedBooks($books)
{
    if ($books->isEmpty()) {
        return collect();
    }

    $mainBook = $books->first();
    $excludedIds = $books->pluck('id')->toArray();

    return Book::where('category_id', $mainBook->category_id)
        ->whereNotIn('id', $excludedIds)
        ->inRandomOrder()
        ->take(10)
        ->get();
}

private function relatedBooks(int $bookId)
{
    $book = Book::with(['category.parent', 'primaryAuthor'])
        ->findOrFail($bookId);

    // 1️⃣ Same category
    $related = Book::where('category_id', $book->category_id)
        ->where('id', '!=', $book->id)
        ->take(10)
        ->get();

    // 2️⃣ Same author (fallback)
    if ($related->isEmpty() && $book->primaryAuthor) {
        $related = Book::where('author_id', $book->primaryAuthor->id)
            ->where('id', '!=', $book->id)
            ->take(10)
            ->get();
    }

    // 3️⃣ Last fallback: latest books
    if ($related->isEmpty()) {
        $related = Book::latest()
            ->where('id', '!=', $book->id)
            ->take(10)
            ->get();
    }

    return $related;
}
private function relatedCategories($categoryId)
{
    $category = Category::find($categoryId);

    if (!$category) {
        return collect();
    }

    // If category has a parent → get siblings
    if ($category->parent_id) {
        return Category::where('parent_id', $category->parent_id)
            ->where('id', '!=', $category->id)
            ->take(10)
            ->get();
    }

    // If category is parent → get children
    return Category::where('parent_id', $category->id)
        ->take(10)
        ->get();
}
private function popularCategories($limit = 10)
{
    return Category::withCount('books')
        ->orderByDesc('books_count')
        ->take($limit)
        ->get();
}


// AJAX method for autocomplete (keep your existing one)
public function searchBooksAjax(Request $request)
{
    $request->validate(['query' => 'nullable|string|max:200']);
    $query = $request->input('query', '');

    try {
        // First try exact match
        $books = Book::where('title', 'LIKE', "%{$query}%")
                    ->orWhere('author', 'LIKE', "%{$query}%")
                    ->orWhere('ISBN', 'LIKE', "%{$query}%")
                    ->take(5) // Limit for autocomplete
                    ->get();
        
        // If no results, try n-gram approach
        if ($books->isEmpty()) {
            $tokens = [];
            for ($i = 0; $i < mb_strlen($query) - 2; $i++) {
                $tokens[] = mb_substr($query, $i, 3);
            }
            
            if (!empty($tokens)) {
                $books = Book::where(function ($q) use ($tokens) {
                    foreach ($tokens as $token) {
                        $q->orWhere('title', 'LIKE', "%{$token}%")
                          ->orWhere('author', 'LIKE', "%{$token}%");
                    }
                })->take(5)->get();
            }
        }
        
        return response()->json([
            'success' => true,
            'books' => $books
        ]);
    } catch (\Exception $e) {
        \Log::error('Error in searchBooks:', ['error' => $e->getMessage()]);
        return response()->json(['success' => false, 'message' => 'An error occurred'], 500);
    }
}
   

    public function index()
    {
        // Get all authors
        $authors = Author::active()->get();
        
        // Get all active publishing houses
        $publishingHouses = PublishingHouse::active()->get();
        
        // Get books with their relationships loaded - prioritize primaryAuthor
        $books = Book::with([
            'primaryAuthor',        // Load primary author via author_id
            'authors',              // Load all authors via many-to-many as backup
            'publishingHouse',      // Load publishing house
            'category'              // Load category
        ])->get();
        $popularBooks = Cache::remember('popular_books', 1800, function () {
            return Book::select(
                'books.*',
                DB::raw('COUNT(order_details.book_id) as orders_count')
            )
            ->join('order_details', 'books.id', '=', 'order_details.book_id')
            ->groupBy('books.id')
            ->orderByDesc('orders_count')
            ->with(['primaryAuthor', 'authors'])
            ->limit(10)
            ->get();
        });

        // Get categories with children
        $categorie = Cache::remember('nav_categories', 3600, function () {
            return Category::whereNull('parent_id')
                ->with('children')
                ->take(13)
                ->get();
        });

        $categorieIcons = Cache::remember('category_icons', 3600, function () {
            return Category::withIcons()
                ->inRandomOrder()
                ->limit(12)
                ->get();
        });

        // Get English books with relationships
        $englishBooks = Book::where('Langue', 'English')
            ->with([
                'primaryAuthor',    // Primary author relationship
                'authors',          // All authors relationship
                'publishingHouse'   // Publishing house relationship
            ])
            ->get();
        $accessories = Book::accessories()->with('primaryAuthor')->limit(10)->get();

        // Get recently viewed books from session
        $recentlyViewedIds = session()->get('recently_viewed', []);
        $recentlyViewed = collect();
        if (!empty($recentlyViewedIds)) {
            $recentlyViewed = Book::with('primaryAuthor')
                ->whereIn('id', $recentlyViewedIds)
                ->where('type', 'book')
                ->get()
                ->sortBy(function ($book) use ($recentlyViewedIds) {
                    return array_search($book->id, $recentlyViewedIds);
                })->values();
        }

        return view('index', compact('books', 'categorie', 'englishBooks', 'authors', 'publishingHouses','popularBooks','categorieIcons','accessories','recentlyViewed'));
    }
    // Additional method to handle book creation with author assignment
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:191',
            'author_id' => 'nullable|exists:authors,id',
            'author_name' => 'nullable|string|max:191',
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'publishing_house_id' => 'nullable|exists:publishing_houses,id',
            'ISBN' => 'required|string|unique:books,ISBN',
            'Page_Num' => 'required|integer|min:1',
            'Langue' => 'required|string',
            'Quantity' => 'required|integer|min:0',
        ]);

        $bookData = $validated;
        
        // Handle author creation if author_name is provided but author_id is not
        if (!$request->author_id && $request->author_name) {
            $author = Author::firstOrCreate(
                ['name' => $request->author_name],
                ['status' => 'active']
            );
            $bookData['author_id'] = $author->id;
            $bookData['author'] = $author->name; // Keep old field for compatibility
        } elseif ($request->author_id) {
            $author = Author::find($request->author_id);
            $bookData['author'] = $author->name; // Keep old field for compatibility
        }
        
        // Handle publishing house
        if ($request->publishing_house_id) {
            $publishingHouse = PublishingHouse::find($request->publishing_house_id);
            $bookData['Publishing_House'] = $publishingHouse->name; // Keep old field for compatibility
        }
        
        $book = Book::create($bookData);
        
        // Create primary author relationship in book_authors table
        if (isset($author)) {
            BookAuthor::create([
                'book_id' => $book->id,
                'author_id' => $author->id,
                'author_type' => 'primary'
            ]);
        }
        
        return redirect()->route('books.index')->with('success', 'Book created successfully!');
    }

    // Method to add additional authors to a book
    public function addAuthor(Request $request, Book $book)
    {
        $request->validate([
            'author_id' => 'required|exists:authors,id',
            'author_type' => 'required|in:co-author,editor,translator,illustrator'
        ]);
        
        // Check if this author-book-type combination already exists
        $exists = BookAuthor::where([
            'book_id' => $book->id,
            'author_id' => $request->author_id,
            'author_type' => $request->author_type
        ])->exists();
        
        if (!$exists) {
            BookAuthor::create([
                'book_id' => $book->id,
                'author_id' => $request->author_id,
                'author_type' => $request->author_type
            ]);
            
            return response()->json(['success' => true, 'message' => 'Author added successfully!']);
        }
        
        return response()->json(['success' => false, 'message' => 'This author is already associated with this book in this role.']);
    }


    public function byCategory(Request $request, Category $category)
    {
        // Get current category and its children
        $childCategoryIds = $category->children->pluck('id')->toArray();
        $allCategoryIds = array_merge([$category->id], $childCategoryIds);

        // Start the query with category filter
        $query = Book::whereIn('category_id', $allCategoryIds);

        // ✅ Apply publishing house filter
        if ($request->has('publishers')) {
            $query->whereIn('Publishing_House_id', $request->input('publishers'));
        }

        // ✅ Apply language filter
        if ($request->filled('language')) {
            $query->where('Langue', $request->input('language'));
        }

        // ✅ Apply price range filters
        if ($request->filled('price_min')) {
            $query->where('price', '>=', $request->input('price_min'));
        }
        if ($request->filled('price_max')) {
            $query->where('price', '<=', $request->input('price_max'));
        }

        

        // Additional data for the filters
        $authors = Author::active()->get();
        $publishingHouses = PublishingHouse::active()->get();
        $categories = Category::all();

        switch ($request->input('sort')) {
            case 'newest':
                $query->latest();
                break;
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'title':
                $query->orderBy('title', 'asc');
                break;
        }
        
        // Final result with pagination
        $books = $query->with(['primaryAuthor', 'publishingHouse'])->paginate(12)->appends($request->query());
        return view('by-category', compact(
            'books',
            'category',
            'categories',
            'authors',
            'publishingHouses'
        ));
    }

    public function byCategory2(Category $category)
    {
        $childCategoryIds = $category->children->pluck('id')->toArray();
        $allCategoryIds = array_merge([$category->id], $childCategoryIds);
        $books = Book::whereIn('category_id', $allCategoryIds)->paginate(12);
        $categories = Category::all();

        return view('Dashbord_Admin.ManagementSystem', compact('books', 'category', 'categories'));
    }
    // Add this temporary method to your BookController for testing
    // Add this to your BookController for debugging
// Add this enhanced debug method to your BookController
public function debugEnrich($id)
{
    try {
        \Log::info('=== DEBUG: Starting enrichment debug ===');
        
        // Step 1: Test finding the book and examine its data
        \Log::info('Step 1: Finding book and examining data');
        $book = Book::findOrFail($id);
        \Log::info('Book found: ' . $book->title);
        \Log::info('Book attributes: ' . json_encode($book->toArray()));
        
        // Check ISBN field specifically
        $isbn = $book->ISBN ?? $book->isbn ?? null;
        \Log::info('ISBN from model: ' . var_export($isbn, true));
        \Log::info('ISBN field exists (uppercase): ' . (isset($book->ISBN) ? 'YES' : 'NO'));
        \Log::info('isbn field exists (lowercase): ' . (isset($book->isbn) ? 'YES' : 'NO'));
        
        if (empty($isbn)) {
            return response()->json([
                'success' => false,
                'message' => 'Book has no ISBN field set',
                'book_data' => $book->toArray(),
                'isbn_check' => [
                    'ISBN_uppercase' => $book->ISBN ?? 'NOT_SET',
                    'isbn_lowercase' => $book->isbn ?? 'NOT_SET'
                ]
            ]);
        }
        
        // Clean the ISBN
        $cleanIsbn = preg_replace('/[^0-9X]/', '', $isbn);
        \Log::info('Cleaned ISBN: ' . $cleanIsbn);
        
        if (empty($cleanIsbn)) {
            return response()->json([
                'success' => false,
                'message' => 'ISBN is empty after cleaning',
                'original_isbn' => $isbn,
                'cleaned_isbn' => $cleanIsbn
            ]);
        }
        
        // Step 2: Test APIService with the cleaned ISBN
        \Log::info('Step 2: Testing APIService with ISBN: ' . $cleanIsbn);
        $apiService = new \App\Services\APIService();
        $apiData = $apiService->fetchBookDataByISBN($cleanIsbn);
        \Log::info('APIService successful, got data: ' . (isset($apiData['items']) ? 'YES' : 'NO'));
        
        if (!isset($apiData['items']) || empty($apiData['items'])) {
            return response()->json([
                'success' => false,
                'message' => 'API returned no data for this ISBN',
                'isbn_used' => $cleanIsbn,
                'api_response' => $apiData
            ]);
        }
        
        // Step 3: Test mapping
        \Log::info('Step 3: Testing mapApiData');
        $bookService = new \App\Services\BookService();
        
        // Use reflection to call private method for testing
        $reflection = new \ReflectionClass($bookService);
        $method = $reflection->getMethod('mapApiData');
        $method->setAccessible(true);
        $mappedData = $method->invoke($bookService, $apiData, $book);
        
        \Log::info('Mapping successful, mapped fields: ' . implode(', ', array_keys($mappedData)));
        
        // Step 4: Test update with Scout disabled
        \Log::info('Step 4: Testing book update with Scout disabled');
        Book::withoutSyncingToSearch(function () use ($book, $mappedData) {
            $book->update($mappedData);
            $book->api_data_status = 'enriched';
            $book->save();
        });
        \Log::info('Book update successful');
        
        return response()->json([
            'success' => true,
            'message' => 'All debug steps passed!',
            'original_isbn' => $isbn,
            'cleaned_isbn' => $cleanIsbn,
            'mapped_data' => $mappedData,
            'api_items_count' => count($apiData['items'])
        ]);
        
    } catch (\Exception $e) {
        \Log::error('DEBUG ERROR: ' . $e->getMessage());
        \Log::error('Stack trace: ' . $e->getTraceAsString());
        
        return response()->json([
            'success' => false,
            'message' => 'Debug failed: ' . $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
            'trace' => $e->getTraceAsString()
        ], 500);
    }
}
public function testApiConnection()
{
    try {
        $apiService = new \App\Services\APIService();
        
        // Test with a known ISBN (Harry Potter)
        $testIsbn = '9780439708180';
        $result = $apiService->fetchBookDataByISBN($testIsbn);
        
        return response()->json([
            'success' => true,
            'message' => 'API connection successful!',
            'test_isbn' => $testIsbn,
            'total_items' => $result['totalItems'] ?? 0,
            'has_items' => isset($result['items']) && count($result['items']) > 0,
            'first_title' => $result['items'][0]['volumeInfo']['title'] ?? 'No title found'
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'API connection failed: ' . $e->getMessage(),
            'error_details' => [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]
        ], 500);
    }
}
public function showproduct(Request $request)
    {
        $search = $request->search;
        $categoryId = $request->category;

        $query = Book::with('category');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                ->orWhere('author', 'like', "%{$search}%");
            });
        }

        if ($categoryId) {
            $category = Category::with('parent', 'children')->find($categoryId);

            if ($category) {
                $categoryIds = collect([
                    $category->id,
                    optional($category->parent)->id,
                    ...$category->children->pluck('id')
                ])->filter()->unique();

                $query->whereIn('category_id', $categoryIds);
            }
        }

        $products = $query->latest()
                        ->paginate(15)
                        ->withQueryString();

        //get categories
        $categories = Category::whereNull('parent_id')->get();
        // Get statistics for stats cards
        $totalProducts = Book::count();
        $availableProducts = Book::where('Quantity', '>', 0)->count();
        $totalCategories = Book::distinct('category_id')->count('category_id');

        return view('Dashbord_Admin.product', compact(
            'products',
            'totalProducts',
            'availableProducts',
            'totalCategories',
            'categories'
        ));
    }
    public function viewProduct($id){
        // Get the book with its category, category's parent, and author relationship
        $product =  Book::findOrFail($id);

        return response()->json($product);
    }
    public function updateProduct(Request $request, $id)
    {
        try {
            \Log::info('=== UPDATE PRODUCT START ===');
            \Log::info('Product ID: ' . $id);
            \Log::info('Request Method: ' . $request->method());
            \Log::info('Request Data: ', $request->except(['image'])); // Don't log file data
        
            // Find the product
            $product = Book::findOrFail($id);
            \Log::info('Product found: ' . $product->title);
            
            // Validate the request
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'price' => 'required|numeric|min:0',
                'author' => 'required|string|max:255',
                'Page_Num' => 'nullable|integer|min:1',
                'Langue' => 'nullable|string|max:100',
                'Publishing_House' => 'nullable|string|max:255',
                'ISBN' => 'nullable|string|max:50',
                'Quantity' => 'required|integer|min:0',
                'category_id' => 'nullable|integer|exists:categories,id',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048'
            ]);
            
            // Find or create Author
            $authorName = trim($validated['author']);
            $author = Author::firstOrCreate(
                ['name' => $authorName],
                ['status' => 'active']
            );

            // Find or create Publishing House
            $publishingHouseId = null;
            $publishingHouseName = trim($validated['Publishing_House'] ?? '');
            if (!empty($publishingHouseName)) {
                $publishingHouse = PublishingHouse::firstOrCreate(
                    ['name' => $publishingHouseName],
                    ['status' => 'active']
                );
                $publishingHouseId = $publishingHouse->id;
            }

            // Update basic fields
            $product->title = $validated['title'];
            $product->description = $validated['description'];
            $product->price = $validated['price'];
            $product->author = $authorName;
            $product->author_id = $author->id;
            $product->Page_Num = $validated['Page_Num'] ?? null;
            $product->Langue = $validated['Langue'] ?? null;
            $product->Publishing_House = $publishingHouseName ?: null;
            $product->publishing_house_id = $publishingHouseId;
            $product->ISBN = $validated['ISBN'] ?? null;
            $product->Quantity = $validated['Quantity'];
        
            // Handle category_id
            if (isset($validated['category_id'])) {
                $product->category_id = $validated['category_id'];
            }
        
            // Handle image upload
            if ($request->hasFile('image')) {
                \Log::info('Processing image upload...');
                
                try {
                    $file = $request->file('image');
                    \Log::info('File details: ' . $file->getClientOriginalName() . ' (' . $file->getSize() . ' bytes)');
                    
                    // Delete old image if exists
                    if ($product->image) {
                        $oldImagePath = public_path($product->image);
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                            \Log::info('Old image deleted: ' . $oldImagePath);
                        }
                    }
                    
                    // Generate unique filename
                    $imageName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    $destinationPath = public_path('images/books');
                    
                    // Create directory if it doesn't exist
                    if (!file_exists($destinationPath)) {
                        if (!mkdir($destinationPath, 0755, true)) {
                            throw new \Exception('Failed to create upload directory');
                        }
                        \Log::info('Created directory: ' . $destinationPath);
                    }
                    
                    // Move the file
                    if ($file->move($destinationPath, $imageName)) {
                        $imagePath = 'images/books/' . $imageName;
                        $product->image = $imagePath;
                        
                        // Verify file exists
                        if (file_exists(public_path($imagePath))) {
                            \Log::info('Image successfully stored: ' . $imagePath);
                            \Log::info('Full path: ' . public_path($imagePath));
                        } else {
                            throw new \Exception('Image file not found after move operation');
                        }
                    } else {
                        throw new \Exception('Failed to move uploaded file');
                    }
                    
                } catch (\Exception $imageError) {
                    \Log::error('Image upload error: ' . $imageError->getMessage());
                    
                    // For AJAX requests, return the error
                    if ($request->ajax()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Failed to upload image: ' . $imageError->getMessage()
                        ], 500);
                    }
                    
                    // For regular requests, continue without image
                    \Log::warning('Continuing update without image due to upload error');
                }
            }
        
            // Save the product
            \Log::info('Attempting to save product...');
            $saved = $product->saveQuietly();
            \Log::info('Product save result: ' . ($saved ? 'SUCCESS' : 'FAILED'));
        
            if (!$saved) {
                throw new \Exception('Failed to save product to database');
            }

            // Update book_authors pivot entry
            $product->authors()->sync([
                $author->id => ['author_type' => 'primary']
            ]);

            \Log::info('=== UPDATE PRODUCT SUCCESS ===');
        
            // Return appropriate response based on request type
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم تحديث المنتج بنجاح!',
                    'product' => $product->load('category')
                ], 200);
            }
            
            return redirect()->back()->with('success', 'Product updated successfully!');
        
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::error('Product not found: ' . $e->getMessage());
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'المنتج غير موجود.'
                ], 404);
            }
            
            return redirect()->back()->withErrors(['error' => 'Product not found.']);
        
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation failed: ', $e->errors());
            
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل في التحقق من البيانات',
                    'errors' => $e->errors()
                ], 422);
            }
            
            return redirect()->back()->withErrors($e->errors())->withInput();
        
        } catch (\Exception $e) {
            \Log::error('=== UPDATE PRODUCT ERROR ===');
            \Log::error('Error Message: ' . $e->getMessage());
            \Log::error('Error File: ' . $e->getFile());
            \Log::error('Error Line: ' . $e->getLine());
            \Log::error('Stack Trace: ' . $e->getTraceAsString());
            \Log::error('=========================');
        
            if ($request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء تحديث المنتج، يرجى المحاولة لاحقاً.'
                ], 500);
            }
            
            return redirect()->back()->withErrors(['error' => 'An error occurred while updating the product.'])->withInput();
        }
    }
    public function searchBook(Request $request)
    {
        $request->validate(['q' => 'required|string|min:3|max:100']);
        $query = $request->query('q');

        $books = Book::where('ISBN', $query)
            ->orWhere('title', 'like', "%{$query}%")
            ->orWhere('author', 'like', "%{$query}%")
            ->select('id', 'ISBN', 'title', 'author', 'price', 'Quantity', 'cost_price')
            ->limit(10)
            ->get();
        
        return response()->json($books);
    }
}