<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\Category;
use App\Services\BookService;
use App\Services\APIService;

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
        // Get the book with its category and the category's parent
        $book = Book::with(['category.parent'])->findOrFail($id);
        
        // Get related books from the same category
        $relatedBooks = Book::where('category_id', $book->category_id)
            ->where('id', '!=', $book->id)
            ->take(10)
            ->get();
        
        // If no related books found in same category, try to get books from parent category
        if ($relatedBooks->isEmpty() && $book->category && $book->category->parent_id) {
            $relatedBooks = Book::where('category_id', $book->category->parent_id)
                ->where('id', '!=', $book->id)
                ->take(10)
                ->get();
        }
        
        // If still no related books, get random books from same author
        if ($relatedBooks->isEmpty()) {
            $relatedBooks = Book::where('author', $book->author)
                ->where('id', '!=', $book->id)
                ->take(10)
                ->get();
        }
        
        // If still empty, get latest books (last resort)
        if ($relatedBooks->isEmpty()) {
            $relatedBooks = Book::where('id', '!=', $book->id)
                ->latest()
                ->take(10)
                ->get();
        }
        
        return view('moredetail', compact('book', 'relatedBooks'));
    }

    public function showproduct()
    {
        return view('Dashbord_Admin.product');
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
            $query->where('title', 'like', "%$search%")
                ->orWhere('author', 'like', "%$search%")
                ->orWhere('ISBN', 'like', "%$search%");
        }

        // Apply status filter (api_data_status)
        if (!empty($status)) {
            $query->where('api_data_status', $status);
        }

        // Paginate results (10 per page)
        $products = $query->paginate(10);

        // Format the response
        return response()->json([
            'success' => true,
            'data' => $products,
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
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);
        
        // Update basic fields
        $product->title = $validated['title'];
        $product->description = $validated['description'];
        $product->price = $validated['price'];
        $product->author = $validated['author'];
        $product->Page_Num = $validated['Page_Num'] ?? null;
        $product->Langue = $validated['Langue'] ?? null;
        $product->Publishing_House = $validated['Publishing_House'] ?? null;
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
        $saved = $product->save();
        \Log::info('Product save result: ' . ($saved ? 'SUCCESS' : 'FAILED'));
       
        if (!$saved) {
            throw new \Exception('Failed to save product to database');
        }
       
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
                'message' => 'حدث خطأ أثناء تحديث المنتج: ' . $e->getMessage()
            ], 500);
        }
        
        return redirect()->back()->withErrors(['error' => 'An error occurred while updating the product.'])->withInput();
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
        'productImage' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'auto_enrich' => 'nullable|boolean'
    ]);

    // Initialize imagePath variable
    $imagePath = "images/books/".$validated['productName'];

    // Save the product image if uploaded
    if ($request->hasFile('productImage')) {
        try {
            $file = $request->file('productImage');
            $imageName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $destinationPath = public_path('images/books');
            
            // Create directory if it doesn't exist
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            $file->move($destinationPath, $imageName);
            $imagePath = 'images/books/' . $imageName;
        } catch (\Exception $e) {
            \Log::error('Image upload failed: ' . $e->getMessage());
            return redirect()->back()->withErrors(['productImage' => 'Image upload failed'])->withInput();
        }
    }

    try {
        // Save product to the database
        $product = new Book();
        $product->title = $validated['productName'];
        $product->author = $validated['productauthor'];
        $product->price = $validated['productPrice'];
        $product->category_id = $validated['Productcategorie'];
        $product->description = $validated['productDescription'];
        $product->image = $imagePath;
        $product->Page_Num = $validated['productNumPages'] ?? null;
        $product->Langue = $validated['productLanguage'] ?? null;
        $product->Publishing_House = $validated['ProductPublishingHouse'] ?? null;
        $product->ISBN = $validated['productIsbn'] ?? null;
        $product->Quantity = $validated['productQuantity']; // Use the form value instead of hardcoding 0
        $product->api_data_status = 'pending';
        $product->save();

        // Auto-enrich if requested
        if ($request->boolean('auto_enrich')) {
            try {
                $this->bookService->enrichBookFromAPI($product);
                $message = 'Product added and enriched successfully!';
            } catch (\Exception $e) {
                \Log::warning('Failed to enrich book after creation: ' . $e->getMessage());
                $message = 'Product added successfully, but API enrichment failed.';
            }
        } else {
            $message = 'Product added successfully!';
        }

        // Return JSON response for AJAX requests
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'product' => $product
            ]);
        }
        
        return redirect()->route('Dashbord_Admin.product')->with('success', $message);
        
    } catch (\Exception $e) {
        \Log::error('Error adding product: ' . $e->getMessage());
        
        // Delete uploaded image if product save fails
        if ($imagePath && file_exists(public_path($imagePath))) {
            unlink(public_path($imagePath));
        }
        
        // Return JSON error response for AJAX requests
        if ($request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في إضافة المنتج. يرجى المحاولة مرة أخرى.'
            ], 500);
        }
        
        return redirect()->back()->withErrors(['error' => 'Failed to add product. Please try again.'])->withInput();
    }
}
    // New method to enrich a single book
    // Replace your enrichBook method in BookController with this improved version
