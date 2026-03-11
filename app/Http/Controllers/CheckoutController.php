<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Services\CartService;
use App\Services\CheckoutService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class CheckoutController extends Controller
{
    public function __construct(
        private CartService $cartService,
        private CheckoutService $checkoutService,
    ) {}

    public function submit(Request $request)
    {
        // Double-submit protection
        $sessionToken = session()->pull('checkout_token');
        if (!$sessionToken || $request->input('checkout_token') !== $sessionToken) {
            return redirect()->route('cart.page')->with('error', 'تم تقديم الطلب بالفعل أو انتهت صلاحية الجلسة. يرجى المحاولة مرة أخرى.');
        }

        try {
            $validated = $request->validate([
                'full_name'      => 'required|string|max:255',
                'email'          => 'nullable|email|max:255',
                'phone'          => 'required|string|regex:/^[0-9]{10}$/',
                'address'        => 'required|string',
                'city'           => 'required|string',
                'notes'          => 'nullable|string|max:500',
                'payment_method' => 'required|in:cod,bank_transfer',
            ], [
                'full_name.required'      => 'الاسم الكامل مطلوب',
                'email.email'             => 'يرجى إدخال بريد إلكتروني صحيح',
                'phone.required'          => 'رقم الهاتف مطلوب',
                'phone.regex'             => 'يرجى إدخال رقم هاتف صحيح (10 أرقام)',
                'address.required'        => 'العنوان مطلوب',
                'city.required'           => 'المدينة مطلوبة',
                'payment_method.required' => 'طريقة الدفع مطلوبة',
            ]);

            if (empty($validated['email']) && Auth::check()) {
                $validated['email'] = Auth::user()->email;
            }

            $cart = $this->cartService->loadCartForCheckout();
            if (empty($cart)) {
                return redirect()->back()->with('error', 'السلة فارغة');
            }

            $this->checkoutService->validateStock($cart);

            // Calculate totals
            $subtotal = array_sum(array_map(fn($item) => $item['price'] * $item['quantity'], $cart));
            ['shipping' => $shipping] = \App\Models\SystemSetting::calculateShipping($subtotal);
            ['discount' => $discount, 'couponCode' => $couponCode] = $this->checkoutService->resolveDiscount($subtotal);

            $total = $subtotal + $shipping - $discount;

            $order = $this->checkoutService->createOrder($cart, $validated, $total, $subtotal, $shipping, $discount, $couponCode);

            if ($couponCode) {
                session()->forget('applied_coupon');
            }

            $this->checkoutService->sendOrderConfirmation($order, $validated['email'] ?? '', $validated['full_name']);
            $this->cartService->clearCart();

            return redirect()->route('success', ['id' => $order->id, 'token' => $order->management_token])
                   ->with('success', 'تم إرسال طلبك بنجاح! سيتم التواصل معك قريباً.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Checkout submission failed', ['message' => $e->getMessage()]);
            return redirect()->back()->with('error', 'حدث خطأ أثناء معالجة الطلب. يرجى المحاولة مرة أخرى.');
        }
    }

    public function applyCoupon(Request $request)
    {
        $result = $this->checkoutService->validateCoupon(
            $request->input('code', ''),
            (float) $request->input('subtotal', 0)
        );

        $status = $result['success'] ? 200 : 422;
        return response()->json($result, $status);
    }

    public function removeCoupon()
    {
        session()->forget('applied_coupon');
        return response()->json(['success' => true]);
    }

    public function success($orderId)
    {
        try {
            $order = Order::with('orderDetails.book')->findOrFail($orderId);
        } catch (\Exception $e) {
            Log::error('Success page error', ['order_id' => $orderId, 'error' => $e->getMessage()]);
            return redirect()->route('index.page')->with('error', 'الطلب غير موجود');
        }

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

    public function trackmyorder(Request $request)
    {
        $input = trim($request->input('trackOrderInput'));

        $order = Order::where('tracking_number', $input)
            ->with(['checkoutDetail', 'orderDetails.book', 'statusHistory'])
            ->first();

        if ($order) {
            $progress = match ($order->status) {
                'pending'    => 25,
                'processing' => 50,
                'shipped'    => 75,
                'delivered'  => 100,
                'cancelled', 'failed', 'refunded', 'returned' => 0,
                default      => 25,
            };

            return view('trackmyorder', compact('order', 'progress'));
        }

        return redirect()->back()->with('error', 'الطلب غير موجود');
    }
}
