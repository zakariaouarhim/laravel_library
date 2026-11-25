<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>لوحة القيادة</title>

    <link rel="stylesheet" href="{{ asset('css/variables.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components/badges.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components/modal.css') }}">
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.rtl.min.css">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- Favicon -->
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
    
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sidebardaschboard.css') }}">
    
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="{{ asset('js/csrf.js') }}" defer></script>
    <style>
        .stock-editor   { display:inline-flex;align-items:center;gap:.3rem; }
        .stock-input    { width:64px;text-align:center;border:1px solid #d8dde6;border-radius:6px;padding:.25rem;font-family:inherit; }
        .stock-step     { width:30px;height:30px;border:1px solid #d8dde6;background:#f7f9fc;border-radius:6px;cursor:pointer;font-size:1rem;line-height:1;color:#445; }
        .stock-step:hover { background:#eef3ff; }
        .stock-save     { width:32px;height:30px;border:none;background:#27ae60;color:#fff;border-radius:6px;cursor:pointer; }
        .stock-save:hover { background:#219150; }
        .stock-save:disabled { opacity:.5;cursor:default; }
        .stock-editor.dirty .stock-save { box-shadow:0 0 0 3px rgba(39,174,96,.2); }
        #dashToast { position:fixed;bottom:1.5rem;left:1.5rem;z-index:9999; }
    </style>
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
                        <a href="{{ route('admin.products.index') }}" class="btn-header btn-export">
                            <i class="fas fa-boxes-stacked me-2"></i>إدارة المخزون
                        </a>
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
                        @php $up = ($ordersIncrease ?? 0) >= 0; @endphp
                        <div class="stat-change {{ $up ? 'positive' : 'negative' }}">
                            <i class="fas fa-arrow-{{ $up ? 'up' : 'down' }}"></i>
                            {{ $up ? '+' : '' }}{{ $ordersIncrease ?? 0 }}% هذا الشهر
                        </div>
                    </div>

                    <div class="stat-card revenue">
                        <div class="stat-icon">
                            <i class="fas fa-coins"></i>
                        </div>
                        <div class="stat-title">إجمالي الإيرادات</div>
                        <div class="stat-value">{{ number_format($totalRevenue ?? 0, 2) }} د.م</div>
                        @php $up = ($revenueIncrease ?? 0) >= 0; @endphp
                        <div class="stat-change {{ $up ? 'positive' : 'negative' }}">
                            <i class="fas fa-arrow-{{ $up ? 'up' : 'down' }}"></i>
                            {{ $up ? '+' : '' }}{{ $revenueIncrease ?? 0 }}% هذا الشهر
                        </div>
                    </div>

                    <div class="stat-card pending">
                        <div class="stat-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-title">طلبات قيد الانتظار</div>
                        <div class="stat-value">{{ $pendingOrders ?? 0 }}</div>
                        @php $down = ($pendingDecrease ?? 0) >= 0; // pending falling is good @endphp
                        <div class="stat-change {{ $down ? 'positive' : 'negative' }}">
                            <i class="fas fa-arrow-{{ $down ? 'down' : 'up' }}"></i>
                            {{ $down ? '-' : '+' }}{{ abs($pendingDecrease ?? 0) }}% هذا الشهر
                        </div>
                    </div>

                    <div class="stat-card delivered">
                        <div class="stat-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-title">طلبات مكتملة</div>
                        <div class="stat-value">{{ $deliveredOrders ?? 0 }}</div>
                        @php $up = ($deliveredIncrease ?? 0) >= 0; @endphp
                        <div class="stat-change {{ $up ? 'positive' : 'negative' }}">
                            <i class="fas fa-arrow-{{ $up ? 'up' : 'down' }}"></i>
                            {{ $up ? '+' : '' }}{{ $deliveredIncrease ?? 0 }}% هذا الشهر
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
                            <option value="monthlyRevenue">الشهر</option>
                            <option value="yearlyRevenue">السنة</option>
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

                <!-- Low Stock Alerts -->
                @if(isset($lowStockBooks) && $lowStockBooks->count())
                <div class="recent-orders" id="lowStockPanel" style="margin-bottom: 2rem;">
                    <h3 style="color: #e74c3c;">
                        <i class="fas fa-exclamation-triangle"></i>
                        تنبيه المخزون المنخفض
                        <span class="badge bg-danger ms-2" id="lowStockCount">{{ $lowStockBooks->count() }}</span>
                    </h3>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th>الكتاب</th>
                                    <th>الكمية المتبقية</th>
                                    <th>الحالة</th>
                                    <th>تعديل الكمية</th>
                                </tr>
                            </thead>
                            <tbody id="lowStockBody">
                                @foreach($lowStockBooks as $book)
                                <tr id="low-stock-row-{{ $book->id }}" data-id="{{ $book->id }}">
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 10px;">
                                            @if($book->image)
                                            <img src="{{ asset($book->image) }}" alt="{{ $book->title }}" width="35" height="45" style="object-fit: cover; border-radius: 4px;" loading="lazy"
                                 onerror="this.onerror=null;this.src='{{ asset('images/book-placeholder.png') }}'">
                                            @endif
                                            <div>
                                                <span>{{ $book->title }}</span>
                                                <div style="font-size: .75rem; color: #95a5a6;">آخر بيع: {{ $book->last_sold_at ? \Carbon\Carbon::parse($book->last_sold_at)->format('d-m-Y') : '—' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><strong class="stock-qty">{{ $book->quantity }}</strong></td>
                                    <td>
                                        <span class="status-badge stock-status" style="background: {{ $book->quantity == 0 ? '#e74c3c' : '#f39c12' }}; color: #fff;">
                                            {{ $book->quantity == 0 ? 'نفذ المخزون' : 'مخزون منخفض' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="stock-editor">
                                            <button type="button" class="stock-step" data-step="-1" aria-label="إنقاص">&minus;</button>
                                            <input type="number" class="stock-input" min="0" value="{{ $book->quantity }}" aria-label="الكمية">
                                            <button type="button" class="stock-step" data-step="1" aria-label="زيادة">+</button>
                                            <button type="button" class="stock-save" title="حفظ"><i class="fas fa-check"></i></button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div style="text-align: center; margin-top: .5rem;">
                        <a href="{{ route('admin.products.index') }}" class="btn btn-sm btn-outline-danger">
                            <i class="fas fa-list me-1"></i> عرض كل المخزون
                        </a>
                    </div>
                </div>
                @endif

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
                                    <td>{{ number_format($order->total_price, 2) }} د.م</td>
                                    <td>
                                        <span class="badge badge-custom {{ $order->payment_method === 'bank_transfer' ? 'badge-processing' : ($order->payment_method === 'cod' ? 'badge-pending' : 'badge-processing') }}">
                                            {{ $order->payment_label }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $statusMap = [
                                                'pending' => ['class' => 'status-pending', 'text' => 'قيد الانتظار'],
                                                'processing' => ['class' => 'status-processing', 'text' => 'قيد المعالجة'],
                                                'shipped' => ['class' => 'status-shipped', 'text' => 'مشحون'],
                                                'delivered' => ['class' => 'status-delivered', 'text' => 'تم التسليم'],
                                                'cancelled' => ['class' => 'status-cancelled', 'text' => 'ملغى'],
                                                'failed' => ['class' => 'status-failed', 'text' => 'فشل'],
                                                'refunded' => ['class' => 'status-refunded', 'text' => 'مسترجع'],
                                                'returned' => ['class' => 'status-returned', 'text' => 'مرتجع'],
                                            ];
                                            $status = $statusMap[$order->status->value] ?? ['class' => 'status-pending', 'text' => $order->status->label()];
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
    <div id="dashToast"></div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    
    <script src="{{ asset('js/dashboard.js') }}" defer></script> 
    <script>
        // Chart data from PHP
        var chartData = {
            weekly: @json($weeklyRevenue),
            monthly: @json($monthlyRevenue),
            yearly: @json($yearlyRevenue),
            status: [
                {{ $pendingOrders ?? 0 }},
                {{ $processingOrders ?? 0 }},
                {{ $shippedOrders ?? 0 }},
                {{ $deliveredOrders ?? 0 }},
                {{ $cancelledOrders ?? 0 }},
                {{ $failedOrders ?? 0 }},
                {{ $refundedOrders ?? 0 }},
                {{ $returnedOrders ?? 0 }}
            ]
        };

        document.addEventListener('DOMContentLoaded', function () {
            if (typeof Chart === 'undefined') return;

            var weeklabels = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
            var monthLabels = ['يناير', 'فبراير', 'مارس', 'أبريل', 'ماي', 'يونيو', 'يوليو', 'غشت', 'شتنبر', 'أكتوبر', 'نونبر', 'دجنبر'];
            var yearLabels = chartData.yearly.map(function(i) { return i.year; });

            var revenueData = new Array(7).fill(0);
            var monthlyData = new Array(12).fill(0);
            var yearlyData  = chartData.yearly.map(function(i) { return parseFloat(i.total); });

            chartData.monthly.forEach(function(i) { monthlyData[i.month - 1] = parseFloat(i.total); });
            chartData.weekly.forEach(function(i)  { revenueData[i.day - 1]   = parseFloat(i.total); });

            // Revenue Chart
            var revenueCtx = document.getElementById('revenueChart');
            if (!revenueCtx) return;

            var revenueChart = new Chart(revenueCtx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: weeklabels,
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
                            beginAtZero: true,
                            ticks: { font: { family: "'Cairo', sans-serif" }, color: '#7f8c8d' },
                            grid: { color: 'rgba(0, 0, 0, 0.05)' }
                        },
                        x: {
                            ticks: { font: { family: "'Cairo', sans-serif" }, color: '#7f8c8d' },
                            grid: { display: false }
                        }
                    }
                }
            });

            // Period switcher
            var dateSelect = document.getElementById('date');
            if (dateSelect) {
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
            }

            // Status Distribution Chart
            var statusCtx = document.getElementById('statusChart');
            if (!statusCtx) return;

            new Chart(statusCtx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['قيد الانتظار', 'قيد المعالجة', 'مشحون', 'تم التسليم', 'ملغي', 'فشل', 'مسترجع', 'مرتجع'],
                    datasets: [{
                        data: chartData.status,
                        backgroundColor: ['#f39c12', '#3498db', '#9b59b6', '#27ae60', '#e74c3c', '#c0392b', '#7f8c8d', '#d35400'],
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
        });
    </script>
    <script>
        // ===== Inline low-stock editing =====
        (function () {
            const LOW_STOCK_THRESHOLD = 5; // matches DashboardReportService::getLowStockBooks()
            const panel = document.getElementById('lowStockPanel');
            if (!panel) return;

            const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const stockUrl = id => "{{ url('admin/products') }}/" + id + "/stock";

            function toast(msg, ok = true) {
                const el = document.createElement('div');
                el.style = `background:${ok ? '#27ae60' : '#e74c3c'};color:#fff;padding:.7rem 1.1rem;border-radius:8px;margin-top:.5rem;font-family:'Cairo',sans-serif;box-shadow:0 4px 12px rgba(0,0,0,.15);`;
                el.textContent = msg;
                document.getElementById('dashToast').appendChild(el);
                setTimeout(() => el.remove(), 3200);
            }

            function refreshCount() {
                const rows = panel.querySelectorAll('#lowStockBody tr').length;
                const badge = document.getElementById('lowStockCount');
                if (badge) badge.textContent = rows;
                if (rows === 0) {
                    panel.querySelector('.table-responsive').innerHTML =
                        '<p class="text-center text-muted py-3 mb-0">لا توجد كتب بمخزون منخفض 🎉</p>';
                }
            }

            panel.addEventListener('click', function (e) {
                const editor = e.target.closest('.stock-editor');
                if (!editor) return;
                const input = editor.querySelector('.stock-input');

                // +/- steppers
                const step = e.target.closest('.stock-step');
                if (step) {
                    const next = Math.max(0, (parseInt(input.value, 10) || 0) + parseInt(step.dataset.step, 10));
                    input.value = next;
                    editor.classList.add('dirty');
                    return;
                }

                // Save
                const saveBtn = e.target.closest('.stock-save');
                if (!saveBtn) return;

                const row = e.target.closest('tr');
                const id = row.dataset.id;
                const qty = parseInt(input.value, 10);
                if (isNaN(qty) || qty < 0) { toast('قيمة غير صحيحة', false); return; }

                saveBtn.disabled = true;
                fetch(stockUrl(id), {
                    method: 'PATCH',
                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'Content-Type': 'application/json' },
                    body: JSON.stringify({ quantity: qty })
                })
                .then(r => r.json())
                .then(data => {
                    saveBtn.disabled = false;
                    if (!data.success) { toast(data.message || 'حدث خطأ', false); return; }
                    editor.classList.remove('dirty');

                    // Update displayed quantity + status badge
                    row.querySelector('.stock-qty').textContent = data.quantity;
                    const badge = row.querySelector('.stock-status');
                    if (data.quantity == 0) {
                        badge.style.background = '#e74c3c'; badge.textContent = 'نفذ المخزون';
                    } else {
                        badge.style.background = '#f39c12'; badge.textContent = 'مخزون منخفض';
                    }
                    toast(data.message);

                    // No longer low stock → fade the row out of the panel
                    if (data.quantity >= LOW_STOCK_THRESHOLD) {
                        row.style.transition = 'opacity .4s';
                        row.style.opacity = '0';
                        setTimeout(() => { row.remove(); refreshCount(); }, 420);
                    }
                })
                .catch(() => { saveBtn.disabled = false; toast('حدث خطأ في الاتصال', false); });
            });

            // Mark editor dirty when typing
            panel.addEventListener('input', function (e) {
                if (e.target.classList.contains('stock-input')) {
                    e.target.closest('.stock-editor').classList.add('dirty');
                }
            });
        })();
    </script>
</body>
</html>