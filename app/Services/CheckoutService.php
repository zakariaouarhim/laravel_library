<?php

namespace App\Services;

use App\Jobs\NotifyAdminOrderJob;
use App\Jobs\UpdateInterestScoresJob;
use App\Mail\OrderConfirmationMail;
use App\Models\Book;
use App\Models\CheckoutDetail;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderStatusHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CheckoutService
{
    public function __construct(
        private CartService $cartService,
    ) {}

    /**
     * Validate that all cart items are in stock.
     * Throws \Exception if any item is out of stock.
     */
    public function validateStock(array $cart): void
    {
        $outOfStock = [];
        $books = Book::whereIn('id', array_keys($cart))->get()->keyBy('id');

        foreach ($cart as $id => $item) {
            $book = $books->get($id);

            if (!$book) {
                $outOfStock[] = $item['title'] . ' (لم يعد متوفراً)';
            } elseif ($book->quantity < $item['quantity']) {
                $outOfStock[] = $item['title'] . ' (المتوفر: ' . $book->quantity . ')';
            }
        }

        if (!empty($outOfStock)) {
            throw new \Exception('بعض المنتجات غير متوفرة بالكمية المطلوبة: ' . implode('، ', $outOfStock));
        }
    }

    /**
     * Resolve the applied coupon from session and calculate the discount.
     *
     * @return array{discount: float, couponCode: string|null}
     */
    public function resolveDiscount(float $subtotal): array
    {
        $discount = 0.00;
        $couponCode = null;

        $sessionCoupon = session('applied_coupon');

        if ($sessionCoupon) {
            $coupon = Coupon::where('code', $sessionCoupon['code'])->first();

            if ($coupon && $coupon->isValidFor($subtotal)) {
                $discount = $coupon->discountFor($subtotal);
                $couponCode = $coupon->code;
            }
        }

        return ['discount' => $discount, 'couponCode' => $couponCode];
    }

    /**
     * Create the order inside a DB transaction.
     * Decrements stock, records order details and status history, increments coupon usage.
     */
    public function createOrder(array $cart, array $validated, float $total, float $subtotal, float $shipping, float $discount, ?string $couponCode): Order
    {
        $order = DB::transaction(function () use ($cart, $validated, $total, $subtotal, $shipping, $discount, $couponCode) {
            // Lock all cart rows in deterministic order (by id) to prevent deadlocks
            // between concurrent carts that overlap on the same books.
            $lockedBooks = Book::whereIn('id', array_keys($cart))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $outOfStock = [];
            foreach ($cart as $id => $item) {
                $book = $lockedBooks->get($id);
                if (!$book) {
                    $outOfStock[] = $item['title'] . ' (لم يعد متوفراً)';
                } elseif ($book->quantity < $item['quantity']) {
                    $outOfStock[] = $item['title'] . ' (المتوفر: ' . $book->quantity . ')';
                }
            }
            if (!empty($outOfStock)) {
                throw new \Exception('بعض المنتجات غير متوفرة بالكمية المطلوبة: ' . implode('، ', $outOfStock));
            }

            $order = Order::create([
                'user_id'          => auth()->check() ? auth()->id() : null,
                'status'           => 'pending',
                'total_price'      => $total,
                'shipping_address' => $validated['address'] . ', ' . $validated['city'],
                'billing_address'  => $validated['address'] . ', ' . $validated['city'],
                'payment_method'   => $validated['payment_method'],
                'tracking_number'  => 'TR-' . Str::upper(Str::random(10)),
                'management_token' => Str::random(64),
            ]);

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status'   => 'pending',
            ]);

            CheckoutDetail::create([
                'order_id'       => $order->id,
                'full_name'      => $validated['full_name'],
                'email'          => $validated['email'],
                'phone'          => $validated['phone'],
                'address'        => $validated['address'],
                'city'           => $validated['city'],
                'notes'          => $validated['notes'] ?? null,
                'payment_method' => $validated['payment_method'],
                'subtotal'       => $subtotal,
                'shipping'       => $shipping,
                'discount'       => $discount,
                'total'          => $total,
                'cart_items'     => json_encode($cart),
                'status'         => 'pending',
            ]);

            foreach ($cart as $id => $item) {
                OrderDetail::create([
                    'order_id' => $order->id,
                    'book_id'  => $id,
                    'quantity' => $item['quantity'],
                    'price'    => $item['price'],
                ]);

                $updated = Book::where('id', $id)
                    ->where('quantity', '>=', $item['quantity'])
                    ->decrement('quantity', $item['quantity']);

                if (!$updated) {
                    throw new \Exception('الكتاب "' . $item['title'] . '" لم يعد متوفراً بالكمية المطلوبة');
                }
            }

            if ($couponCode) {
                $coupon = Coupon::where('code', $couponCode)
                    ->lockForUpdate()
                    ->first();

                if (!$coupon || !$coupon->isValidFor($subtotal)) {
                    throw new \Exception('انتهت صلاحية كود الخصم أو تم استخدامه. يرجى المحاولة بدون كوبون.');
                }

                $coupon->increment('used_count');
            }

            return $order;
        });

        // Score purchase signals async — only for authenticated users.
        // Dispatched outside the transaction so the job sees a committed order.
        if ($order->user_id) {
            UpdateInterestScoresJob::dispatch($order->id);
        }

        // Notify the shop owner via Telegram on every new order (guests included).
        NotifyAdminOrderJob::dispatch($order->id);

        return $order;
    }

    /**
     * Queue an order-confirmation email (no-op if email is empty).
     */
    public function sendOrderConfirmation(Order $order, string $email, string $fullName): void
    {
        if (empty($email)) {
            return;
        }

        try {
            $manageUrl = url('/order/manage?token=' . $order->management_token);
            Mail::to($email)->queue(new OrderConfirmationMail($order, $fullName, $manageUrl));
        } catch (\Exception $e) {
            Log::error('Failed to queue order confirmation email', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Validate a coupon code against a subtotal.
     *
     * @return array{success: bool, message: string, discount?: float, type?: string, value?: mixed}
     */
    public function validateCoupon(string $code, float $subtotal): array
    {
        $code = strtoupper(trim($code));

        if (empty($code)) {
            return ['success' => false, 'message' => 'يرجى إدخال كود الخصم'];
        }

        $coupon = Coupon::where('code', $code)->first();

        if (!$coupon) {
            return ['success' => false, 'message' => 'كود الخصم غير صحيح'];
        }

        if (!$coupon->is_active) {
            return ['success' => false, 'message' => 'هذا الكوبون غير مفعّل'];
        }

        if ($coupon->expires_at && $coupon->expires_at->isPast()) {
            return ['success' => false, 'message' => 'انتهت صلاحية هذا الكوبون'];
        }

        if ($coupon->max_uses !== null && $coupon->used_count >= $coupon->max_uses) {
            return ['success' => false, 'message' => 'تجاوز هذا الكوبون الحد الأقصى للاستخدام'];
        }

        if ($subtotal < $coupon->min_order_amount) {
            $min = number_format($coupon->min_order_amount, 2);
            return ['success' => false, 'message' => "الحد الأدنى للطلب لاستخدام هذا الكوبون هو {$min} د.م"];
        }

        $discount = $coupon->discountFor($subtotal);

        session(['applied_coupon' => ['code' => $coupon->code]]);

        return [
            'success'  => true,
            'message'  => 'تم تطبيق الكوبون بنجاح!',
            'discount' => $discount,
            'type'     => $coupon->type,
            'value'    => $coupon->value,
        ];
    }
}
