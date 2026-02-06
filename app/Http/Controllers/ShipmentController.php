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

        // Get total counts from database (not paginated)
        $totalShipments = Shipment::count();
        $processingCount = Shipment::where('status', 'processing')->count();
        $completedCount = Shipment::where('status', 'completed')->count();

        return view('Dashbord_Admin.Shipment_Management', compact('shipments', 'totalShipments', 'processingCount', 'completedCount'));
    }
    public function searchShipment(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');

        $query = Shipment::with('items');

        // Apply search filter (grouped to work correctly with status filter)
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('shipment_reference', 'like', "%$search%")
                  ->orWhere('supplier_name', 'like', "%$search%");
            });
        }

        // Apply status filter
        if (!empty($status)) {
            $query->where('status', $status);
        }

        $shipments = $query->orderBy('created_at', 'desc')->paginate(10);

        // Get total counts from database (not paginated)
        $totalShipments = Shipment::count();
        $processingCount = Shipment::where('status', 'processing')->count();
        $completedCount = Shipment::where('status', 'completed')->count();

        return view('Dashbord_Admin.Shipment_Management', compact('shipments', 'totalShipments', 'processingCount', 'completedCount'));
    }
    public function editShipment($id){
        $shipment = Shipment::with('items')->findOrFail($id);
        return response()->json($shipment);
    }
    public function updateShipment(Request $request, Shipment $shipment)
    {
        $validated = $request->validate([
            'supplier_name' => 'nullable|string|max:255',
            'arrival_date' => 'required|date',
            'status' => 'required|in:pending,processing,completed,cancelled',
            'notes' => 'nullable|string',
            'total_books' => 'nullable|integer|min:0',
            'processed_books' => 'nullable|integer|min:0',
        ]);

        try {
            $shipment->update($validated);

            return redirect()->route('admin.Dashbord_Admin.Shipment_Management')
                ->with('success', 'تم تحديث الشحنة بنجاح!');

        } catch (\Exception $e) {
            \Log::error('Error updating shipment: ' . $e->getMessage());
            return redirect()->back()
                ->withErrors(['error' => 'فشل في تحديث الشحنة. يرجى المحاولة مرة أخرى.'])
                ->withInput();
        }
    }

    public function destroyItem($shipmentId, $itemId)
    {
        try {
            $item = ShipmentItem::findOrFail($itemId);
            
            // Check if item belongs to this shipment
            if ($item->shipment_id != $shipmentId) {
                return response()->json(['success' => false, 'message' => 'العنصر لا ينتمي لهذه الشحنة'], 403);
            }

            // Get the book before deleting item
            $book = $item->book;

            // Delete the shipment item
            $item->delete();

            // Optionally: decrease book quantity if needed
            if ($book) {
                $book->Quantity -= $item->quantity_received;
                if ($book->Quantity < 0) {
                    $book->Quantity = 0;
                }
                $book->save();
            }

            // Update shipment totals
            $shipment = Shipment::find($shipmentId);
            $shipment->total_books -= $item->quantity_received;
            $shipment->save();

            return response()->json(['success' => true, 'message' => 'تم حذف العنصر بنجاح']);

        } catch (\Exception $e) {
            \Log::error('Error deleting shipment item: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'حدث خطأ: ' . $e->getMessage()], 500);
        }
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
        'shipment_reference' => 'required|unique:shipments',
        'supplier_name' => 'nullable|string|max:255',
        'arrival_date' => 'required|date',
        'notes' => 'nullable|string',
        'items' => 'required|array|min:1',
        'items.*.book_id' => 'nullable|exists:books,id',
        'items.*.isbn' => 'required|string',
        'items.*.title' => 'required|string',
        'items.*.author_id' => 'nullable|exists:authors,id',
        'items.*.publishing_house_id' => 'nullable|exists:publishing_houses,id',
        'items.*.quantity_received' => 'required|integer|min:1',
        'items.*.cost_price' => 'nullable|numeric|min:0',
        'items.*.selling_price' => 'required|numeric|min:0',
    ]);

    $shipment = Shipment::create([
        'shipment_reference' => $validated['shipment_reference'],
        'supplier_name' => $validated['supplier_name'],
        'arrival_date' => $validated['arrival_date'],
        'notes' => $validated['notes'],
        'total_books' => array_sum(array_column($validated['items'], 'quantity_received')),
        'processed_books' => 0,
        'status' => 'pending'
    ]);

    foreach ($validated['items'] as $itemData) {
        $book = null;
        
        // Check if linking to existing book
        if (!empty($itemData['book_id'])) {
            $book = Book::find($itemData['book_id']);
            $book->Quantity += $itemData['quantity_received'];
            $book->price = $itemData['selling_price'];
            $book->save();
        } else {
            // Create new book with proper relationships
            $book = Book::create([
                'title' => $itemData['title'],
                'author' => 'غير محدد', // Default author string
                'ISBN' => $itemData['isbn'],
                'author_id' => $itemData['author_id'],
                'price' => $itemData['selling_price'],
                'cost_price' => $itemData['cost_price'] ?? 0,
                'Quantity' => $itemData['quantity_received'],
                'Publishing_House' => 'غير محدد', // Default Publishing_House string
                'publishing_house_id' => $itemData['publishing_house_id'],
                'category_id' => 1,// Default category
                'Page_Num' => 0,
                'Langue' => 'غير محدد',
                'description' => 'تم إضافته من خلال الشحنة رقم: ' . $validated['shipment_reference'],
                'image' => 'images/books/default.jpg',
                'api_data_status' => 'pending'
            ]);
        }

        ShipmentItem::create([
            'shipment_id' => $shipment->id,
            'book_id' => $book->id,
            'isbn' => $itemData['isbn'],
            'title' => $itemData['title'],
            'author_id' => $itemData['author_id'],
            'quantity_received' => $itemData['quantity_received'],
            'cost_price' => $itemData['cost_price'],
            'selling_price' => $itemData['selling_price'],
            'publishing_house_id' => $itemData['publishing_house_id'],
        ]);
    }

    return redirect()->route('admin.Dashbord_Admin.Shipment_Management')
        ->with('success', 'تم إنشاء الشحنة بنجاح!');
    }
    /////////////////======delete shipment===========
    public function destroy(Shipment $shipment)
    {
        try {
            // Optional: Check if shipment can be deleted
            if ($shipment->status === 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن حذف شحنة مكتملة'
                ], 403);
            }

            // Delete all related shipment items first
            ShipmentItem::where('shipment_id', $shipment->id)->delete();

            // Delete the shipment
            $shipment->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الشحنة بنجاح'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error deleting shipment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage()
            ], 500);
        }
    }
    ///////////////////////
    public function show(Shipment $shipment)
    {
        $shipment->load('items.book');
        return view('Dashbord_Admin.ShowShipments', compact('shipment'));
    }

    public function processShipment(Shipment $shipment)
    {
        try {
            $this->shipmentService->processShipment($shipment);
            
            return redirect()->route('admin.shipments.show', $shipment->id)
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
            
            return redirect()->route('admin.shipments.show', $shipment->id)
                ->with('success', $message);
                
        } catch (\Exception $e) {
            \Log::error('Error in bulk enrichment: ' . $e->getMessage());
            return redirect()->back()
                ->withErrors(['error' => 'Bulk enrichment failed: ' . $e->getMessage()]);
        }
    }
    public function updateStatus(Request $request, Shipment $shipment)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,processing,completed,cancelled'
        ]);

        try {
            $oldStatus = $shipment->status;
            $newStatus = $validated['status'];

            // Validate status transition
            $validTransitions = [
                'pending' => ['processing', 'cancelled'],
                'processing' => ['completed', 'cancelled'],
                'completed' => [], // Cannot change from completed
                'cancelled' => [] // Cannot change from cancelled
            ];

            if (!in_array($newStatus, $validTransitions[$oldStatus] ?? [])) {
                return response()->json([
                    'success' => false,
                    'message' => "لا يمكن تغيير الحالة من '{$oldStatus}' إلى '{$newStatus}'"
                ], 422);
            }

            $shipment->update(['status' => $newStatus]);

            \Log::info("Shipment #{$shipment->id} status changed from '{$oldStatus}' to '{$newStatus}'");

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الحالة بنجاح',
                'status' => $newStatus
            ]);

        } catch (\Exception $e) {
            \Log::error('Error updating shipment status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage()
            ], 500);
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