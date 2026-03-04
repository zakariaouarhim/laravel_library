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

        return view('Dashbord_Admin.reports', compact(
            'from', 'to', 'summary', 'totalBooksSold',
            'topBooks', 'topCustomers', 'ordersByStatus',
            'revenueByPayment', 'monthlySales'
        ));
    }
}
