<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>لوحة القيادة</title>
    
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.rtl.min.css">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- Favicon -->
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
    
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sidebardaschboard.css') }}">
    
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    
    <div class="dashboard_layout">
    <div class="container-fluid">
        <div class="row">
            @include('Dashbord_Admin.Sidebar')

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <!-- Dashboard Header -->
                <div class="dashboard-header">
                    <h1>
                        <i class="fas fa-chart-line"></i>
                        لوحة القيادة
                    </h1>
                    <div class="header-actions">
                        <button class="btn-header btn-share">
                            <i class="fas fa-share-alt me-2"></i>مشاركة
                        </button>
                        <button class="btn-header btn-export" onclick="exportData()">
                            <i class="fas fa-download me-2"></i>تصدير
                        </button>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card orders">
                        <div class="stat-icon">
                            <i class="fas fa-shopping-bag"></i>
                        </div>
                        <div class="stat-title">إجمالي الطلبات</div>
                        <div class="stat-value">{{ $totalOrders ?? 0 }}</div>
                        <div class="stat-change positive">
                            <i class="fas fa-arrow-up"></i>
                            +{{ $ordersIncrease ?? 0 }}% هذا الشهر
                        </div>
                    </div>

                    <div class="stat-card revenue">
                        <div class="stat-icon">
                            <i class="fas fa-coins"></i>
                        </div>
                        <div class="stat-title">إجمالي الإيرادات</div>
                        <div class="stat-value">{{ number_format($totalRevenue ?? 0, 2) }} ر.س</div>
                        <div class="stat-change positive">
                            <i class="fas fa-arrow-up"></i>
                            +{{ $revenueIncrease ?? 0 }}% هذا الشهر
                        </div>
                    </div>

                    <div class="stat-card pending">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-title">طلبات قيد الانتظار</div>
                        <div class="stat-value">{{ $pendingOrders ?? 0 }}</div>
                        <div class="stat-change negative">
                            <i class="fas fa-arrow-down"></i>
                            -{{ $pendingDecrease ?? 0 }}% هذا الشهر
                        </div>
                    </div>

                    <div class="stat-card delivered">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-title">طلبات مكتملة</div>
                        <div class="stat-value">{{ $deliveredOrders ?? 0 }}</div>
                        <div class="stat-change positive">
                            <i class="fas fa-arrow-up"></i>
                            +{{ $deliveredIncrease ?? 0 }}% هذا الشهر
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="charts-section">
                    <div class="chart-card">
                        <h4 class="chart-title">
                            <i class="fas fa-chart-line"></i>
                            الإيرادات هذا <select name="date" id="date">
                            <option value="weeklyRevenue">الأسبوع</option>
                            <option value="monthlyRevenue">month</option>
                            <option value="yearlyRevenue">year</option>
                            </select>
                        </h4>
                        <div class="chart-container">
                            <canvas id="revenueChart"></canvas>
                        </div>
                    </div>

                    <div class="chart-card">
                        <h4 class="chart-title">
                            <i class="fas fa-chart-pie"></i>
                            توزيع حالات الطلبات
                        </h4>
                        <div class="chart-container">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="recent-orders">
                    <h3>
                        <i class="fas fa-list"></i>
                        الطلبات الأخيرة
                    </h3>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>رقم الطلب</th>
                                    <th>المبلغ</th>
                                    <th>طريقة الدفع</th>
                                    <th>الحالة</th>
                                    <th>التاريخ</th>
                                    <th>الإجراء</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentOrders ?? [] as $order)
                                <tr>
                                    <td><span class="order-id">#{{ $order->id }}</span></td>
                                    <td>{{ number_format($order->total_price, 2) }} ر.س</td>
                                    <td>
                                        @if($order->payment_method == 'cod')
                                            <span class="badge badge-custom badge-pending">الدفع عند الاستلام</span>
                                        @else
                                            <span class="badge badge-custom badge-processing">بطاقة ائتمان</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $statusMap = [
                                                'pending' => ['class' => 'status-pending', 'text' => 'قيد الانتظار'],
                                                'processing' => ['class' => 'status-processing', 'text' => 'قيد المعالجة'],
                                                'shipped' => ['class' => 'status-shipped', 'text' => 'مشحون'],
                                                'delivered' => ['class' => 'status-delivered', 'text' => 'تم التسليم'],
                                                'cancelled' => ['class' => 'status-cancelled', 'text' => 'ملغى'],
                                                'Failed' => ['class' => 'status-failed', 'text' => 'فشل'],
                                                'Refunded' => ['class' => 'status-refunded', 'text' => 'مسترجع'],
                                                'returned' => ['class' => 'status-returned', 'text' => 'مرتجع'],
                                            ];
                                            $status = $statusMap[$order->status] ?? ['class' => 'status-pending', 'text' => $order->status];
                                        @endphp
                                        <span class="status-badge {{ $status['class'] }}">{{ $status['text'] }}</span>
                                    </td>
                                    <td>{{ $order->created_at->format('d-m-Y') }}</td>
                                    <td>
                                        <button class="view-btn" onclick="viewOrder({{ $order->id }})">
                                            <i class="fas fa-eye"></i> عرض
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                                        <p>لا توجد طلبات</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>
  </div>
  <!-- Order Details Modal -->
