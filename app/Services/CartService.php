<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Auth;

class CartService
{
    /**
     * Load cart items as a plain array (for views / internal use).
     * Keys = book IDs.
     */
    public function loadCart(): array
    {
        if (Auth::check()) {
            return $this->loadCartFromDb();
        }

        return session()->get('cart', []);
    }

    /**
     * Load cart for JSON API responses (images wrapped with asset()).
     */
    public function loadCartForApi(): array
    {
        if (Auth::check()) {
            $cart = $this->loadCartFromDb();

            foreach ($cart as &$item) {
                $item['image'] = asset($item['image']);
            }

            return $cart;
        }

        $sessionCart = session()->get('cart', []);
        $cart = [];

        foreach ($sessionCart as $id => $item) {
            $cart[$id] = [
                'id'       => $item['id'] ?? $id,
                'title'    => $item['title'],
                'price'    => $item['price'],
                'image'    => asset($item['image']),
                'quantity' => $item['quantity'],
            ];
        }

        return $cart;
    }

    /**
     * Load cart for checkout — re-fetches prices from DB for guests.
     */
    public function loadCartForCheckout(): array
    {
        if (Auth::check()) {
            return $this->loadCartFromDb();
        }

        $cart = [];

        foreach (session()->get('cart', []) as $bookId => $item) {
            $book = Book::find($bookId);
            if ($book) {
                $cart[$bookId] = [
                    'id'       => $book->id,
                    'title'    => $book->title,
                    'price'    => $book->price,
                    'image'    => $book->image,
                    'quantity' => max(1, (int) ($item['quantity'] ?? 1)),
                ];
            }
        }

        return $cart;
    }

    /**
     * Add a book to the cart (session + DB for authenticated users).
     * Returns the updated session cart.
     */
    public function addItem(Book $book, int $quantity = 1): array
    {
        $cart = session()->get('cart', []);

        $itemData = [
            'id'       => $book->id,
            'title'    => $book->title,
            'price'    => $book->price,
            'image'    => $book->image,
            'quantity' => max(1, $quantity),
        ];

        if (isset($cart[$book->id])) {
            $cart[$book->id]['quantity']++;
        } else {
            $cart[$book->id] = $itemData;
        }

        session()->put('cart', $cart);

        if (Auth::check()) {
            $userCart = Cart::firstOrCreate(['user_id' => Auth::id()]);

            $existingItem = CartItem::where('cart_id', $userCart->id)
                                    ->where('book_id', $book->id)
                                    ->first();

            if ($existingItem) {
                $existingItem->increment('quantity');
            } else {
                CartItem::create([
                    'cart_id'  => $userCart->id,
                    'book_id'  => $book->id,
                    'quantity' => 1,
                ]);
            }
        }

        return $cart;
    }

    /**
     * Remove a book from the cart. Returns the new cart count.
     */
    public function removeItem(int $bookId): int
    {
        if (Auth::check()) {
            $userCart = Cart::where('user_id', Auth::id())->first();

            if ($userCart) {
                $userCart->items()->where('book_id', $bookId)->delete();
                $count = $userCart->items()->count();

                if ($count === 0) {
                    $userCart->delete();
                }

                session()->forget('cart');
                return $count;
            }

            return 0;
        }

        $cart = session()->get('cart', []);

        if (isset($cart[$bookId])) {
            unset($cart[$bookId]);
            session()->put('cart', $cart);
        }

        return count($cart);
    }

    /**
     * Update the quantity of a cart item.
     * Returns true on success, throws on failure.
     */
    public function updateItemQuantity(int $bookId, int $newQuantity): void
    {
        if (Auth::check()) {
            $userCart = Cart::where('user_id', Auth::id())->first();

            if (!$userCart) {
                throw new \RuntimeException('السلة غير موجودة');
            }

            $cartItem = $userCart->items()->where('book_id', $bookId)->first();

            if (!$cartItem) {
                throw new \RuntimeException('المنتج غير موجود في السلة');
            }

            $cartItem->update(['quantity' => $newQuantity]);
        } else {
            $cart = session()->get('cart', []);

            if (!isset($cart[$bookId])) {
                throw new \RuntimeException('المنتج غير موجود في السلة');
            }

            $cart[$bookId]['quantity'] = $newQuantity;
            session()->put('cart', $cart);
        }
    }

    /**
     * Calculate totals for the given cart array.
     */
    public function calculateTotals(array $cart): array
    {
        $subtotal = array_sum(array_map(fn($item) => $item['price'] * $item['quantity'], $cart));

        ['shipping' => $shipping, 'freeThreshold' => $freeThreshold] = SystemSetting::calculateShipping($subtotal);

        $discount = 0.00;
        $total    = $subtotal + $shipping - $discount;

        return compact('subtotal', 'shipping', 'discount', 'total', 'freeThreshold');
    }

    /**
     * Clear the entire cart (session + DB).
     */
    public function clearCart(): void
    {
        if (Auth::check()) {
            $userCart = Cart::where('user_id', Auth::id())->first();

            if ($userCart) {
                $userCart->items()->delete();
                $userCart->delete();
            }
        }

        session()->forget('cart');
    }

    /**
     * Load cart items from the database for the current authenticated user.
     */
    private function loadCartFromDb(): array
    {
        $userCart = Cart::with('items.book')->where('user_id', Auth::id())->first();

        if (!$userCart) {
            return [];
        }

        $cart = [];

        foreach ($userCart->items as $item) {
            $cart[$item->book_id] = [
                'id'       => $item->book_id,
                'title'    => $item->book->title,
                'price'    => $item->book->price,
                'image'    => $item->book->image,
                'quantity' => $item->quantity,
            ];
        }

        return $cart;
    }
}
