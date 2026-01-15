<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\Author;
use App\Models\BookAuthor;
use App\Models\PublishingHouse;
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
        // Get the book with its category, category's parent, and author relationship
        $book = Book::with(['category.parent', 'primaryAuthor'])->findOrFail($id);
        
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
        
        return view('moredetail', compact(
            'book', 
            'relatedBooks', 
            'authors', 
            'publishingHouses', 
            'primaryAuthor', 
            'authorBooks'
        ));
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

    public function searchproductBooks(Request $request)
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
    // Method to show search results page
public function searchResults(Request $request)
{
    $query      = $request->input('query');
    $filter     = $request->input('filter');
    $categoryId = $request->input('category');

    $categories = Category::whereNull('parent_id')->take(13)->get();

    // 🔍 1. Search
    $books = $this->searchBooks2($query);

    // 🎛 2. Apply filters
    $books = $this->applyFilters($books, $filter, $categoryId);

    // 🔗 3. Related books 
    $relatedBooks = $this->getRelatedBooks($books);

    $count_relatedBooks = $books->count() + $relatedBooks->count();
    $relatedCategories = collect();

    // If user searched by category → get related
    if ($categoryId) {
        $relatedCategories = $this->relatedCategories($categoryId);
    }

    // Fallback to popular categories
    if ($relatedCategories->isEmpty()) {
        $relatedCategories = $this->popularCategories();
    }

    return view('search-results', compact(
        'books',
        'query',
        'relatedBooks',
        'count_relatedBooks',
        'categories',
        'relatedCategories'
    ));
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

    return Book::all()->filter(function ($book) use ($tokens) {
        $matchCount = 0;

        foreach ($tokens as $token) {
            if (
                mb_stripos($book->title, $token) !== false ||
                mb_stripos($book->author, $token) !== false
            ) {
                $matchCount++;
            }
        }

        return $matchCount >= count($tokens) * 0.5;
    })->values();
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
    $query = $request->input('query');
    
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
            })->take(5);
            
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
        
        // Get categories with children
        $categorie = Category::whereNull('parent_id')
            ->with('children')
            ->take(13)
            ->get();
        $categorieImages = Category::withImages()->get();
        $categorieIcons = Category::withIcons()
                            ->inRandomOrder()
                            ->limit(12)
                            ->get();

        // Get English books with relationships
        $EnglichBooks = Book::where('Langue', 'English')
            ->with([
                'primaryAuthor',    // Primary author relationship
                'authors',          // All authors relationship
                'publishingHouse'   // Publishing house relationship
            ])
            ->get();
            
        return view('index', compact('books', 'categorie', 'EnglichBooks', 'authors', 'publishingHouses','categorieImages','categorieIcons'));
    }
    // Additional method to handle book creation with author assignment
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:191',
            'author_id' => 'nullable|exists:authors,id',
            'author_name' => 'nullable|string|max:191', // For creating new authors
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'publishing_house_id' => 'nullable|exists:publishing_houses,id',
            'ISBN' => 'required|string|unique:books,ISBN',
            'Page_Num' => 'required|integer|min:1',
            'Langue' => 'required|string',
            'Quantity' => 'required|integer|min:0',
            // Add other validation rules as needed
        ]);
        
        $bookData = $request->all();
        
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
        $books = $query->paginate(12)->appends($request->query());
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

}