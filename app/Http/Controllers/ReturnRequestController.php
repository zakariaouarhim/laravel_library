<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ReturnRequest;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;

class ReturnRequestController extends Controller
{
    /**
     * Display authenticated user's return requests
     */
    public function index(Request $request)
    {
        $userId = Auth::id();
        $status = $request->input('status');

        $query = ReturnRequest::where('user_id', $userId)
            ->with(['order.orderDetails.book', 'order.checkoutDetail']);

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        $returnRequests = $query->latest()->paginate(10)->appends($request->query());

        $statusCounts = [
            'all'      => ReturnRequest::where('user_id', $userId)->count(),
            'pending'  => ReturnRequest::where('user_id', $userId)->where('status', 'pending')->count(),
            'approved' => ReturnRequest::where('user_id', $userId)->where('status', 'approved')->count(),
            'rejected' => ReturnRequest::where('user_id', $userId)->where('status', 'rejected')->count(),
            'refunded' => ReturnRequest::where('user_id', $userId)->where('status', 'refunded')->count(),
        ];

        // Delivered orders eligible for return (no pending/approved return request)
        $eligibleOrders = Order::where('user_id', $userId)
            ->where('status', 'delivered')
            ->whereDoesntHave('returnRequests', function ($q) {
                $q->whereIn('status', ['pending', 'approved']);
            })
            ->with('orderDetails.book')
            ->latest()
            ->get();

        return view('return-requests', compact('returnRequests', 'status', 'statusCounts', 'eligibleOrders'));
    }

    /**
     * Store a new return request
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'reason'   => 'required|string|max:1000',
        ], [
            'order_id.required' => 'يرجى اختيار الطلب',
            'order_id.exists'   => 'الطلب غير موجود',
            'reason.required'   => 'يرجى كتابة سبب الإرجاع',
            'reason.max'        => 'سبب الإرجاع يجب ألا يتجاوز 1000 حرف',
        ]);

        $userId = Auth::id();

        // Verify order belongs to user and is delivered
        $order = Order::where('id', $validated['order_id'])
            ->where('user_id', $userId)
            ->where('status', 'delivered')
            ->with('checkoutDetail')
            ->firstOrFail();

        // Check for duplicate
        $existingReturn = ReturnRequest::where('order_id', $order->id)
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($existingReturn) {
            return redirect()->back()->with('error', 'يوجد طلب إسترجاع قيد المعالجة لهذا الطلب بالفعل');
        }

        $paymentMethod = $order->payment_method ?? ($order->checkoutDetail ? $order->checkoutDetail->payment_method : 'cod');

        ReturnRequest::create([
            'order_id'       => $order->id,
            'user_id'        => $userId,
            'status'         => 'pending',
            'reason'         => $validated['reason'],
            'payment_method' => $paymentMethod,
            'refund_amount'  => $order->total_price,
        ]);

        return redirect()->route('return-requests.index')->with('success', 'تم إرسال طلب الإسترجاع بنجاح');
    }
}
