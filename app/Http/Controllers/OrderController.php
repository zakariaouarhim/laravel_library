<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService,
    ) {}

    public function index(Request $request)
    {
        $query = Order::with(['orderDetails.book', 'checkoutDetail']);

        if ($request->has('search') && $request->search) {
            $search = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $request->search);
            $query->where('id', 'like', '%' . $search . '%')
                  ->orWhere('tracking_number', 'like', '%' . $search . '%');
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $orders = $query->latest()->paginate(15);

        $statusCounts = $this->orderService->getStatusCounts();

        $pendingCount    = $statusCounts->get('pending', 0);
        $processingCount = $statusCounts->get('processing', 0);
        $deliveredCount  = $statusCounts->get('delivered', 0);
        $cancelledCount  = $statusCounts->get('cancelled', 0);

        return view('Dashbord_Admin.orders', compact(
            'orders', 'pendingCount', 'processingCount', 'deliveredCount', 'cancelledCount'
        ));
    }

    public function show($id)
    {
        $order = Order::with(['orderDetails.book', 'checkoutDetail'])->findOrFail($id);
        return response()->json($order);
    }

    public function edit($id)
    {
        $order = Order::with(['orderDetails.book', 'checkoutDetail'])->findOrFail($id);
        return view('Dashbord_Admin.editorder', compact('order'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled,failed,refunded,returned',
        ]);

        $order = Order::with('checkoutDetail')->findOrFail($id);

        try {
            $this->orderService->updateStatus($order, $request->status, $request->input('note'));
        } catch (\InvalidArgumentException $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }
            return redirect()->back()->with('error', $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم تحديث حالة الطلب بنجاح',
                'order'   => $order,
            ]);
        }

        return redirect()->route('admin.orders.index')->with('success', 'تم تحديث حالة الطلب بنجاح');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id'          => 'nullable|exists:users,id',
            'status'           => 'required|in:pending,processing,shipped,delivered,cancelled,failed,refunded,returned',
            'total_price'      => 'required|numeric',
            'payment_method'   => 'required|in:cod,credit_card',
            'shipping_address' => 'required|string',
            'tracking_number'  => 'nullable|unique:orders,tracking_number',
        ]);

        Order::create($validated);

        return redirect()->route('admin.orders.index')->with('success', 'تم إنشاء الطلب بنجاح');
    }

    public function myOrders(Request $request)
    {
        $userId = Auth::id();
        $status = $request->input('status');

        $query = Order::where('user_id', $userId)
            ->with(['orderDetails.book', 'checkoutDetail', 'statusHistory']);

        if ($status && $status !== 'all') {
            $query->where('status', $status);
        }

        $orders = $query->latest()->paginate(10)->appends($request->query());

        $counts   = $this->orderService->getStatusCounts($userId);
        $allCount = $counts->sum();

        $statusCounts = [
            'all'        => $allCount,
            'pending'    => $counts->get('pending', 0),
            'processing' => $counts->get('processing', 0),
            'shipped'    => $counts->get('shipped', 0),
            'delivered'  => $counts->get('delivered', 0),
            'cancelled'  => $counts->get('cancelled', 0),
        ];

        return view('my-orders', compact('orders', 'status', 'statusCounts'));
    }

    public function cancelOrder(Request $request, $id)
    {
        $order = Order::where('id', $id)
            ->where('user_id', Auth::id())
            ->whereIn('status', ['pending', 'processing'])
            ->firstOrFail();

        $order->update(['status' => 'cancelled']);

        return redirect()->route('my-orders.index')->with('success', 'تم إلغاء الطلب بنجاح');
    }
}
