<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>إدارة طلبات الإسترجاع</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.rtl.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">

    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/admin-return-requests.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sidebardaschboard.css') }}">

    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>

    <div class="container-fluid">
        <div class="row">
            @include('Dashbord_Admin.Sidebar')

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <!-- Page Header -->
                <div class="page-header">
                    <h2>
                        <i class="fas fa-undo"></i>
                        إدارة طلبات الإسترجاع
                    </h2>
                </div>

                <!-- Stats Cards -->
                <div class="stats-cards">
                    <div class="stat-card pending">
                        <div class="stat-card-icon"><i class="fas fa-clock text-warning"></i></div>
                        <div class="stat-card-title">قيد المراجعة</div>
                        <div class="stat-card-value">{{ $pendingCount ?? 0 }}</div>
                    </div>
                    <div class="stat-card approved">
                        <div class="stat-card-icon"><i class="fas fa-check-circle text-success"></i></div>
                        <div class="stat-card-title">مقبول</div>
                        <div class="stat-card-value">{{ $approvedCount ?? 0 }}</div>
                    </div>
                    <div class="stat-card rejected">
                        <div class="stat-card-icon"><i class="fas fa-times-circle text-danger"></i></div>
                        <div class="stat-card-title">مرفوض</div>
                        <div class="stat-card-value">{{ $rejectedCount ?? 0 }}</div>
                    </div>
                    <div class="stat-card refunded">
                        <div class="stat-card-icon"><i class="fas fa-money-bill-wave text-info"></i></div>
                        <div class="stat-card-title">تم الاسترداد</div>
                        <div class="stat-card-value">{{ $refundedCount ?? 0 }}</div>
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
                                placeholder="بحث برقم طلب الإسترجاع أو رقم الطلب..."
                                value="{{ request('search') }}"
                            >
                        </div>
                        <div class="col-md-3">
                            <select id="statusFilter" class="form-select">
                                <option value="">كل الحالات</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>قيد المراجعة</option>
                                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>مقبول</option>
                                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>مرفوض</option>
                                <option value="refunded" {{ request('status') == 'refunded' ? 'selected' : '' }}>تم الاسترداد</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-apply-filters w-100" onclick="applyFilters()">
                                <i class="fas fa-filter me-2"></i>تطبيق الفلاتر
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Return Requests Table -->
                <div class="table-section">
                    <div class="table-responsive">
                        <table class="table table-hover text-center align-middle">
                            <thead>
                                <tr>
                                    <th>رقم الطلب</th>
                                    <th>الطلب الأصلي</th>
                                    <th>العميل</th>
                                    <th>سبب الإرجاع</th>
                                    <th>طريقة الدفع</th>
                                    <th>مبلغ الاسترداد</th>
                                    <th>الحالة</th>
                                    <th>التاريخ</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($returnRequests as $returnRequest)
                                <tr>
                                    <td>
                                        <span class="return-id">#{{ $returnRequest->id }}</span>
                                    </td>
                                    <td>
                                        <span class="order-ref">#{{ $returnRequest->order_id }}</span>
                                    </td>
                                    <td>
                                        @if($returnRequest->order && $returnRequest->order->user)
                                            {{ $returnRequest->order->user->name }}
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="reason-preview" title="{{ $returnRequest->reason }}">
                                            {{ Str::limit($returnRequest->reason, 50) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($returnRequest->payment_method == 'cod')
                                            <span class="badge bg-warning">الدفع عند الاستلام</span>
                                        @else
                                            <span class="badge bg-info">بطاقة ائتمان</span>
                                        @endif
                                    </td>
                                    <td>
                                        <strong>{{ number_format($returnRequest->refund_amount, 2) }} د.م</strong>
                                    </td>
                                    <td>
                                        @php
                                            $statusMap = [
                                                'pending'  => ['class' => 'return-status-pending',  'text' => 'قيد المراجعة'],
                                                'approved' => ['class' => 'return-status-approved', 'text' => 'مقبول'],
                                                'rejected' => ['class' => 'return-status-rejected', 'text' => 'مرفوض'],
                                                'refunded' => ['class' => 'return-status-refunded', 'text' => 'تم الاسترداد'],
                                            ];
                                            $s = $statusMap[$returnRequest->status] ?? ['class' => 'return-status-pending', 'text' => $returnRequest->status];
                                        @endphp
                                        <span class="status-badge {{ $s['class'] }}">{{ $s['text'] }}</span>
                                    </td>
                                    <td>{{ $returnRequest->created_at->format('d-m-Y H:i') }}</td>
                                    <td>
                                        <div class="action-buttons">
                                            <button
                                                class="btn-action btn-view"
                                                data-bs-toggle="modal"
                                                data-bs-target="#returnRequestModal"
                                                onclick="viewReturnRequest({{ $returnRequest->id }})">
                                                <i class="fas fa-eye me-1"></i>عرض
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9">
                                        <div class="empty-state">
                                            <i class="fas fa-inbox"></i>
                                            <p>لا توجد طلبات إسترجاع</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                @if($returnRequests instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <nav>
                    {{ $returnRequests->appends(request()->query())->links('pagination::bootstrap-4') }}
                </nav>
                @endif
            </main>
        </div>
    </div>

    <!-- Return Request Details Modal -->
    <div class="modal fade" id="returnRequestModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-undo me-2"></i>تفاصيل طلب الإسترجاع
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
    <script src="{{ asset('js/admin-return-requests.js') }}"></script>
</body>
</html>
