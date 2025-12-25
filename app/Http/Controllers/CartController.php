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
            'quantity' => $request->input('quantity', 1)
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
            
            // Check if item already exists
            $existingItem = CartItem::where('cart_id', $userCart->id)
                                  ->where('book_id', $bookId)
                                  ->first();
            
            if($existingItem) {
                // Update existing item
                $existingItem->increment('quantity');
            } else {
                // Create new item
                CartItem::create([
                    'cart_id' => $userCart->id,
                    'book_id' => $bookId,
                    'quantity' => 1
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'cartCount' => count($cart),
            'cart' => $cart
        ]);

    } catch (\Exception $e) {
        \Log::error('Add to cart error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => $e->getMessage()
        ], 500);
    }
}

    public function getCart()
{
    try {
        $cart = [];
        
        if (Auth::check()) {
            // For logged-in users, get cart from database only
            $userCart = Cart::with('items.book')->where('user_id', Auth::id())->first();
            
            if ($userCart) {
                foreach ($userCart->items as $item) {
                    $cart[$item->book_id] = [
                        'id' => $item->book_id,
                        'title' => $item->book->title,
                        'price' => $item->book->price,
                        'image' => asset($item->book->image),
                        'quantity' => $item->quantity
                    ];
                }
            }
            
            // Clear session cart for logged-in users to prevent confusion
            session()->forget('cart');
            
        } else {
            // For guests, use session cart
            $sessionCart = session()->get('cart', []);
            
            // Process session cart to add full asset URLs
            foreach ($sessionCart as $id => $item) {
                $cart[$id] = [
                    'id' => $item['id'] ?? $id,
                    'title' => $item['title'],
                    'price' => $item['price'],
                    'image' => asset($item['image']), // Convert to full URL
                    'quantity' => $item['quantity']
                ];
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
public function showCheckout() {
    $cart = [];
     
    if (Auth::check()) {
        // For logged-in users, get cart from database
        $userCart = Cart::with('items.book')->where('user_id', Auth::id())->first();
         
        if ($userCart) {
            foreach ($userCart->items as $item) {
                $cart[$item->book_id] = [
                    'id' => $item->book_id,
                    'title' => $item->book->title,
                    'price' => $item->book->price,
                    'image' => $item->book->image,
                    'quantity' => $item->quantity
                ];
            }
        }
    } else {
        // For guests, use session cart
        $cart = session()->get('cart', []);
    }
    
    // Calculate totals
    $subtotal = 0;
    foreach($cart as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    
    $shipping = 25.00;
    $discount = 0.00;
    $total = $subtotal + $shipping - $discount;
     
    return view('checkout', compact('cart', 'subtotal', 'shipping', 'discount', 'total'));
}

public function removeFromCart(Request $request)
{
    try {
        $bookId = $request->id;
        $cartCount = 0;
        
        if (Auth::check()) {
            // Remove from database for authenticated users
            $userCart = Cart::where('user_id', Auth::id())->first();
            if ($userCart) {
                $userCart->items()->where('book_id', $bookId)->delete();
                
                // Get updated cart count from database
                $cartCount = $userCart->items()->count();
                
                // If cart is empty, delete the cart record
                if ($cartCount === 0) {
                    $userCart->delete();
                }
            }
            
            // Clear session cart for authenticated users (consistency)
            session()->forget('cart');
            
        } else {
            // Remove from session for guests
            $cart = session()->get('cart', []);
            if (isset($cart[$bookId])) {
                unset($cart[$bookId]);
                session()->put('cart', $cart);
                $cartCount = count($cart);
            }
        }

        return response()->json([
            'success' => true,
            'cartCount' => $cartCount,
            'message' => 'تم حذف المنتج من السلة بنجاح'
        ]);
        
    } catch (\Exception $e) {
        \Log::error('Remove from cart error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'حدث خطأ أثناء حذف المنتج'
        ], 500);
    }
}

// Helper method to sync session items to database
private function syncItemToDatabase($bookId, $item)
{
    if (Auth::check()) {
        $userCart = Cart::firstOrCreate(['user_id' => Auth::id()]);
        
        $userCart->items()->updateOrCreate(
            ['book_id' => $bookId],
            ['quantity' => $item['quantity']]
        );
    }
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
    try {
        $validated = $request->validate([
            'id' => 'required|integer',
            'quantity' => 'required|integer|min:1'
        ]);

        $itemId = $validated['id'];
        $newQuantity = $validated['quantity'];

        if (Auth::check()) {
            // For authenticated users, update database
            $userCart = Cart::where('user_id', Auth::id())->first();
            
            if ($userCart) {
                $cartItem = $userCart->items()->where('book_id', $itemId)->first();
                
                if ($cartItem) {
                    $cartItem->update(['quantity' => $newQuantity]);
                    
                    return response()->json([
                        'success' => true,
                        'message' => 'تم تحديث الكمية بنجاح'
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'المنتج غير موجود في السلة'
                    ], 404);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'السلة غير موجودة'
                ], 404);
            }
        } else {
            // For guests, update session (your existing code)
            $cart = session()->get('cart', []);

            if (isset($cart[$itemId])) {
                $cart[$itemId]['quantity'] = $newQuantity;
                session()->put('cart', $cart);
                
                return response()->json([
                    'success' => true,
                    'message' => 'تم تحديث الكمية بنجاح'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'المنتج غير موجود في السلة'
                ], 404);
            }
        }

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'بيانات غير صحيحة',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        \Log::error('Update quantity error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'حدث خطأ في تحديث الكمية'
        ], 500);
    }
}

}