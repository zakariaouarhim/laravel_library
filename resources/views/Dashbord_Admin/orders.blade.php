<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>إدارة الطلبات</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.rtl.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/dashbordorder.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sidebardaschboard.css') }}">
    

    <meta name="csrf-token" content="{{ csrf_token() }}">
    
</head>
<body>
    @include('Dashbord_Admin.dashbordHeader')
    
    <div class="container-fluid">
        <div class="row">
            @include('Dashbord_Admin.Sidebar')

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <!-- Page Header -->
                <div class="page-header">
                    <h2>
                        <i class="fas fa-boxes"></i>
                        إدارة الطلبات
                    </h2>
                </div>

                <!-- Stats Cards -->
                <div class="stats-cards">
                    <div class="stat-card pending">
                        <div class="stat-card-icon"><i class="fas fa-clock text-warning"></i></div>
                        <div class="stat-card-title">قيد الانتظار</div>
                        <div class="stat-card-value">{{ $pendingCount ?? 0 }}</div>
                    </div>
                    <div class="stat-card processing">
                        <div class="stat-card-icon"><i class="fas fa-cog text-info"></i></div>
                        <div class="stat-card-title">قيد المعالجة</div>
                        <div class="stat-card-value">{{ $processingCount ?? 0 }}</div>
                    </div>
                    <div class="stat-card delivered">
                        <div class="stat-card-icon"><i class="fas fa-check-circle text-success"></i></div>
                        <div class="stat-card-title">تم التسليم</div>
                        <div class="stat-card-value">{{ $deliveredCount ?? 0 }}</div>
                    </div>
                    <div class="stat-card cancelled">
                        <div class="stat-card-icon"><i class="fas fa-times-circle text-danger"></i></div>
                        <div class="stat-card-title">ملغى</div>
                        <div class="stat-card-value">{{ $cancelledCount ?? 0 }}</div>
                    </div>
                </div>

                <!-- Filters Section -->
                <div class="filter-section">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <input 
                                type="text" 
                                id="searchInput" 
                                class="form-control" 
                                placeholder="بحث برقم الطلب أو رقم التتبع..."
                            >
                        </div>
                        <div class="col-md-3">
                            <select id="statusFilter" class="form-select">
                                <option value="">كل الحالات</option>
                                <option value="pending">قيد الانتظار</option>
                                <option value="processing">قيد المعالجة</option>
                                <option value="shipped">مشحون</option>
                                <option value="delivered">تم التسليم</option>
                                <option value="cancelled">ملغى</option>
                                <option value="Failed">فشل</option>
                                <option value="Refunded">مسترجع</option>
                                <option value="returned">مرتجع</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-apply-filters w-100" onclick="applyFilters()">
                                <i class="fas fa-filter me-2"></i>تطبيق الفلاتر
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Orders Table -->
                <div class="table-section">
                    <div class="table-responsive">
                        <table class="table table-hover text-center align-middle" id="ordersTable">
                            <thead>
                                <tr>
                                    <th>رقم الطلب</th>
                                    <th>رقم التتبع</th>
                                    <th>المبلغ الإجمالي</th>
                                    <th>طريقة الدفع</th>
                                    <th>الحالة</th>
                                    <th>تاريخ الطلب</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($orders as $order)
                                <tr data-order-id="{{ $order->id }}">
                                    <td>
                                        <span class="order-id">#{{ $order->id }}</span>
                                    </td>
                                    <td>
                                        @if($order->tracking_number)
                                            {{ $order->tracking_number }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ number_format($order->total_price, 2) }} ر.س</strong>
                                    </td>
                                    <td>
                                        @if($order->payment_method == 'cod')
                                            <span class="badge bg-warning">الدفع عند الاستلام</span>
                                        @else
                                            <span class="badge bg-info">بطاقة ائتمان</span>
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
                                    <td>{{ $order->created_at->format('d-m-Y H:i') }}</td>
                                    <td>
                                        <div class="action-buttons">
                                            <button 
                                                class="btn-action btn-view" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#orderModal"
                                                onclick="viewOrder({{ $order->id }})">
                                                <i class="fas fa-eye me-1"></i>عرض
                                            </button>
                                            <button 
                                                class="btn-action btn-edit" 
                                                onclick="editOrder({{ $order->id }})">
                                                <i class="fas fa-edit me-1"></i>تعديل
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7">
                                        <div class="empty-state">
                                            <i class="fas fa-inbox"></i>
                                            <p>لا توجد طلبات</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

               <!-- Pagination -->
                @if($orders instanceof \Illuminate\Pagination\Paginator || $orders instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        {{-- Previous Page Link --}}
                        @if ($orders->onFirstPage())
                            <li class="page-item disabled"><span class="page-link">السابق</span></li>
                        @else
                            <li class="page-item"><a class="page-link" href="{{ $orders->previousPageUrl() }}">السابق</a></li>
                        @endif

                        {{-- Pagination Elements --}}
                        @foreach ($orders->getUrlRange(1, $orders->lastPage()) as $page => $url)
                            @if ($page == $orders->currentPage())
                                <li class="page-item active"><span class="page-link">{{ $page }}</span></li>
                            @else
                                <li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                            @endif
                        @endforeach

                        {{-- Next Page Link --}}
                        @if ($orders->hasMorePages())
                            <li class="page-item"><a class="page-link" href="{{ $orders->nextPageUrl() }}">التالي</a></li>
                        @else
                            <li class="page-item disabled"><span class="page-link">التالي</span></li>
                        @endif
                    </ul>
                </nav>
                @endif 
            </main>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.24.1/feather.min.js"></script>

    
    <script src="{{ asset('js/dashboard.js') }}"></script>
      <script src="{{ asset('js/dashboardorder.js') }}"></script> 
</body>
</html>