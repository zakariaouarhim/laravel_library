<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Book;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function addToCart($bookId)
    {
        // Step 1: Find the book by ID
        $book = Book::find($bookId);

        if (!$book) {
            return response()->json(['success' => false, 'message' => 'Book not found']);
        }

        // Step 2: Check if the user is logged in
        $user = Auth::user();

        if ($user) {
            // Step 3: If the user is logged in, use the database
            // Find or create a cart for the user
            $cart = Cart::firstOrCreate(['user_id' => $user->id]);

            // Add the book to the cart (or update the quantity if it already exists)
            $cartItem = CartItem::updateOrCreate(
                ['cart_id' => $cart->id, 'book_id' => $book->id],
                ['quantity' => \DB::raw('quantity + 1')]
            );

            // Step 4: Get the updated cart count
            $cartCount = $cart->items()->count();
        } else {
            // Step 5: If the user is not logged in, use the session
            $cart = session()->get('cart', []);

            if (isset($cart[$bookId])) {
                // If the book is already in the cart, increase the quantity
                $cart[$bookId]['quantity']++;
            } else {
                // If the book is not in the cart, add it
                $cart[$bookId] = [
                    'title' => $book->title,
                    'price' => $book->price,
                    'quantity' => 1,
                    'image' => $book->image
                ];
            }

            // Save the updated cart in the session
            session()->put('cart', $cart);

            // Step 6: Get the updated cart count
            $cartCount = count($cart);
        }

        // Step 7: Return a success response with the updated cart count
        return response()->json([
            'success' => true,
            'cartCount' => $cartCount
        ]);
    }
}
