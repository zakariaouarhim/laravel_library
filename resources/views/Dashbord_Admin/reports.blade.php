<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>التقارير</title>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.rtl.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">

    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sidebardaschboard.css') }}">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .date-filter-form {
            display: flex;
            gap: 1rem;
            align-items: flex-end;
            flex-wrap: wrap;
            background: #fff;
            padding: 1.25rem 1.5rem;
            border-radius: 14px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            margin-bottom: 1.5rem;
        }
        .date-filter-form label {
            font-weight: 600;
            font-size: 0.85rem;
            color: #34495e;
            margin-bottom: 0.3rem;
            display: block;
        }
        .date-filter-form input[type="date"] {
            border-radius: 10px;
            border: 1.5px solid #e0e0e0;
            padding: 0.5rem 0.75rem;
            font-family: 'Cairo', sans-serif;
        }
        .date-filter-form .btn-filter {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: #fff;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 10px;
            font-weight: 700;
            font-family: 'Cairo', sans-serif;
            cursor: pointer;
        }
        .date-filter-form .btn-filter:hover {
            background: linear-gradient(135deg, #2980b9, #2471a3);
        }
        .report-table-card {
            background: #fff;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        }
        .report-table-card h4 {
            font-size: 1rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 1rem;
            padding-bottom: 0.6rem;
            border-bottom: 2px solid #f0f0f0;
        }
        .report-table-card h4 i {
            margin-left: 0.5rem;
            color: #3498db;
        }
        .report-table-card .table {
            font-size: 0.9rem;
        }
        .report-table-card .table th {
            background: #f8f9fa;
            font-weight: 700;
            color: #2c3e50;
            border: none;
            padding: 0.75rem;
        }
        .report-table-card .table td {
            vertical-align: middle;
            padding: 0.65rem 0.75rem;
            border-color: #f0f0f0;
        }
        .rank-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: #3498db;
            color: #fff;
            font-weight: 700;
            font-size: 0.8rem;
        }
        .rank-badge.gold { background: #f39c12; }
        .rank-badge.silver { background: #95a5a6; }
        .rank-badge.bronze { background: #cd6155; }
        .book-thumb {
            width: 40px;
            height: 55px;
            object-fit: cover;
            border-radius: 6px;
            margin-left: 0.75rem;
        }
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #95a5a6;
        }
        .empty-state i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>

<div class="dashboard_layout">
<div class="container-fluid">
    <div class="row">
        @include('Dashbord_Admin.Sidebar')

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <div class="dashboard-header">
                <h1>
                    <i class="fas fa-chart-bar"></i>
                    التقارير
                </h1>
            </div>

            {{-- Date Filter --}}
            <form method="GET" action="{{ route('admin.reports.index') }}" class="date-filter-form">
                <div>
                    <label>من</label>
                    <input type="date" name="date_from" value="{{ $from }}">
                </div>
                <div>
                    <label>إلى</label>
                    <input type="date" name="date_to" value="{{ $to }}">
                </div>
                <button type="submit" class="btn-filter">
                    <i class="fas fa-filter me-1"></i>تصفية
                </button>
            </form>

            {{-- Summary Cards --}}
            <div class="stats-grid">
                <div class="stat-card orders">
                    <div class="stat-icon"><i class="fas fa-shopping-bag"></i></div>
                    <div class="stat-title">إجمالي الطلبات</div>
                    <div class="stat-value">{{ $summary->total_orders }}</div>
                </div>
                <div class="stat-card revenue">
                    <div class="stat-icon"><i class="fas fa-coins"></i></div>
                    <div class="stat-title">إجمالي الإيرادات</div>
                    <div class="stat-value">{{ number_format($summary->total_revenue, 2) }} د.م</div>
                </div>
                <div class="stat-card pending">
                    <div class="stat-icon"><i class="fas fa-calculator"></i></div>
                    <div class="stat-title">متوسط قيمة الطلب</div>
                    <div class="stat-value">{{ number_format($summary->avg_order_value, 2) }} د.م</div>
                </div>
                <div class="stat-card delivered">
                    <div class="stat-icon"><i class="fas fa-book"></i></div>
                    <div class="stat-title">الكتب المباعة</div>
                    <div class="stat-value">{{ $totalBooksSold }}</div>
                </div>
            </div>

            {{-- Charts --}}
            <div class="charts-section">
                <div class="chart-card">
                    <h4 class="chart-title">
                        <i class="fas fa-chart-line"></i>
                        الإيرادات الشهرية
                    </h4>
                    <div class="chart-container">
                        <canvas id="monthlyRevenueChart"></canvas>
                    </div>
                </div>
                <div class="chart-card">
                    <h4 class="chart-title">
                        <i class="fas fa-chart-pie"></i>
                        الطلبات حسب الحالة
                    </h4>
                    <div class="chart-container">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>

            {{-- Tables Row --}}
            <div class="row">
                {{-- Top Books --}}
                <div class="col-lg-6">
                    <div class="report-table-card">
                        <h4><i class="fas fa-trophy"></i>أكثر الكتب مبيعاً</h4>
                        @if($topBooks->isEmpty())
                            <div class="empty-state">
                                <i class="fas fa-inbox d-block"></i>
                                <p>لا توجد بيانات في هذه الفترة</p>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>الكتاب</th>
                                            <th>الكمية</th>
                                            <th>الإيرادات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($topBooks as $i => $item)
                                        <tr>
                                            <td>
                                                <span class="rank-badge {{ $i === 0 ? 'gold' : ($i === 1 ? 'silver' : ($i === 2 ? 'bronze' : '')) }}">
                                                    {{ $i + 1 }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($item->book)
                                                    <div class="d-flex align-items-center">
                                                        <img src="{{ asset($item->book->image ?? 'images/placeholder.jpg') }}" alt="" class="book-thumb">
                                                        <span>{{ Str::limit($item->book->title, 35) }}</span>
                                                    </div>
                                                @else
                                                    <span class="text-muted">كتاب محذوف</span>
                                                @endif
                                            </td>
                                            <td>{{ $item->total_sold }}</td>
                                            <td>{{ number_format($item->total_revenue, 2) }} د.م</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Top Customers --}}
                <div class="col-lg-6">
                    <div class="report-table-card">
                        <h4><i class="fas fa-users"></i>أفضل العملاء</h4>
                        @if($topCustomers->isEmpty())
                            <div class="empty-state">
                                <i class="fas fa-inbox d-block"></i>
                                <p>لا توجد بيانات في هذه الفترة</p>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>العميل</th>
                                            <th>الطلبات</th>
                                            <th>المبلغ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($topCustomers as $i => $cust)
                                        <tr>
                                            <td>
                                                <span class="rank-badge {{ $i === 0 ? 'gold' : ($i === 1 ? 'silver' : ($i === 2 ? 'bronze' : '')) }}">
                                                    {{ $i + 1 }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($cust->user)
                                                    <strong>{{ $cust->user->name }}</strong>
                                                    <br><small class="text-muted">{{ $cust->user->email }}</small>
                                                @else
                                                    <span class="text-muted">مستخدم محذوف</span>
                                                @endif
                                            </td>
                                            <td>{{ $cust->order_count }}</td>
                                            <td>{{ number_format($cust->total_spent, 2) }} د.م</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Revenue by Payment Method --}}
            <div class="report-table-card">
                <h4><i class="fas fa-credit-card"></i>الإيرادات حسب طريقة الدفع</h4>
                @if($revenueByPayment->isEmpty())
                    <div class="empty-state">
                        <i class="fas fa-inbox d-block"></i>
                        <p>لا توجد بيانات في هذه الفترة</p>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>طريقة الدفع</th>
                                    <th>عدد الطلبات</th>
                                    <th>إجمالي الإيرادات</th>
                                    <th>النسبة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $totalPaymentRevenue = $revenueByPayment->sum('total'); @endphp
                                @foreach($revenueByPayment as $pm)
                                <tr>
                                    <td>{{ \App\Models\Order::PAYMENT_LABELS[$pm->payment_method] ?? $pm->payment_method }}</td>
                                    <td>{{ $pm->count }}</td>
                                    <td>{{ number_format($pm->total, 2) }} د.م</td>
                                    <td>
                                        @if($totalPaymentRevenue > 0)
                                            {{ number_format(($pm->total / $totalPaymentRevenue) * 100, 1) }}%
                                        @else
                                            0%
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

        </main>
    </div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>

<script>
    const monthLabels = [
        'يناير', 'فبراير', 'مارس', 'أبريل',
        'ماي', 'يونيو', 'يوليو', 'غشت',
        'شتنبر', 'أكتوبر', 'نونبر', 'دجنبر'
    ];

    const chartFont = { family: "'Cairo', sans-serif" };

    // Monthly Revenue Chart
    const monthlySales = @json($monthlySales);
    const monthlyData = new Array(12).fill(0);
    monthlySales.forEach(item => { monthlyData[item.month - 1] = item.total; });

    new Chart(document.getElementById('monthlyRevenueChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: monthLabels,
            datasets: [{
                label: 'الإيرادات (د.م)',
                data: monthlyData,
                backgroundColor: 'rgba(52, 152, 219, 0.7)',
                borderColor: '#3498db',
                borderWidth: 1,
                borderRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { labels: { font: chartFont, color: '#2c3e50', padding: 16 } }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { font: chartFont, color: '#7f8c8d' },
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: {
                    ticks: { font: chartFont, color: '#7f8c8d' },
                    grid: { display: false }
                }
            }
        }
    });

    // Status Doughnut Chart
    const statusData = @json($ordersByStatus);
    const statusConfig = {
        'pending':    { label: 'قيد الانتظار', color: '#f39c12' },
        'processing': { label: 'قيد المعالجة', color: '#3498db' },
        'shipped':    { label: 'مشحون', color: '#8e44ad' },
        'delivered':  { label: 'تم التسليم', color: '#27ae60' },
        'cancelled':  { label: 'ملغي', color: '#e74c3c' },
        'Failed':     { label: 'فشل', color: '#c0392b' },
        'Refunded':   { label: 'مسترجع', color: '#7f8c8d' },
        'returned':   { label: 'مرتجع', color: '#d35400' }
    };

    const statusLabels = [];
    const statusValues = [];
    const statusColors = [];
    Object.keys(statusConfig).forEach(key => {
        if (statusData[key]) {
            statusLabels.push(statusConfig[key].label);
            statusValues.push(statusData[key].count);
            statusColors.push(statusConfig[key].color);
        }
    });

    new Chart(document.getElementById('statusChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: statusLabels,
            datasets: [{
                data: statusValues,
                backgroundColor: statusColors,
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { font: chartFont, color: '#2c3e50', padding: 16 }
                }
            }
        }
    });
</script>
</body>
</html>
