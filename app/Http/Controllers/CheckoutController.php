<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\CheckoutDetail;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Book;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\OrderConfirmationMail;
use App\Models\OrderStatusHistory;

class CheckoutController extends Controller
{
    public function submit(Request $request)
    {
        Log::info('Checkout form submitted', $request->except(['card_number', 'cvv', 'expiry_date']));

        // Double-submit protection: verify and consume one-time token
        $sessionToken = session()->pull('checkout_token');
        if (!$sessionToken || $request->input('checkout_token') !== $sessionToken) {
            return redirect()->route('cart.page')->with('error', 'تم تقديم الطلب بالفعل أو انتهت صلاحية الجلسة. يرجى المحاولة مرة أخرى.');
        }

        try {
            // Validate the checkout form
            $validated = $request->validate([
                'full_name' => 'required|string|max:255',
                'email' => 'nullable|email|max:255',
                'phone' => 'required|string|regex:/^[0-9]{10}$/',
                'address' => 'required|string',
                'city' => 'required|string',
                'notes' => 'nullable|string|max:500',
                'payment_method' => 'required|in:cod,bank_transfer',
            ], [
                'full_name.required' => 'الاسم الكامل مطلوب',
                'email.email' => 'يرجى إدخال بريد إلكتروني صحيح',
                'phone.required' => 'رقم الهاتف مطلوب',
                'phone.regex' => 'يرجى إدخال رقم هاتف صحيح (10 أرقام)',
                'address.required' => 'العنوان مطلوب',
                'city.required' => 'المدينة مطلوبة',
                'payment_method.required' => 'طريقة الدفع مطلوبة',
            ]);

            // Auto-fill email from authenticated user if not provided
            if (empty($validated['email']) && Auth::check()) {
                $validated['email'] = Auth::user()->email;
            }

            // Get cart based on authentication status
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
                // For guests, re-fetch prices from DB — never trust session prices
                $sessionCart = session()->get('cart', []);
                foreach ($sessionCart as $bookId => $item) {
                    $book = \App\Models\Book::find($bookId);
                    if ($book) {
                        $cart[$bookId] = [
                            'id'       => $book->id,
                            'title'    => $book->title,
                            'price'    => $book->price,
                            'image'    => $book->image,
                            'quantity' => max(1, (int)($item['quantity'] ?? 1)),
                        ];
                    }
                }
            }
            
            if (empty($cart)) {
                Log::warning('Cart is empty', ['user_id' => Auth::id(), 'is_authenticated' => Auth::check()]);
                return redirect()->back()->with('error', 'السلة فارغة');
            }

            // Validate stock availability before proceeding
            $outOfStock = [];
            foreach ($cart as $id => $item) {
                $book = Book::find($id);
                if (!$book) {
                    $outOfStock[] = $item['title'] . ' (لم يعد متوفراً)';
                } elseif ($book->quantity < $item['quantity']) {
                    $outOfStock[] = $item['title'] . ' (المتوفر: ' . $book->quantity . ')';
                }
            }
            if (!empty($outOfStock)) {
                return redirect()->back()->with('error', 'بعض المنتجات غير متوفرة بالكمية المطلوبة: ' . implode('، ', $outOfStock));
            }

            // Calculate totals
            $subtotal = 0;
            foreach ($cart as $item) {
                $subtotal += $item['price'] * $item['quantity'];
            }

            ['shipping' => $shipping] = \App\Models\SystemSetting::calculateShipping($subtotal);
            $discount = 0.00;
            $appliedCouponCode = null;

            // Apply coupon from session (re-validate against DB)
            $sessionCoupon = session('applied_coupon');
            if ($sessionCoupon) {
                $coupon = Coupon::where('code', $sessionCoupon['code'])->first();
                if ($coupon && $coupon->isValidFor($subtotal)) {
                    $discount = $coupon->discountFor($subtotal);
                    $appliedCouponCode = $coupon->code;
                }
            }

            $total = $subtotal + $shipping - $discount;

            // Wrap all DB operations in a transaction to prevent partial order creation
            $order = DB::transaction(function () use ($cart, $validated, $total, $subtotal, $shipping, $discount, $appliedCouponCode) {
                // Create order record
                $order = Order::create([
                    'user_id' => auth()->check() ? auth()->id() : null,
                    'status' => 'pending',
                    'total_price' => $total,
                    'shipping_address' => $validated['address'] . ', ' . $validated['city'],
                    'billing_address' => $validated['address'] . ', ' . $validated['city'],
                    'payment_method' => $validated['payment_method'],
                    'tracking_number' => 'TR-' . Str::upper(Str::random(10)),
                    'management_token' => Str::random(64),
                ]);

                // Log initial status in history
                OrderStatusHistory::create([
                    'order_id' => $order->id,
                    'status' => 'pending',
                ]);

                // Create checkout record
                CheckoutDetail::create([
                    'order_id' => $order->id,
                    'full_name' => $validated['full_name'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone'],
                    'address' => $validated['address'],
                    'city' => $validated['city'],
                    'notes' => $validated['notes'] ?? null,
                    'payment_method' => $validated['payment_method'],
                    'subtotal' => $subtotal,
                    'shipping' => $shipping,
                    'discount' => $discount,
                    'total' => $total,
                    'cart_items' => json_encode($cart),
                    'status' => 'pending'
                ]);

                // Create order details and decrement stock atomically
                foreach ($cart as $id => $item) {
                    OrderDetail::create([
                        'order_id' => $order->id,
                        'book_id' => $id,
                        'quantity' => $item['quantity'],
                        'price' => $item['price']
                    ]);

                    // Decrement stock — use DB-level update to prevent overselling
                    $updated = Book::where('id', $id)
                        ->where('quantity', '>=', $item['quantity'])
                        ->decrement('quantity', $item['quantity']);

                    if (!$updated) {
                        throw new \Exception('الكتاب "' . $item['title'] . '" لم يعد متوفراً بالكمية المطلوبة');
                    }
                }

                // Increment coupon usage count
                if ($appliedCouponCode) {
                    Coupon::where('code', $appliedCouponCode)->increment('used_count');
                }

                return $order;
            });

            if ($appliedCouponCode) {
                session()->forget('applied_coupon');
            }

            // Queue order confirmation email (non-blocking — dispatched to DB queue)
            if (!empty($validated['email'])) {
                try {
                    $manageUrl = url('/order/manage?token=' . $order->management_token);
                    Mail::to($validated['email'])->queue(new OrderConfirmationMail($order, $validated['full_name'], $manageUrl));
                } catch (\Exception $e) {
                    Log::error('Failed to queue order confirmation email', ['error' => $e->getMessage()]);
                }
            }

            // Clear the cart after successful order
            if (Auth::check()) {
                // Clear database cart for authenticated users
                $userCart = Cart::where('user_id', Auth::id())->first();
                if ($userCart) {
                    $userCart->items()->delete();
                    $userCart->delete();
                }
            } else {
                // Clear session cart for guests
                session()->forget('cart');
            }

            return redirect()->route('success', ['id' => $order->id, 'token' => $order->management_token])
                   ->with('success', 'تم إرسال طلبك بنجاح! سيتم التواصل معك قريباً.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed', ['errors' => $e->errors()]);
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Checkout submission failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'حدث خطأ أثناء معالجة الطلب. يرجى المحاولة مرة أخرى.');
        }
    }

    public function applyCoupon(Request $request)
    {
        $code    = strtoupper(trim($request->input('code', '')));
        $subtotal = (float) $request->input('subtotal', 0);

        if (empty($code)) {
            return response()->json(['success' => false, 'message' => 'يرجى إدخال كود الخصم'], 422);
        }

        $coupon = Coupon::where('code', $code)->first();

        if (!$coupon) {
            return response()->json(['success' => false, 'message' => 'كود الخصم غير صحيح'], 422);
        }

        if (!$coupon->is_active) {
            return response()->json(['success' => false, 'message' => 'هذا الكوبون غير مفعّل'], 422);
        }

        if ($coupon->expires_at && $coupon->expires_at->isPast()) {
            return response()->json(['success' => false, 'message' => 'انتهت صلاحية هذا الكوبون'], 422);
        }

        if ($coupon->max_uses !== null && $coupon->used_count >= $coupon->max_uses) {
            return response()->json(['success' => false, 'message' => 'تجاوز هذا الكوبون الحد الأقصى للاستخدام'], 422);
        }

        if ($subtotal < $coupon->min_order_amount) {
            $min = number_format($coupon->min_order_amount, 2);
            return response()->json(['success' => false, 'message' => "الحد الأدنى للطلب لاستخدام هذا الكوبون هو {$min} د.م"], 422);
        }

        $discount = $coupon->discountFor($subtotal);

        // Store in session so submit() can re-validate
        session(['applied_coupon' => ['code' => $coupon->code]]);

        return response()->json([
            'success'  => true,
            'message'  => 'تم تطبيق الكوبون بنجاح!',
            'discount' => $discount,
            'type'     => $coupon->type,
            'value'    => $coupon->value,
        ]);
    }

    public function removeCoupon()
    {
        session()->forget('applied_coupon');
        return response()->json(['success' => true]);
    }

    public function success($orderId)
    {
        // Load the order — 404 if not found
        try {
            $order = Order::with('orderDetails.book')->findOrFail($orderId);
        } catch (\Exception $e) {
            Log::error('Success page error', ['order_id' => $orderId, 'error' => $e->getMessage()]);
            return redirect()->route('index.page')->with('error', 'الطلب غير موجود');
        }

        // Access rule: must either own the order OR supply the correct management token.
        // This covers: authenticated owners, guests with token, and prevents enumeration.
        $ownsOrder = Auth::check()
            && $order->user_id !== null
            && $order->user_id === Auth::id();

        $hasValidToken = request('token') !== null
            && request('token') === $order->management_token;

        if (!$ownsOrder && !$hasValidToken) {
            abort(403);
        }

        $manageUrl = url('/order/manage?token=' . $order->management_token);
        return view('success', compact('order', 'manageUrl'));
    }

    // Other methods remain the same...
    public function store(Request $request)
    {
        $cart = session()->get('cart', []);
        
        if ($request->has('quantity')) {
            foreach ($request->quantity as $id => $quantity) {
                if (isset($cart[$id]) && $quantity > 0) {
                    $cart[$id]['quantity'] = $quantity;
                }
            }
            session()->put('cart', $cart);
        }

        return redirect()->back()->with('success', 'تم تحديث السلة بنجاح');
    }

    public function updateQuantity(Request $request)
    {
        $cart = session()->get('cart', []);
        $itemId = $request->input('id');
        $quantity = $request->input('quantity');

        if (isset($cart[$itemId]) && $quantity > 0) {
            $cart[$itemId]['quantity'] = $quantity;
            session()->put('cart', $cart);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الكمية بنجاح',
                'cartCount' => array_sum(array_column($cart, 'quantity'))
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'حدث خطأ في تحديث الكمية'
        ]);
    }
    public function trackmyorder(Request $request){
        $input = trim($request->input('trackOrderInput'));
         

        $order = Order::where('tracking_number', $input)
            ->with(['checkoutDetail', 'orderDetails.book', 'statusHistory'])
            ->first();

        if ($order) {
           
            switch ($order->status) {
                case 'pending':
                     $progress=25;
                    break;
                case 'processing':
                     $progress=50;
                    break;
                case 'shipped':
                     $progress=75;
                    break;
                case 'delivered':
                     $progress=100;
                    break;
                case 'cancelled':
                     $progress=0;
                    break;
                case 'failed':
                     $progress=0;
                    break;
                case 'refunded':
                     $progress=0;
                    break;
                case 'returned':
                     $progress=0;
                    break;    
                    
                default:
                    $progress=25;
                }

            return view('trackmyorder', compact('order','progress'));

        }


        return redirect()->back()->with('error', 'الطلب غير موجود');
    }
}