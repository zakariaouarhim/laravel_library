<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Models\Book;
use App\Services\ShipmentService;
use App\Services\BookService;
use App\Models\Category;

class ShipmentController extends Controller
{
    protected $shipmentService;
    protected $bookService;

    public function __construct(ShipmentService $shipmentService, BookService $bookService)
    {
        $this->shipmentService = $shipmentService;
        $this->bookService = $bookService;
    }

    public function index()
    {
        $shipments = Shipment::with('items')->orderBy('created_at', 'desc')->paginate(10);
        return view('Dashbord_Admin.Shipment_Management', compact('shipments'));
    }
    public function showmanagement(Category $category)     
{         
    $childCategoryIds = $category->children->pluck('id')->toArray();         
    $allCategoryIds = array_merge([$category->id], $childCategoryIds);         
    $books = Book::whereIn('category_id', $allCategoryIds)->paginate(12);         
    
    // Get categories organized hierarchically
    $categories = Category::with('children')->whereNull('parent_id')->get();
    
    return view('Dashbord_Admin.ManagementSystem', compact('books', 'category', 'categories'));     
}
    public function create()
    {
        return view('Dashbord_Admin.shipments.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'shipment_reference' => 'required|unique:shipments,shipment_reference',
            'supplier_name' => 'nullable|string|max:255',
            'arrival_date' => 'required|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.isbn' => 'required|string|max:20',
            'items.*.title' => 'required|string|max:255',
            'items.*.author' => 'nullable|string|max:255',
            'items.*.quantity_received' => 'required|integer|min:1',
            'items.*.cost_price' => 'nullable|numeric|min:0',
            'items.*.selling_price' => 'required|numeric|min:0',
        ]);
    
