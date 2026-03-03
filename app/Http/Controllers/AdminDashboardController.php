<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    public function dashboard()
    {
        // Single query for all order stats
        $orderStats = Order::selectRaw("
            COUNT(*) as total,
            SUM(total_price) as revenue,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered,
            SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
        ")->first();

        $totalOrders = $orderStats->total;
        $totalRevenue = $orderStats->revenue ?? 0;
        $pendingOrders = $orderStats->pending;
        $deliveredOrders = $orderStats->delivered;
        $processingOrders = $orderStats->processing;
        $cancelledOrders = $orderStats->cancelled;

        $recentOrders = Order::latest()->limit(5)->get();

        // Single query for month-over-month comparisons
        $startThisMonth = Carbon::now()->startOfMonth();
        $startLastMonth = Carbon::now()->subMonth()->startOfMonth();
        $endLastMonth = Carbon::now()->subMonth()->endOfMonth();

        $monthComparison = Order::selectRaw("
            SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as orders_this,
            SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as orders_last,
            SUM(CASE WHEN created_at >= ? THEN total_price ELSE 0 END) as revenue_this,
            SUM(CASE WHEN created_at BETWEEN ? AND ? THEN total_price ELSE 0 END) as revenue_last,
            SUM(CASE WHEN status = 'pending' AND created_at >= ? THEN 1 ELSE 0 END) as pending_this,
            SUM(CASE WHEN status = 'pending' AND created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as pending_last,
            SUM(CASE WHEN status = 'delivered' AND created_at >= ? THEN 1 ELSE 0 END) as delivered_this,
            SUM(CASE WHEN status = 'delivered' AND created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as delivered_last
        ", [
            $startThisMonth, $startLastMonth, $endLastMonth,
            $startThisMonth, $startLastMonth, $endLastMonth,
            $startThisMonth, $startLastMonth, $endLastMonth,
            $startThisMonth, $startLastMonth, $endLastMonth,
        ])->first();

        $ordersIncrease = $monthComparison->orders_last > 0
            ? round((($monthComparison->orders_this - $monthComparison->orders_last) / $monthComparison->orders_last) * 100)
            : 100;

        $revenueIncrease = $monthComparison->revenue_last > 0
            ? round((($monthComparison->revenue_this - $monthComparison->revenue_last) / $monthComparison->revenue_last) * 100)
            : 100;

        $pendingDecrease = $monthComparison->pending_last > 0
            ? round((($monthComparison->pending_last - $monthComparison->pending_this) / $monthComparison->pending_last) * 100)
            : 0;

        $deliveredIncrease = $monthComparison->delivered_last > 0
            ? round((($monthComparison->delivered_this - $monthComparison->delivered_last) / $monthComparison->delivered_last) * 100)
            : 100;

        // Weekly revenue
        $weeklyRevenue = Order::select(
                DB::raw('DAYOFWEEK(created_at) as day'),
                DB::raw('SUM(total_price) as total')
            )
            ->where('created_at', '>=', Carbon::now()->startOfWeek())
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        // Monthly revenue for current year
        $monthlyRevenue = Order::select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('SUM(total_price) as total')
            )
            ->whereYear('created_at', Carbon::now()->year)
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Yearly revenue
        $yearlyRevenue = Order::select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('SUM(total_price) as total')
            )
            ->groupBy('year')
            ->orderBy('year')
            ->limit(5)
            ->get();

        return view('Dashbord_Admin.dashboard', compact(
            'totalOrders', 'totalRevenue', 'pendingOrders', 'deliveredOrders',
            'processingOrders', 'cancelledOrders', 'recentOrders',
            'ordersIncrease', 'revenueIncrease', 'pendingDecrease', 'deliveredIncrease',
            'weeklyRevenue', 'monthlyRevenue', 'yearlyRevenue'
        ));
    }
}