<div class="modal fade" id="orderModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-box me-2"></i>تفاصيل الطلب
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalContent">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">جاري التحميل...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    
    <script src="{{ asset('js/dashboard.js') }}"></script> 
    <script>
        // Revenue Chart - Weekly
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        
        const weeklyRevenue = @json($weeklyRevenue);
        const monthlyRevenue = @json($monthlyRevenue);
        const yearlyRevenue = @json($yearlyRevenue);
        const revenueData = new Array(7).fill(0);
        const dateSelect = document.getElementById('date');
        const weeklabels = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
        const monthLabels = [
            'يناير', 'فبراير', 'مارس', 'أبريل',
            'ماي', 'يونيو', 'يوليو', 'غشت',
            'شتنبر', 'أكتوبر', 'نونبر', 'دجنبر'
        ];
        const yearLabels = yearlyRevenue.map(item => item.year);

        const monthlyData = new Array(12).fill(0);
        const yearlyData = yearlyRevenue.map(item => item.total);

        monthlyRevenue.forEach(item => {
            monthlyData[item.month - 1] = item.total;
        });

        weeklyRevenue.forEach(item => {
            revenueData[item.day - 1] = item.total;
        });
                    dateSelect.addEventListener('change', function () {
                        

                    switch (this.value) {

                    case 'weeklyRevenue':
                        revenueChart.data.labels = weeklabels;
                        revenueChart.data.datasets[0].data = revenueData;
                        break;

                    case 'monthlyRevenue':
                        revenueChart.data.labels = monthLabels;
                        revenueChart.data.datasets[0].data = monthlyData;
                        break;

                    case 'yearlyRevenue':
                        revenueChart.data.labels = yearLabels;
                        revenueChart.data.datasets[0].data = yearlyData;
                        break;
                }

                revenueChart.update();
            });
            
        const revenueChart = new Chart(revenueCtx, {
            
            type: 'line',
            data: {
                labels:weeklabels,
                datasets: [{
                    label: 'الإيرادات',
                    data: revenueData,
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#3498db',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        labels: {
                            font: { family: "'Cairo', sans-serif", size: 12 },
                            padding: 20,
                            color: '#2c3e50'
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        ticks: {
                            font: { family: "'Cairo', sans-serif" },
                            color: '#7f8c8d'
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        ticks: {
                            font: { family: "'Cairo', sans-serif" },
                            color: '#7f8c8d'
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Status Distribution Chart - Pie
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusChart = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['قيد الانتظار', 'قيد المعالجة', 'مكتمل', 'ملغي'],
                datasets: [{
                    data: [
                        {{ $pendingOrders ?? 0 }},
                        {{ $processingOrders ?? 0 }},
                        {{ $deliveredOrders ?? 0 }},
                        {{ $cancelledOrders ?? 0 }}
                    ],
                    backgroundColor: [
                        '#f39c12',
                        '#3498db',
                        '#27ae60',
                        '#e74c3c'
                    ],
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
                        labels: {
                            font: { family: "'Cairo', sans-serif", size: 12 },
                            padding: 20,
                            color: '#2c3e50'
                        }
                    }
                }
            }
        });

        
        function exportData() {
            alert('سيتم تصدير البيانات قريباً');
        }
    </script>
</body>
</html>