        try {
            // Calculate total books count
            $totalBooks = array_sum(array_column($validated['items'], 'quantity_received'));
    
            // Create shipment with correct field names
            $shipment = Shipment::create([
                'shipment_reference' => $validated['shipment_reference'],
                'supplier_name' => $validated['supplier_name'],
                'arrival_date' => $validated['arrival_date'],
                'notes' => $validated['notes'],
                'total_books' => $totalBooks,
                'processed_books' => 0,
                'status' => 'pending'
            ]);
    
            // Create shipment items and process books
            foreach ($validated['items'] as $itemData) {
                // Create shipment item
                $shipmentItem = ShipmentItem::create([
                    'shipment_id' => $shipment->id,
                    'isbn' => $itemData['isbn'],
                    'title' => $itemData['title'],
                    'author' => $itemData['author'],
                    'quantity_received' => $itemData['quantity_received'],
                    'cost_price' => $itemData['cost_price'],
                    'selling_price' => $itemData['selling_price'],
                ]);
    
                // Check if book already exists by ISBN
                $existingBook = Book::where('ISBN', $itemData['isbn'])->first();
    
                if ($existingBook) {
                    // Update existing book quantity and prices
                    $existingBook->Quantity += $itemData['quantity_received'];
                    $existingBook->price = $itemData['selling_price']; // Update selling price
                    $existingBook->save();
                    
                    // Link shipment item to existing book
                    $shipmentItem->update(['book_id' => $existingBook->id]);
                } else {
                    // Create new book entry
                    $newBook = Book::create([
                        'title' => $itemData['title'],
                        'author' => $itemData['author'] ?? 'غير محدد',
                        'ISBN' => $itemData['isbn'],
                        'price' => $itemData['selling_price'],
                        'Quantity' => $itemData['quantity_received'],
                        'cost_price' => $itemData['cost_price'] ?? 0,
                        'description' => 'تم إضافته من خلال الشحنة رقم: ' . $validated['shipment_reference'],
                        'category_id' => 1, // Default category, you might want to make this configurable
                        'Page_Num' => 0,
                        'Langue' => 'غير محدد',
                        'Publishing_House' => 'غير محدد',
                        'image' => 'images/books/default.jpg', // Default image path
                        'api_data_status' => 'pending'
                    ]);
    
                    // Link shipment item to new book
                    $shipmentItem->update(['book_id' => $newBook->id]);
                }
            }
    
            // Use the correct route name for redirect
            return redirect()->route('Dashbord_Admin.Shipment_Management')
                ->with('success', 'تم إنشاء الشحنة بنجاح!');
    
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            \Log::error('Error creating shipment: ' . $e->getMessage());
            return redirect()->back()
                ->withErrors(['error' => 'فشل في إنشاء الشحنة. يرجى المحاولة مرة أخرى.'])
                ->withInput();
        }
    }

    public function show(Shipment $shipment)
    {
        $shipment->load('items.book');
        return view('Dashbord_Admin.ShowShipments', compact('shipment'));
    }

    public function processShipment(Shipment $shipment)
    {
        try {
            $this->shipmentService->processShipment($shipment);
            
            return redirect()->route('shipments.show', $shipment->id)
                ->with('success', 'Shipment processed successfully!');
                
        } catch (\Exception $e) {
            \Log::error('Error processing shipment: ' . $e->getMessage());
            return redirect()->back()
                ->withErrors(['error' => 'Failed to process shipment: ' . $e->getMessage()]);
        }
    }

    public function enrichItem(ShipmentItem $item)
    {
        try {
            if ($item->book) {
                $this->bookService->enrichBookFromAPI($item->book);
            }
            
            return response()->json(['success' => true, 'message' => 'Book enriched successfully']);
            
        } catch (\Exception $e) {
            \Log::error('Error enriching book: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function bulkEnrich(Shipment $shipment)
    {
        try {
            $enriched = 0;
            $failed = 0;

            foreach ($shipment->items as $item) {
                if ($item->book && $item->book->api_data_status !== 'enriched') {
                    try {
                        $this->bookService->enrichBookFromAPI($item->book);
                        $enriched++;
                    } catch (\Exception $e) {
                        $failed++;
                        \Log::error('Failed to enrich book ID ' . $item->book->id . ': ' . $e->getMessage());
                    }
                }
            }

            $message = "Enrichment completed. Enriched: {$enriched}, Failed: {$failed}";
            
            return redirect()->route('shipments.show', $shipment->id)
                ->with('success', $message);
                
        } catch (\Exception $e) {
            \Log::error('Error in bulk enrichment: ' . $e->getMessage());
            return redirect()->back()
                ->withErrors(['error' => 'Bulk enrichment failed: ' . $e->getMessage()]);
        }
    }
    public function updateProduct(Request $request, $id)
    {
        try {
            \Log::info('=== UPDATE PRODUCT START ===');
            \Log::info('Product ID: ' . $id);
            \Log::info('Request Method: ' . $request->method());
            \Log::info('Request Data: ', $request->all());
            
            // Find the product
            $product = Book::findOrFail($id);
            \Log::info('Product found: ' . $product->title);
    
            // Update fields one by one with logging
            $product->title = $request->input('title');
            \Log::info('Title updated to: ' . $product->title);
            
            $product->description = $request->input('description');
            $product->price = $request->input('price');
            $product->author = $request->input('author');
            $product->Page_Num = $request->input('Page_Num');
            $product->Langue = $request->input('Langue');
            $product->Publishing_House = $request->input('Publishing_House');
            $product->ISBN = $request->input('ISBN');
            $product->Quantity = $request->input('Quantity');
            
            // Handle category_id if it exists
            if ($request->has('category_id')) {
                $product->category_id = $request->input('category_id');
            }
            
            // Handle image upload if exists
            if ($request->hasFile('image')) {
                \Log::info('Image file detected');
                try {
                    $imagePath = $request->file('image')->store('products', 'public');
                    $product->image = $imagePath;
                    \Log::info('Image saved to: ' . $imagePath);
                } catch (\Exception $imageError) {
                    \Log::error('Image upload error: ' . $imageError->getMessage());
                    // Continue without failing the entire update
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
            
            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'Product updated successfully!',
                'product_id' => $product->id
            ], 200);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::error('Product not found: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Product not found.'
            ], 404);
            
        } catch (\Exception $e) {
            \Log::error('=== UPDATE PRODUCT ERROR ===');
            \Log::error('Error Message: ' . $e->getMessage());
            \Log::error('Error File: ' . $e->getFile());
            \Log::error('Error Line: ' . $e->getLine());
            \Log::error('Stack Trace: ' . $e->getTraceAsString());
            \Log::error('=========================');
            
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the product: ' . $e->getMessage()
            ], 500);
        }
    }
}