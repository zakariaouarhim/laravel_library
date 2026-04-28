<?php

namespace App\Http\Controllers;

use App\Http\Requests\Checkout\SubmitCheckoutRequest;
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

    public function submit(SubmitCheckoutRequest $request)
    {
        // Double-submit protection
        $sessionToken = session()->pull('checkout_token');
        if (!$sessionToken || $request->input('checkout_token') !== $sessionToken) {
            Log::warning('Checkout token mismatch — possible double submit', [
                'had_token' => (bool) $sessionToken,
            ]);
            return redirect()->route('cart.page')->with('error', 'تم تقديم الطلب بالفعل أو انتهت صلاحية الجلسة. يرجى المحاولة مرة أخرى.');
        }

        try {
            $validated = $request->validated();

            $cart = $this->cartService->loadCartForCheckout();
            if (empty($cart)) {
                Log::warning('Checkout: cart was empty at submit time');
                return redirect()->back()->with('error', 'السلة فارغة');
            }

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

            Log::info('Checkout success — redirecting to success page', ['order_id' => $order->id]);

            return redirect()->route('success', ['id' => $order->id, 'token' => $order->management_token])
                   ->with('success', 'تم إرسال طلبك بنجاح! سيتم التواصل معك قريباً.');

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Checkout validation failed', ['errors' => $e->errors()]);
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            Log::error('Checkout submission failed', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $userMessage = $e->getMessage() ?: 'حدث خطأ أثناء معالجة الطلب. يرجى المحاولة مرة أخرى.';
            return redirect()->back()->with('error', $userMessage);
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
        Log::info('Success page hit', ['order_id' => $orderId, 'token' => request('token')]);

        try {
            $order = Order::with('orderDetails.book')->findOrFail($orderId);
        } catch (\Exception $e) {
            Log::error('Success page error', ['order_id' => $orderId, 'error' => $e->getMessage()]);
            return redirect()->route('index.page')->with('error', 'الطلب غير موجود');
        }

        $ownsOrder = Auth::check() && Auth::user()->can('view', $order);

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
                \App\Enums\OrderStatus::Pending    => 25,
                \App\Enums\OrderStatus::Processing => 50,
                \App\Enums\OrderStatus::Shipped    => 75,
                \App\Enums\OrderStatus::Delivered  => 100,
                \App\Enums\OrderStatus::Cancelled,
                \App\Enums\OrderStatus::Failed,
                \App\Enums\OrderStatus::Refunded,
                \App\Enums\OrderStatus::Returned   => 0,
            };

            return view('trackmyorder', compact('order', 'progress'));
        }

        return redirect()->back()->with('error', 'الطلب غير موجود');
    }
}
