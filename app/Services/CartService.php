<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Offer;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CartService
{
    /** Session key holding "pick N for fixed price" offer selections. */
    private const OFFERS_KEY = 'cart_offers';

    // ===================== Offer groups =====================

    /**
     * Add an offer selection ("N books for fixed price") to the cart.
     * Validates exactly N eligible, in-stock books, distributes the fixed price
     * across them, and appends the group to the session. Returns all offer groups.
     *
     * @param  int[] $bookIds
     * @throws \RuntimeException on invalid selection
     */
    public function addOfferGroup(Offer $offer, array $bookIds): array
    {
        $bookIds = array_values(array_unique(array_map('intval', $bookIds)));

        // At least N books (a series/bundle unit can push the total past N).
        if (count($bookIds) < (int) $offer->quantity) {
            throw new \RuntimeException("يجب اختيار {$offer->quantity} كتب على الأقل لهذا العرض.");
        }

        // Only books that are actually eligible for this offer (hand-picked ∪ price rule).
        $eligibleIds = $offer->eligibleBookIds();
        if (array_diff($bookIds, $eligibleIds)) {
            throw new \RuntimeException('بعض الكتب المختارة غير متاحة ضمن هذا العرض.');
        }

        $books = Book::whereIn('id', $bookIds)->get(['id', 'title', 'image', 'price', 'quantity', 'slug']);
        if ($books->count() !== count($bookIds)) {
            throw new \RuntimeException('بعض الكتب لم تعد موجودة.');
        }

        $outOfStock = $books->filter(fn($b) => (int) $b->quantity < 1);
        if ($outOfStock->isNotEmpty()) {
            throw new \RuntimeException('بعض الكتب غير متوفرة في المخزون: ' . $outOfStock->pluck('title')->implode('، '));
        }

        $allocations = $this->allocatePrices($books, (float) $offer->fixed_price);

        $group = [
            'uid'         => 'offer_' . $offer->id . '_' . Str::lower(Str::random(6)),
            'offer_id'    => $offer->id,
            'title'       => $offer->title,
            'fixed_price' => round((float) $offer->fixed_price, 2),
            'quantity'    => (int) $offer->quantity,
            'books'       => $books->map(fn($b) => [
                'id'    => $b->id,
                'slug'  => $b->slug,
                'title' => $b->title,
                'image' => $b->image,
                'price' => $allocations[$b->id],
            ])->values()->all(),
        ];

        $groups = $this->loadOfferGroups();
        $groups[$group['uid']] = $group;
        session()->put(self::OFFERS_KEY, $groups);

        return $groups;
    }

    /** All offer groups currently in the cart, keyed by uid. */
    public function loadOfferGroups(): array
    {
        return session()->get(self::OFFERS_KEY, []);
    }

    /** Remove an offer group by its uid. Returns remaining groups. */
    public function removeOfferGroup(string $uid): array
    {
        $groups = $this->loadOfferGroups();
        unset($groups[$uid]);
        session()->put(self::OFFERS_KEY, $groups);

        return $groups;
    }

    /** Sum of all offer fixed prices. */
    public function offersSubtotal(): float
    {
        return round(array_sum(array_map(fn($g) => (float) $g['fixed_price'], $this->loadOfferGroups())), 2);
    }

    /** Total number of books locked inside offer groups (for the cart badge). */
    public function offersBookCount(): int
    {
        return array_sum(array_map(fn($g) => count($g['books']), $this->loadOfferGroups()));
    }

    /**
     * Distribute a fixed offer price across books proportional to their list price.
     * The last book absorbs the rounding remainder so the parts sum exactly to the
     * fixed price. Falls back to an equal split when all list prices are zero.
     *
     * @return array<int,float> book id => allocated price
     */
    private function allocatePrices($books, float $fixedPrice): array
    {
        $fixedPrice = round($fixedPrice, 2);
        $listTotal  = (float) $books->sum('price');
        $count      = $books->count();

        $alloc = [];
        $running = 0.0;
        $i = 0;

        foreach ($books as $b) {
            $i++;
            if ($i === $count) {
                $alloc[$b->id] = round($fixedPrice - $running, 2); // remainder to last
                break;
            }

            $share = $listTotal > 0
                ? round($fixedPrice * ((float) $b->price / $listTotal), 2)
                : round($fixedPrice / $count, 2);

            $alloc[$b->id] = $share;
            $running = round($running + $share, 2);
        }

        return $alloc;
    }
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
                'slug'     => $item['slug'] ?? null,
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
                    'slug'     => $book->slug,
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
            'slug'     => $book->slug,
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
        $booksSubtotal = array_sum(array_map(fn($item) => $item['price'] * $item['quantity'], $cart));
        $offersSubtotal = $this->offersSubtotal();
        $subtotal = round($booksSubtotal + $offersSubtotal, 2);

        ['shipping' => $shipping, 'freeThreshold' => $freeThreshold] = SystemSetting::calculateShipping($subtotal);

        $discount = 0.00;
        $total    = $subtotal + $shipping - $discount;

        $offerGroups = $this->loadOfferGroups();

        return compact('subtotal', 'shipping', 'discount', 'total', 'freeThreshold', 'offerGroups', 'offersSubtotal');
    }

    /**
     * Merge the guest session cart into the given user's DB cart.
     *
     * Called from the Login event listener so a guest who added books
     * before authenticating doesn't lose them. Sums quantities where
     * a book exists in both carts, capped at current stock. Clears
     * session('cart') after merging.
     */
    public function mergeGuestCartIntoDb(int $userId): void
    {
        $sessionCart = session()->get('cart', []);

        if (empty($sessionCart)) {
            return;
        }

        $userCart = Cart::firstOrCreate(['user_id' => $userId]);

        foreach ($sessionCart as $bookId => $item) {
            $book = Book::find($bookId);
            if (!$book) {
                continue;
            }

            $sessionQty = max(1, (int) ($item['quantity'] ?? 1));
            $stock      = (int) ($book->quantity ?? 0);

            $existingItem = CartItem::where('cart_id', $userCart->id)
                                    ->where('book_id', $book->id)
                                    ->first();

            if ($existingItem) {
                $merged = $existingItem->quantity + $sessionQty;
                $existingItem->update([
                    'quantity' => $stock > 0 ? min($merged, $stock) : $merged,
                ]);
            } else {
                CartItem::create([
                    'cart_id'  => $userCart->id,
                    'book_id'  => $book->id,
                    'quantity' => $stock > 0 ? min($sessionQty, $stock) : $sessionQty,
                ]);
            }
        }

        session()->forget('cart');
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
        session()->forget(self::OFFERS_KEY);
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
                'slug'     => $item->book->slug,
                'title'    => $item->book->title,
                'price'    => $item->book->price,
                'image'    => $item->book->image,
                'quantity' => $item->quantity,
            ];
        }

        return $cart;
    }
}
