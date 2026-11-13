<?php

namespace App\Services;

use App\Models\Book;
use App\Models\Order;
use App\Models\OrderDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardReportService
{
    /**
     * Orders that never produced (or gave back) money — excluded from revenue.
     */
    private const LOST_STATUSES = ['cancelled', 'failed', 'refunded', 'returned'];

    /**
     * Get all order stats for the admin dashboard (single query).
     */
    public function getOrderStats(): object
    {
        return Order::selectRaw("
            COUNT(*) as total,
            SUM(CASE WHEN status NOT IN ('cancelled','failed','refunded','returned') THEN total_price ELSE 0 END) as revenue,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered,
            SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
        ")->first();
    }

    /**
     * Get month-over-month comparison percentages.
     *
     * @return array{ordersIncrease: int, revenueIncrease: int, pendingDecrease: int, deliveredIncrease: int}
     */
    public function getMonthOverMonth(): array
    {
        $startThisMonth = Carbon::now()->startOfMonth();
        $startLastMonth = Carbon::now()->subMonth()->startOfMonth();
        $endLastMonth   = Carbon::now()->subMonth()->endOfMonth();

        $m = Order::selectRaw("
            SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as orders_this,
            SUM(CASE WHEN created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as orders_last,
            SUM(CASE WHEN status NOT IN ('cancelled','failed','refunded','returned') AND created_at >= ? THEN total_price ELSE 0 END) as revenue_this,
            SUM(CASE WHEN status NOT IN ('cancelled','failed','refunded','returned') AND created_at BETWEEN ? AND ? THEN total_price ELSE 0 END) as revenue_last,
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

        return [
            'ordersIncrease'    => $this->percentChange($m->orders_this, $m->orders_last),
            'revenueIncrease'   => $this->percentChange($m->revenue_this, $m->revenue_last),
            'pendingDecrease'   => $m->pending_last > 0
                ? round((($m->pending_last - $m->pending_this) / $m->pending_last) * 100)
                : 0,
            'deliveredIncrease' => $this->percentChange($m->delivered_this, $m->delivered_last),
        ];
    }

    /**
     * Get revenue chart data (weekly, monthly, yearly).
     *
     * @return array{weekly: \Illuminate\Support\Collection, monthly: \Illuminate\Support\Collection, yearly: \Illuminate\Support\Collection}
     */
    public function getRevenueCharts(): array
    {
        $weekly = Order::select(
                DB::raw('DAYOFWEEK(created_at) as day'),
                DB::raw('SUM(total_price) as total')
            )
            ->whereNotIn('status', self::LOST_STATUSES)
            ->where('created_at', '>=', Carbon::now()->startOfWeek())
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $monthly = Order::select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('SUM(total_price) as total')
            )
            ->whereNotIn('status', self::LOST_STATUSES)
            ->whereYear('created_at', Carbon::now()->year)
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Latest 5 years, then re-sorted ascending for display.
        $yearly = Order::select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('SUM(total_price) as total')
            )
            ->whereNotIn('status', self::LOST_STATUSES)
            ->groupBy('year')
            ->orderByDesc('year')
            ->limit(5)
            ->get()
            ->sortBy('year')
            ->values();

        return compact('weekly', 'monthly', 'yearly');
    }

    /**
     * Get low stock books for the dashboard alert.
     */
    public function getLowStockBooks(int $threshold = 5, int $limit = 10)
    {
        // Books a customer just bought (stock decreased by a sale) come first:
        // checkout writes order_details, so the latest order_details.created_at
        // per book is the "last sold" moment. MySQL sorts NULLs last on DESC,
        // so never-sold books sink to the bottom, then lowest quantity first.
        return Book::where('quantity', '<', $threshold)
            ->select(['id', 'title', 'quantity', 'image'])
            ->addSelect(['last_sold_at' => OrderDetail::select('created_at')
                ->whereColumn('order_details.book_id', 'books.id')
                ->latest()
                ->limit(1)])
            ->orderByDesc('last_sold_at')
            ->orderBy('quantity')
            ->limit($limit)
            ->get();
    }

    /**
     * Get full report data for a date range.
     */
    public function getReportData(Carbon $startDate, Carbon $endDate): array
    {
        // total_orders counts everything (orders received); revenue and averages
        // only count orders that kept their money (same rule as the dashboard).
        $summary = Order::whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw("COUNT(*) as total_orders,
                COALESCE(SUM(CASE WHEN status NOT IN ('cancelled','failed','refunded','returned') THEN total_price END), 0) as total_revenue,
                COALESCE(AVG(CASE WHEN status NOT IN ('cancelled','failed','refunded','returned') THEN total_price END), 0) as avg_order_value")
            ->first();

        $totalBooksSold = OrderDetail::whereHas('order', fn($q) => $q->whereBetween('created_at', [$startDate, $endDate])
                ->whereNotIn('status', self::LOST_STATUSES))
            ->sum('quantity');

        $topBooks = OrderDetail::select('book_id', DB::raw('SUM(quantity) as total_sold'), DB::raw('SUM(price * quantity) as total_revenue'))
            ->whereHas('order', fn($q) => $q->whereBetween('created_at', [$startDate, $endDate])
                ->whereNotIn('status', self::LOST_STATUSES))
            ->groupBy('book_id')
            ->orderByDesc('total_sold')
            ->limit(10)
            ->with('book:id,title,image')
            ->get();

        $topCustomers = Order::select('user_id', DB::raw('COUNT(*) as order_count'), DB::raw('SUM(total_price) as total_spent'))
            ->whereNotNull('user_id')
            ->whereNotIn('status', self::LOST_STATUSES)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('user_id')
            ->orderByDesc('total_spent')
            ->limit(10)
            ->with('user:id,name,email')
            ->get();

        // keyBy on the raw enum throws ("enum could not be converted to string"),
        // which 500'd the whole reports page — key by the backing value instead.
        $ordersByStatus = Order::whereBetween('created_at', [$startDate, $endDate])
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get()
            ->keyBy(fn ($row) => $row->status->value);

        $revenueByPayment = Order::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotIn('status', self::LOST_STATUSES)
            ->select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(total_price) as total'))
            ->groupBy('payment_method')
            ->get();

        // Group by year AND month so a range spanning years doesn't merge e.g. both Januaries.
        $monthlySales = Order::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotIn('status', self::LOST_STATUSES)
            ->select(DB::raw('YEAR(created_at) as year'), DB::raw('MONTH(created_at) as month'), DB::raw('SUM(total_price) as total'), DB::raw('COUNT(*) as count'))
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get();

        $revenueByCity = DB::table('orders')
            ->join('checkout_details', 'orders.id', '=', 'checkout_details.order_id')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->whereNotIn('orders.status', self::LOST_STATUSES)
            ->whereNotNull('checkout_details.city')
            ->where('checkout_details.city', '!=', '')
            ->select('checkout_details.city', DB::raw('COUNT(*) as order_count'), DB::raw('SUM(orders.total_price) as revenue'))
            ->groupBy('checkout_details.city')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        $dailyTrends = Order::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotIn('status', self::LOST_STATUSES)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as orders'), DB::raw('SUM(total_price) as revenue'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return compact(
            'summary', 'totalBooksSold', 'topBooks', 'topCustomers',
            'ordersByStatus', 'revenueByPayment', 'monthlySales',
            'revenueByCity', 'dailyTrends'
        );
    }

    /**
     * Stream CSV export of orders within a date range.
     */
    public function streamOrdersCsv(Carbon $startDate, Carbon $endDate): \Closure
    {
        return function () use ($startDate, $endDate) {
            $handle = fopen('php://output', 'w');
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
                            $order->status->label(),
                            Order::PAYMENT_LABELS[$order->payment_method] ?? $order->payment_method,
                            $order->total_price,
                        ]);
                    }
                });

            fclose($handle);
        };
    }

    private function percentChange($current, $previous): int
    {
        if ($previous > 0) {
            return round((($current - $previous) / $previous) * 100);
        }

        return $current > 0 ? 100 : 0;
    }
}
