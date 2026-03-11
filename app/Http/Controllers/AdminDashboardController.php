<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\DashboardReportService;

class AdminDashboardController extends Controller
{
    public function __construct(
        private DashboardReportService $reportService,
    ) {}

    public function dashboard()
    {
        $orderStats = $this->reportService->getOrderStats();

        $totalOrders      = $orderStats->total;
        $totalRevenue     = $orderStats->revenue ?? 0;
        $pendingOrders    = $orderStats->pending;
        $deliveredOrders  = $orderStats->delivered;
        $processingOrders = $orderStats->processing;
        $cancelledOrders  = $orderStats->cancelled;

        $recentOrders = Order::latest()->limit(5)->get();

        $monthOverMonth    = $this->reportService->getMonthOverMonth();
        $ordersIncrease    = $monthOverMonth['ordersIncrease'];
        $revenueIncrease   = $monthOverMonth['revenueIncrease'];
        $pendingDecrease   = $monthOverMonth['pendingDecrease'];
        $deliveredIncrease = $monthOverMonth['deliveredIncrease'];

        $charts         = $this->reportService->getRevenueCharts();
        $weeklyRevenue  = $charts['weekly'];
        $monthlyRevenue = $charts['monthly'];
        $yearlyRevenue  = $charts['yearly'];

        $lowStockBooks = $this->reportService->getLowStockBooks();

        return view('Dashbord_Admin.dashboard', compact(
            'totalOrders', 'totalRevenue', 'pendingOrders', 'deliveredOrders',
            'processingOrders', 'cancelledOrders', 'recentOrders',
            'ordersIncrease', 'revenueIncrease', 'pendingDecrease', 'deliveredIncrease',
            'weeklyRevenue', 'monthlyRevenue', 'yearlyRevenue',
            'lowStockBooks'
        ));
    }
}
