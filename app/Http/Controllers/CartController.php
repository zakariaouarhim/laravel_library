<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Book;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function addToCart($bookId, Request $request)
    {
    try {
        $book = Book::findOrFail($bookId);
        $cart = session()->get('cart', []);

        $itemData = [
            'id' => $book->id,
            'title' => $request->input('title') ?? $book->title,
            'price' => $request->input('price') ?? $book->price,
            'image' => $request->input('image') ?? $book->image,
            'quantity' => $request->input('quantity', 1) // Get quantity from request
        ];

        if(isset($cart[$bookId])) {
            $cart[$bookId]['quantity']++;
        } else {
            $cart[$bookId] = $itemData;
        }

        session()->put('cart', $cart);

        // Sync with database if authenticated
        if(Auth::check()) {
            $userCart = Cart::firstOrCreate(['user_id' => Auth::id()]);
            CartItem::updateOrCreate(
                ['cart_id' => $userCart->id, 'book_id' => $bookId],
                ['quantity' => \DB::raw('quantity + 1')]
            );
        }

        return response()->json([
            'success' => true,
            'cartCount' => count($cart),
            'cart' => $cart
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
    }

    public function getCart()
    {
    try {
        $cart = session()->get('cart', []);

        // Merge with database cart if authenticated
        if (Auth::check()) {
            $userCart = Cart::with('items.book')->where('user_id', Auth::id())->first();
            
            if ($userCart) {
                foreach ($userCart->items as $item) {
                    $bookId = $item->book_id;
                    if (isset($cart[$bookId])) {
                        $cart[$bookId]['quantity'] += $item->quantity;
                    } else {
                        $cart[$bookId] = [
                            'id' => $bookId,
                            'title' => $item->book->title,
                            'price' => $item->book->price,
                            'image' => asset($item->book->image), // Use the accessor here
                            'quantity' => $item->quantity
                        ];
                    }
                }
                session()->put('cart', $cart);
            }
        }

        return response()->json([
            'success' => true,
            'cart' => $cart,
            'cartCount' => count($cart)
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
    }

    public function removeFromCart(Request $request)
{
    $cart = session()->get('cart', []);

    if (isset($cart[$request->id])) {
        unset($cart[$request->id]);
        session()->put('cart', $cart);
    }

    return response()->json([
        'success' => true,
        'cartCount' => count($cart) // Update cart count in the UI
    ]);
}
public function getCartHtml()
{
    $cart = session()->get('cart', []);
    return view('partials.cart-items', compact('cart'))->render();
}

public function removeItem(Request $request, $id)
{
    $cart = session('cart', []);
    unset($cart[$id]); // Remove the item by ID
    session(['cart' => $cart]); // Update the session

    return response()->json([
        'success' => true,
        'cartCount' => count($cart),
        'message' => 'تم حذف المنتج من السلة بنجاح'
    ]);
}
public function updateQuantity(Request $request)
{
    $validated = $request->validate([
        'id' => 'required|integer',
        'quantity' => 'required|integer|min:1'
    ]);

    $cart = session()->get('cart', []);

    if (isset($cart[$validated['id']])) {
        $cart[$validated['id']]['quantity'] = $validated['quantity'];
        session()->put('cart', $cart);
        
        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الكمية بنجاح'
        ]);
    }

    return response()->json([
        'success' => false,
        'message' => 'المنتج غير موجود في السلة'
    ], 404);
}

}