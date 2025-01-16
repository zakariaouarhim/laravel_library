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
}
