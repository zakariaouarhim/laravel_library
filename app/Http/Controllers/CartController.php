<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use App\Services\CartService;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function __construct(
        private CartService $cartService,
    ) {}

    public function addToCart($bookId, Request $request)
    {
        try {
            $book = Book::findOrFail($bookId);

            if (($book->quantity ?? 0) <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'عذراً، هذا الكتاب غير متوفر حالياً.',
                ], 422);
            }

            $cart = $this->cartService->addItem($book, (int) $request->input('quantity', 1));

            return response()->json([
                'success'   => true,
                'cartCount' => count($cart),
                'cart'      => $cart,
            ]);

        } catch (\Exception $e) {
            \Log::error('Add to cart error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ، يرجى المحاولة لاحقاً',
            ], 500);
        }
    }

    public function getCart()
    {
        try {
            $cart = $this->cartService->loadCartForApi();

            if (Auth::check()) {
                session()->forget('cart');
            }

            return response()->json([
                'success'   => true,
                'cart'      => $cart,
                'cartCount' => count($cart),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function showCart()
    {
        $cart   = $this->cartService->loadCart();
        $totals = $this->cartService->calculateTotals($cart);

        return view('cart.index', compact('cart') + $totals);
    }

    public function showCheckout()
    {
        $cart   = $this->cartService->loadCart();
        $totals = $this->cartService->calculateTotals($cart);

        $checkoutToken = \Illuminate\Support\Str::random(40);
        session(['checkout_token' => $checkoutToken]);

        $lastPhone = null;
        if (Auth::check()) {
            $lastPhone = \App\Models\CheckoutDetail::whereHas('order', function ($q) {
                $q->where('user_id', Auth::id());
            })->latest('id')->value('phone');
        }

        return view('checkout', compact('cart', 'checkoutToken', 'lastPhone') + $totals);
    }

    public function removeFromCart(Request $request)
    {
        try {
            $cartCount = $this->cartService->removeItem($request->id);

            return response()->json([
                'success'   => true,
                'cartCount' => $cartCount,
                'message'   => 'تم حذف المنتج من السلة بنجاح',
            ]);

        } catch (\Exception $e) {
            \Log::error('Remove from cart error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف المنتج',
            ], 500);
        }
    }

    public function updateQuantity(Request $request)
    {
        try {
            $validated = $request->validate([
                'id'       => 'required|integer',
                'quantity' => 'required|integer|min:1',
            ]);

            $itemId      = $validated['id'];
            $newQuantity = $validated['quantity'];

            $book = Book::find($itemId);
            if ($book && $book->quantity < $newQuantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'الكمية المطلوبة غير متوفرة. المتوفر: ' . $book->quantity,
                ], 422);
            }

            $this->cartService->updateItemQuantity($itemId, $newQuantity);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الكمية بنجاح',
            ]);

        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Update quantity error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تحديث الكمية',
            ], 500);
        }
    }

    public function storeForCheckout(Request $request)
    {
        session()->put('checkout_cart', json_decode($request->cart_data, true));
        return redirect()->route('checkout.page');
    }
}
