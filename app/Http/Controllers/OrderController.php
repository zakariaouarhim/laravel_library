<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CheckoutDetail;
use App\Models\Order;
 


class OrderController extends Controller
{
    
    public function index(Request $request)
    {
        $query = Order::with(['orderDetails.book', 'checkoutDetail']);
        
        // Search by order ID or tracking number
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where('id', 'like', '%' . $search . '%')
                  ->orWhere('tracking_number', 'like', '%' . $search . '%');
        }
        
        // Filter by status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }
        
        $orders = $query->latest()->paginate(15);
        
        // Count orders by status
        $pendingCount = Order::where('status', 'pending')->count();
        $processingCount = Order::where('status', 'processing')->count();
        $deliveredCount = Order::where('status', 'delivered')->count();
        $cancelledCount = Order::where('status', 'cancelled')->count();
        
        return view('Dashbord_Admin.orders', compact(
            'orders',
            'pendingCount',
            'processingCount',
            'deliveredCount',
            'cancelledCount'
        ));
    }

    /**
     * Show a single order (JSON response for modal)
     */
    public function show($id)
    {
        $order = Order::with(['orderDetails.book', 'checkoutDetail'])->findOrFail($id);
        
        return response()->json($order);
    }

    /**
     * Show the edit form for an order
     */
    public function edit($id)
    {
        $order = Order::with(['orderDetails.book', 'checkoutDetail'])->findOrFail($id);
        
        return view('Dashbord_Admin.editorder', compact('order'));
    }

    /**
     * Update order status
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled,Failed,Refunded,returned'
        ]);

        $order = Order::findOrFail($id);
        $order->update(['status' => $request->status]);

        // If AJAX request, return JSON
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم تحديث حالة الطلب بنجاح',
                'order' => $order
            ]);
        }

        return redirect()->route('admin.orders.index')->with('success', 'تم تحديث حالة الطلب بنجاح');
    }

    /**
     * Store order (if needed for creating new orders)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled,Failed,Refunded,returned',
            'total_price' => 'required|numeric',
            'payment_method' => 'required|in:cod,credit_card',
            'shipping_address' => 'required|string',
            'tracking_number' => 'nullable|unique:orders,tracking_number'
        ]);

        Order::create($validated);

        return redirect()->route('admin.orders.index')->with('success', 'تم إنشاء الطلب بنجاح');
    }

    /**
     * Delete an order (soft delete recommended)
     */
    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        $order->delete();

        return redirect()->route('admin.orders.index')->with('success', 'تم حذف الطلب بنجاح');
    }
    /*public function index()
    {
        return view('Dashbord_Admin.orders');
    }*/

}
