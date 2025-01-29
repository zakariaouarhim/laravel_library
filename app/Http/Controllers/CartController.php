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
    \Log::info('Adding book to cart:', ['bookId' => $bookId]); // Debugging

    try {
        // Fetch the book by ID
        $book = Book::find($bookId);

        if (!$book) {
            \Log::error('Book not found:', ['bookId' => $bookId]); // Debugging
            return response()->json(['success' => false, 'message' => 'Book not found']);
        }

        // Get the current user
        $user = Auth::user();

        if ($user) {
            \Log::info('User is logged in:', ['userId' => $user->id]); // Debugging

            // Find or create a cart for the user
            $cart = Cart::firstOrCreate(['user_id' => $user->id]);

            // Add the book to the cart (or update the quantity if it already exists)
            $cartItem = CartItem::updateOrCreate(
                ['cart_id' => $cart->id, 'book_id' => $book->id],
                ['quantity' => \DB::raw('quantity + 1')]
            );

            // Get the updated cart count
            $cartCount = $cart->items()->count();
        } else {
            \Log::info('User is not logged in, using session.'); // Debugging

            // If the user is not logged in, use the session
            $cart = session()->get('cart', []);

            if (isset($cart[$bookId])) {
                $cart[$bookId]['quantity']++;
            } else {
                $cart[$bookId] = [
                    'title' => $book->title,
                    'price' => $book->price,
                    'quantity' => 1,
                    'image' => $book->image
                ];
            }

            // Save the updated cart in the session
            session()->put('cart', $cart);

            // Get the updated cart count
            $cartCount = count($cart);
        }

        \Log::info('Cart updated successfully:', ['cartCount' => $cartCount]); // Debugging

        // Return a success response with the updated cart count
        return response()->json([
            'success' => true,
            'cartCount' => $cartCount
        ]);
    } catch (\Exception $e) {
        \Log::error('Error adding book to cart:', ['error' => $e->getMessage()]); // Debugging
        return response()->json(['success' => false, 'message' => 'An error occurred']);
    }
}
}
