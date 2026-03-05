<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ReturnRequest;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\ReturnRequestStatusMail;

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

        // Single query for all status counts instead of 5 separate queries
        $counts = ReturnRequest::where('user_id', $userId)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
                SUM(CASE WHEN status = 'refunded' THEN 1 ELSE 0 END) as refunded
            ")->first();

        $statusCounts = [
            'all'      => $counts->total,
            'pending'  => $counts->pending,
            'approved' => $counts->approved,
            'rejected' => $counts->rejected,
            'refunded' => $counts->refunded,
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

    /**
     * Admin: list all return requests
     */
    public function adminIndex(Request $request)
    {
        $request->validate([
            'search' => 'nullable|string|max:100',
            'status' => 'nullable|in:pending,approved,rejected,refunded',
        ]);

        $query = ReturnRequest::with(['order.user', 'order.orderDetails.book', 'order.checkoutDetail']);

        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', '%' . $search . '%')
                  ->orWhere('order_id', 'like', '%' . $search . '%');
            });
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $returnRequests = $query->latest()->paginate(15);

        // Single query for all status counts instead of 4 separate queries
        $adminCounts = ReturnRequest::selectRaw("
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
            SUM(CASE WHEN status = 'refunded' THEN 1 ELSE 0 END) as refunded
        ")->first();

        $pendingCount  = $adminCounts->pending;
        $approvedCount = $adminCounts->approved;
        $rejectedCount = $adminCounts->rejected;
        $refundedCount = $adminCounts->refunded;

        $pendingReturns = $pendingCount;

        return view('Dashbord_Admin.return_requests', compact(
            'returnRequests',
            'pendingCount',
            'approvedCount',
            'rejectedCount',
            'refundedCount',
            'pendingReturns'
        ));
    }

    /**
     * Admin: show a single return request (JSON for modal)
     */
    public function adminShow($id)
    {
        $returnRequest = ReturnRequest::with(['order.user', 'order.orderDetails.book', 'order.checkoutDetail'])
            ->findOrFail($id);

        return response()->json($returnRequest);
    }

    /**
     * Admin: update return request status and notes
     */
    public function adminUpdate(Request $request, $id)
    {
        $request->validate([
            'status'      => 'required|in:pending,approved,rejected,refunded',
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        $returnRequest = ReturnRequest::findOrFail($id);

        $oldStatus = $returnRequest->status;
        $newStatus = $request->status;

        $returnRequest->status = $newStatus;
        $returnRequest->admin_notes = $request->admin_notes;

        if ($oldStatus === 'pending' && $newStatus !== 'pending') {
            $returnRequest->resolved_at = now();
        }

        $returnRequest->save();

        if ($newStatus === 'refunded' && $returnRequest->order) {
            $returnRequest->order->update(['status' => 'Refunded']);
        }

        // Send status change email if status actually changed
        if ($oldStatus !== $newStatus) {
            try {
                $returnRequest->load('order.checkoutDetail');
                $order = $returnRequest->order;

                // Determine customer email and name
                $customerEmail = $returnRequest->guest_email;
                $customerName = null;

                if ($order && $order->checkoutDetail) {
                    $customerEmail = $customerEmail ?: $order->checkoutDetail->email;
                    $customerName = $order->checkoutDetail->full_name;
                }

                if (!$customerName && $order && $order->user) {
                    $customerName = $order->user->name;
                    $customerEmail = $customerEmail ?: $order->user->email;
                }

                if ($customerEmail) {
                    $statusMap = [
                        'pending'  => 'قيد المراجعة',
                        'approved' => 'مقبول',
                        'rejected' => 'مرفوض',
                        'refunded' => 'تم الاسترداد',
                    ];

                    $manageUrl = ($order && $order->management_token)
                        ? url('/order/manage?token=' . $order->management_token)
                        : null;

                    Mail::to($customerEmail)->send(new ReturnRequestStatusMail(
                        $returnRequest,
                        $customerName ?: 'عميلنا العزيز',
                        $statusMap[$newStatus] ?? $newStatus,
                        $manageUrl
                    ));

                    Log::info('Return request status email sent', ['email' => $customerEmail, 'status' => $newStatus]);
                }
            } catch (\Exception $e) {
                Log::error('Failed to send return request status email', ['error' => $e->getMessage()]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث طلب الإسترجاع بنجاح',
        ]);
    }
}
