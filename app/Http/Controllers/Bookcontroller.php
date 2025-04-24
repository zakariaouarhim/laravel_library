<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;

class Bookcontroller extends Controller
{
    public function index(){
        $books= Book::all();
        

        return view('index', compact('books'));
    }
    public function show($id)
    {
    $book = Book::findOrFail($id);
    return view('moredetail', compact('book'));
    }
    public function showproduct()
    {
        return view('Dashbord_Admin.product');
    }

    public function getProducts()
    {
        $products = Book::all();
        return response()->json($products);
    }
    
    public function getProductById($id)
    {
        $product = Book::find($id);

        if ($product) {
            return response()->json($product);
        } else {
            return response()->json(['message' => 'Product not found'], 404);
        }
    }


   public function updateProduct(Request $request, $id)
    {
        \Log::info('Update Product Request Data:', $request->all()); // Log the request data

        try {
            $product = Book::findOrFail($id);

            // Update fields
            $product->title = $request->input('title');
            $product->description = $request->input('description');
            $product->price = $request->input('price');
            $product->author = $request->input('author');
            $product->Page_Num = $request->input('Page_Num');
            $product->Langue = $request->input('Langue');
            $product->Publishing_House = $request->input('Publishing_House');
            $product->ISBN = $request->input('ISBN');
            $product->save();

            return response()->json(['message' => 'Product updated successfully!']);
        } catch (\Exception $e) {
            \Log::error('Error updating product:', ['error' => $e->getMessage()]); // Log the error
            return response()->json(['message' => 'An error occurred while updating the product.'], 500);
        }
    }
    public function addProduct(Request $request){
        $validated = $request->validate([
            'productName' => 'required|string|max:255',
            'productauthor' => 'required|string|max:255',
            'productDescription' => 'required|string',
            'productPrice' => 'required|numeric',
            'productNumPages' => 'required|integer',
            'productLanguage' => 'required|string',
            'ProductPublishingHouse' => 'required|string',
            'productIsbn' => 'required|string',
            'Productcategorie' => 'required|integer',
            'productImage' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        // Save the product image
        if ($request->hasFile('productImage')) {
             // Get the uploaded file
            $file = $request->file('productImage');

            // Generate a unique name for the image
            $imageName = time() . '.' . $file->getClientOriginalExtension();

            // Move the file to the public/images/books directory
            $file->move(public_path('images/books'), $imageName);

            // Save the path in the database
            $imagePath = 'images/books/' . $imageName;
        }

        // Save product to the database...
        $product = new Book();
        $product->title = $validated['productName'];
        $product->author = $validated['productauthor'];
        $product->price = $validated['productPrice'];
        $product->category_id = $validated['Productcategorie'];
        $product->description = $validated['productDescription'];
        $product->image = $imagePath;
        $product->Page_Num = $validated['productNumPages'];
        $product->Langue = $validated['productLanguage'];
        $product->Publishing_House = $validated['ProductPublishingHouse'];
        $product->ISBN = $validated['productIsbn'];
        
        $product->save();

        return redirect()->route('Dashbord_Admin.product')->with('success', 'Product added successfully!');

    }
    // BookController.php

public function searchBooks(Request $request)
{
    $query = $request->input('query');
    
    try {
        // First try exact match
        $books = Book::where('title', 'LIKE', "%{$query}%")
                    ->orWhere('author', 'LIKE', "%{$query}%")
                    ->get();
        
        // If no results, try n-gram approach
        if ($books->isEmpty()) {
            // Generate 3-letter sequences for query
            $tokens = [];
            for ($i = 0; $i < mb_strlen($query) - 2; $i++) {
                $tokens[] = mb_substr($query, $i, 3);
            }
            
            // Filter using n-grams
            $allBooks = Book::all();
            $matchingBooks = $allBooks->filter(function($book) use ($tokens) {
                $matchCount = 0;
                foreach ($tokens as $token) {
                    if (mb_stripos($book->title, $token) !== false || 
                        mb_stripos($book->author, $token) !== false) {
                        $matchCount++;
                    }
                }
                // If at least 50% of the tokens match, consider it a match
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
}
