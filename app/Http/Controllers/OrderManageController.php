<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\ReturnRequest;

class OrderManageController extends Controller
{
    /**
     * Show the order management page (accessible via token, no auth required)
     */
    public function show(Request $request)
    {
        $token = $request->query('token');

        if (!$token) {
            return redirect()->route('index.page')->with('error', 'رابط غير صالح');
        }

        $order = Order::where('management_token', $token)
            ->with(['orderDetails.book', 'checkoutDetail', 'returnRequests'])
            ->first();

        if (!$order) {
            return redirect()->route('index.page')->with('error', 'الطلب غير موجود أو الرابط غير صالح');
        }

        // Check if order already has a pending/approved return request
        $hasActiveReturn = $order->returnRequests()
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        // Get existing return requests for this order
        $returnRequests = $order->returnRequests()->latest()->get();

        return view('order-manage', compact('order', 'hasActiveReturn', 'returnRequests'));
    }

    /**
     * Cancel order via token
     */
    public function cancel(Request $request)
    {
        $token = $request->input('token');

        $order = Order::where('management_token', $token)
            ->whereIn('status', ['pending', 'processing'])
            ->first();

        if (!$order) {
            return redirect()->back()->with('error', 'لا يمكن إلغاء هذا الطلب');
        }

        $order->update(['status' => 'cancelled']);

        return redirect()->route('order.manage', ['token' => $token])
            ->with('success', 'تم إلغاء الطلب بنجاح');
    }

    /**
     * Submit return request via token (works for both guests and authenticated users)
     */
    public function returnRequest(Request $request)
    {
        $validated = $request->validate([
            'token'  => 'required|string',
            'reason' => 'required|string|max:1000',
        ], [
            'reason.required' => 'يرجى كتابة سبب الإرجاع',
            'reason.max'      => 'سبب الإرجاع يجب ألا يتجاوز 1000 حرف',
        ]);

        $order = Order::where('management_token', $validated['token'])
            ->where('status', 'delivered')
            ->with('checkoutDetail')
            ->first();

        if (!$order) {
            return redirect()->back()->with('error', 'لا يمكن طلب إسترجاع لهذا الطلب');
        }

        // Check for duplicate
        $existingReturn = ReturnRequest::where('order_id', $order->id)
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($existingReturn) {
            return redirect()->back()->with('error', 'يوجد طلب إسترجاع قيد المعالجة لهذا الطلب بالفعل');
        }

        $paymentMethod = $order->payment_method ?? ($order->checkoutDetail ? $order->checkoutDetail->payment_method : 'cod');
        $guestEmail = $order->checkoutDetail ? $order->checkoutDetail->email : null;

        ReturnRequest::create([
            'order_id'       => $order->id,
            'user_id'        => $order->user_id,
            'status'         => 'pending',
            'reason'         => $validated['reason'],
            'payment_method' => $paymentMethod,
            'refund_amount'  => $order->total_price,
            'guest_email'    => $order->isGuestOrder() ? $guestEmail : null,
        ]);

        return redirect()->route('order.manage', ['token' => $validated['token']])
            ->with('success', 'تم إرسال طلب الإسترجاع بنجاح');
    }
}
