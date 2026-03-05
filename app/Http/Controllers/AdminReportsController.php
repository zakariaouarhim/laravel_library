<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderDetail;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminReportsController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->input('date_from', Carbon::now()->startOfMonth()->toDateString());
        $to   = $request->input('date_to', Carbon::now()->toDateString());

        $startDate = Carbon::parse($from)->startOfDay();
        $endDate   = Carbon::parse($to)->endOfDay();

        // Summary stats
        $summary = Order::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('COUNT(*) as total_orders, COALESCE(SUM(total_price), 0) as total_revenue, COALESCE(AVG(total_price), 0) as avg_order_value')
            ->first();

        // Total books sold
        $totalBooksSold = OrderDetail::whereHas('order', function ($q) use ($startDate, $endDate) {
            $q->whereBetween('created_at', [$startDate, $endDate]);
        })->sum('quantity');

        // Top 10 best-selling books
        $topBooks = OrderDetail::select('book_id', DB::raw('SUM(quantity) as total_sold'), DB::raw('SUM(price * quantity) as total_revenue'))
            ->whereHas('order', fn($q) => $q->whereBetween('created_at', [$startDate, $endDate]))
            ->groupBy('book_id')
            ->orderByDesc('total_sold')
            ->limit(10)
            ->with('book:id,title,image')
            ->get();

        // Top 10 customers by spend
        $topCustomers = Order::select('user_id', DB::raw('COUNT(*) as order_count'), DB::raw('SUM(total_price) as total_spent'))
            ->whereNotNull('user_id')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('user_id')
            ->orderByDesc('total_spent')
            ->limit(10)
            ->with('user:id,name,email')
            ->get();

        // Orders by status
        $ordersByStatus = Order::whereBetween('created_at', [$startDate, $endDate])
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        // Revenue by payment method
        $revenueByPayment = Order::whereBetween('created_at', [$startDate, $endDate])
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_price) as total'))
            ->groupBy('payment_method')
            ->get();

        // Monthly chart data
        $monthlySales = Order::whereBetween('created_at', [$startDate, $endDate])
            ->select(DB::raw('MONTH(created_at) as month'), DB::raw('SUM(total_price) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Revenue by city (via checkout_details)
        $revenueByCity = DB::table('orders')
            ->join('checkout_details', 'orders.id', '=', 'checkout_details.order_id')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->whereNotNull('checkout_details.city')
            ->where('checkout_details.city', '!=', '')
            ->select('checkout_details.city', DB::raw('COUNT(*) as order_count'), DB::raw('SUM(orders.total_price) as revenue'))
            ->groupBy('checkout_details.city')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        // Daily trends
        $dailyTrends = Order::whereBetween('created_at', [$startDate, $endDate])
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as orders'), DB::raw('SUM(total_price) as revenue'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return view('Dashbord_Admin.reports', compact(
            'from', 'to', 'summary', 'totalBooksSold',
            'topBooks', 'topCustomers', 'ordersByStatus',
            'revenueByPayment', 'monthlySales',
            'revenueByCity', 'dailyTrends'
        ));
    }

    public function export(Request $request)
    {
        $from = $request->input('date_from', Carbon::now()->startOfMonth()->toDateString());
        $to   = $request->input('date_to', Carbon::now()->toDateString());

        $startDate = Carbon::parse($from)->startOfDay();
        $endDate   = Carbon::parse($to)->endOfDay();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="orders-report-' . $from . '-to-' . $to . '.csv"',
        ];

        return response()->stream(function () use ($startDate, $endDate) {
            $handle = fopen('php://output', 'w');
            // UTF-8 BOM for Excel Arabic support
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, ['رقم الطلب', 'التاريخ', 'العميل', 'المدينة', 'الحالة', 'طريقة الدفع', 'المبلغ']);

            Order::with('checkoutDetail')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->orderBy('created_at', 'desc')
                ->chunk(200, function ($orders) use ($handle) {
                    foreach ($orders as $order) {
                        fputcsv($handle, [
                            $order->id,
                            $order->created_at->format('Y-m-d'),
                            $order->checkoutDetail->full_name ?? '',
                            $order->checkoutDetail->city ?? '',
                            Order::STATUS_LABELS[$order->status] ?? $order->status,
                            Order::PAYMENT_LABELS[$order->payment_method] ?? $order->payment_method,
                            $order->total_price,
                        ]);
                    }
                });
            fclose($handle);
        }, 200, $headers);
    }
}