public function enrichBook(Book $book)
{
    try {
        // Check if book is already being processed
        if ($book->api_data_status === 'processing') {
            return response()->json([
                'success' => false,
                'message' => 'Book enrichment is already in progress. Please wait and try again later.'
            ], 409); // Conflict status
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
        
        // Perform enrichment
        $enrichedBook = $this->bookService->enrichBookFromAPI($book);
        
        \Log::info('Enrichment completed successfully for book ID: ' . $book->id);
        
        return response()->json([
            'success' => true,
            'message' => 'Book enriched successfully!',
            'book' => $enrichedBook
        ]);
                 
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

    // New method to get books that need enrichment
    public function getPendingEnrichment()
    {
        $books = Book::needsEnrichment()->with('category')->get();
        return response()->json($books);
    }

    // New method to bulk enrich books
    public function bulkEnrichBooks(Request $request)
    {
        $bookIds = $request->input('book_ids', []);
        
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
            'failed' => $failed,
            'errors' => $errors,
            'message' => "Enrichment completed. Enriched: {$enriched}, Failed: {$failed}"
        ]);
    }

    public function searchBooks(Request $request)
    {
        $query = $request->input('query');
        
        try {
            // First try exact match
            $books = Book::where('title', 'LIKE', "%{$query}%")
                        ->orWhere('author', 'LIKE', "%{$query}%")
                        ->orWhere('ISBN', 'LIKE', "%{$query}%")
                        ->get();
            
            // If no results, try n-gram approach
            if ($books->isEmpty()) {
                $tokens = [];
                for ($i = 0; $i < mb_strlen($query) - 2; $i++) {
                    $tokens[] = mb_substr($query, $i, 3);
                }
                
                $allBooks = Book::all();
                $matchingBooks = $allBooks->filter(function($book) use ($tokens) {
                    $matchCount = 0;
                    foreach ($tokens as $token) {
                        if (mb_stripos($book->title, $token) !== false || 
                            mb_stripos($book->author, $token) !== false) {
                            $matchCount++;
                        }
                    }
                    return $matchCount >= count($tokens) * 0.5;
                })->take(10);
                
                $books = $matchingBooks->values();
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
        $books = Book::all();
        $categorie = Category::whereNull('parent_id')
            ->with('children')
            ->take(13)
            ->get();
        $EnglichBooks = Book::where('Langue', 'English')->get();   

        return view('index', compact('books', 'categorie', 'EnglichBooks'));
    }

    public function byCategory(Category $category)
    {
        $childCategoryIds = $category->children->pluck('id')->toArray();
        $allCategoryIds = array_merge([$category->id], $childCategoryIds);
        $books = Book::whereIn('category_id', $allCategoryIds)->paginate(12);
        $categories = Category::all();

        return view('by-category', compact('books', 'category', 'categories'));
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

}