<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طلبات الإسترجاع</title>

    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.rtl.min.css">
    <!-- Stylesheets -->
    <link rel="stylesheet" href="{{ asset('css/headerstyle.css') }}">
    <link rel="stylesheet" href="{{ asset('css/return-requests.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Favicon -->
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">

    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    @include('header')

    <!-- Hero Banner -->
    <div class="returns-hero">
        <div class="returns-hero-overlay"></div>
        <div class="container">
            <div class="hero-content text-center">
                <h1 class="hero-title"><i class="fas fa-undo me-2"></i>طلبات الإسترجاع</h1>
                <nav aria-label="breadcrumb" class="mt-3">
                    <ol class="breadcrumb justify-content-center">
                        <li class="breadcrumb-item"><a href="{{ route('index.page') }}"><i class="fas fa-home"></i> الرئيسية</a></li>
                        <li class="breadcrumb-item active" aria-current="page">طلبات الإسترجاع</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="container py-5">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- New Return Request Button -->
        <div class="d-flex justify-content-end mb-4">
            <button class="btn btn-new-return" data-bs-toggle="modal" data-bs-target="#newReturnModal"
                    @if($eligibleOrders->isEmpty()) disabled title="لا توجد طلبات مؤهلة للإسترجاع" @endif>
                <i class="fas fa-plus me-1"></i>طلب إسترجاع جديد
            </button>
        </div>

        <!-- Status Filter Tabs -->
        <div class="status-tabs-wrapper mb-4">
            <ul class="nav nav-pills status-tabs">
                @php
                    $tabs = [
                        'all'      => ['label' => 'الكل',           'icon' => 'fas fa-list'],
                        'pending'  => ['label' => 'قيد المراجعة',    'icon' => 'fas fa-clock'],
                        'approved' => ['label' => 'مقبول',          'icon' => 'fas fa-check'],
                        'rejected' => ['label' => 'مرفوض',          'icon' => 'fas fa-times'],
                        'refunded' => ['label' => 'تم الاسترداد',    'icon' => 'fas fa-money-bill-wave'],
                    ];
                    $currentStatus = $status ?? 'all';
                @endphp
                @foreach($tabs as $key => $tab)
                    <li class="nav-item">
                        <a class="nav-link {{ $currentStatus == $key || (!$currentStatus && $key == 'all') ? 'active' : '' }}"
                           href="{{ route('return-requests.index', $key !== 'all' ? ['status' => $key] : []) }}">
                            <i class="{{ $tab['icon'] }} me-1"></i>
                            {{ $tab['label'] }}
                            <span class="badge-count">{{ $statusCounts[$key] ?? 0 }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>

        <!-- Return Requests List -->
        @if($returnRequests->count() > 0)
            <div class="returns-list">
                @foreach($returnRequests as $returnRequest)
                    <div class="return-card">
                        <!-- Return Request Header -->
                        <div class="return-card-header" data-bs-toggle="collapse" data-bs-target="#returnDetails{{ $returnRequest->id }}" aria-expanded="false">
                            <div class="return-header-main">
                                <div class="return-id-section">
                                    <span class="return-number">طلب إسترجاع #{{ $returnRequest->id }}</span>
                                    <span class="return-order-ref"><i class="fas fa-shopping-bag me-1"></i>طلب #{{ $returnRequest->order_id }}</span>
                                </div>
                                <div class="return-status">
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
                                </div>
                            </div>
                            <div class="return-header-info">
                                <div class="return-meta">
                                    <span><i class="fas fa-calendar-alt me-1"></i>{{ $returnRequest->created_at->format('d/m/Y') }}</span>
                                    <span><i class="fas fa-money-bill-wave me-1"></i>{{ number_format($returnRequest->refund_amount, 2) }} د.م</span>
                                    <span><i class="fas fa-credit-card me-1"></i>{{ $returnRequest->payment_method == 'cod' ? 'الدفع عند الاستلام' : 'بطاقة ائتمان' }}</span>
                                </div>
                                <div class="expand-icon">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Return Request Details (Collapsed) -->
                        <div class="collapse" id="returnDetails{{ $returnRequest->id }}">
                            <div class="return-card-body">
                                <!-- Reason -->
                                <div class="return-reason-section">
                                    <h6 class="section-title"><i class="fas fa-comment me-2"></i>سبب الإرجاع</h6>
                                    <p class="reason-text">{{ $returnRequest->reason }}</p>
                                </div>

                                <!-- Admin Notes -->
                                @if($returnRequest->admin_notes)
                                    <div class="admin-notes-section">
                                        <h6 class="section-title"><i class="fas fa-user-shield me-2"></i>ملاحظات الإدارة</h6>
                                        <p class="admin-notes-text">{{ $returnRequest->admin_notes }}</p>
                                    </div>
                                @endif

                                <!-- Resolved Date -->
                                @if($returnRequest->resolved_at)
                                    <div class="resolved-date">
                                        <i class="fas fa-check-circle me-1"></i>تاريخ المعالجة: {{ $returnRequest->resolved_at->format('d/m/Y') }}
                                    </div>
                                @endif

                                <!-- Order Books -->
                                @if($returnRequest->order && $returnRequest->order->orderDetails->count() > 0)
                                    <div class="return-books">
                                        <h6 class="section-title"><i class="fas fa-book me-2"></i>الكتب في الطلب</h6>
                                        @foreach($returnRequest->order->orderDetails as $item)
                                            <div class="book-row">
                                                <div class="book-thumb">
                                                    @if($item->book)
                                                        <img src="{{ asset($item->book->image ?? 'images/book-placeholder.png') }}" alt="{{ $item->book->title ?? '' }}">
                                                    @else
                                                        <img src="{{ asset('images/book-placeholder.png') }}" alt="">
                                                    @endif
                                                </div>
                                                <div class="book-info">
                                                    @if($item->book)
                                                        <a href="{{ route('moredetail.page', ['id' => $item->book->id]) }}" class="book-title-link">{{ $item->book->title }}</a>
                                                    @else
                                                        <span class="text-muted">كتاب محذوف</span>
                                                    @endif
                                                    <span class="book-qty">الكمية: {{ $item->quantity }}</span>
                                                </div>
                                                <div class="book-price">
                                                    <span class="unit-price">{{ number_format($item->price, 2) }} د.م</span>
                                                    <span class="line-total">{{ number_format($item->price * $item->quantity, 2) }} د.م</span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                <!-- Order Summary -->
                                @if($returnRequest->order && $returnRequest->order->checkoutDetail)
                                    <div class="return-summary-section">
                                        <h6 class="section-title"><i class="fas fa-receipt me-2"></i>ملخص الطلب</h6>
                                        <div class="summary-line">
                                            <span>المجموع الفرعي</span>
                                            <span>{{ number_format($returnRequest->order->checkoutDetail->subtotal, 2) }} د.م</span>
                                        </div>
                                        <div class="summary-line">
                                            <span>الشحن</span>
                                            <span>{{ number_format($returnRequest->order->checkoutDetail->shipping, 2) }} د.م</span>
                                        </div>
                                        @if($returnRequest->order->checkoutDetail->discount > 0)
                                            <div class="summary-line discount">
                                                <span>الخصم</span>
                                                <span>- {{ number_format($returnRequest->order->checkoutDetail->discount, 2) }} د.م</span>
                                            </div>
                                        @endif
                                        <div class="summary-line total">
                                            <span>مبلغ الاسترداد</span>
                                            <span>{{ number_format($returnRequest->refund_amount, 2) }} د.م</span>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <nav class="mt-4">
                {{ $returnRequests->links('pagination::bootstrap-4') }}
            </nav>
        @else
            <!-- Empty State -->
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-undo"></i>
                </div>
                <h3>لا توجد طلبات إسترجاع</h3>
                @if($status && $status !== 'all')
                    <p>لا توجد طلبات إسترجاع بهذه الحالة حالياً</p>
                    <a href="{{ route('return-requests.index') }}" class="btn btn-outline-primary">عرض جميع الطلبات</a>
                @else
                    <p>لم تقم بأي طلبات إسترجاع بعد</p>
                    <a href="{{ route('my-orders.index') }}" class="btn btn-primary-custom">
                        <i class="fas fa-shopping-bag me-2"></i>عرض طلباتي
                    </a>
                @endif
            </div>
        @endif
    </div>

    <!-- New Return Request Modal -->
    <div class="modal fade" id="newReturnModal" tabindex="-1" aria-labelledby="newReturnModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="newReturnModalLabel"><i class="fas fa-undo me-2"></i>طلب إسترجاع جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <form action="{{ route('return-requests.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="orderSelect" class="form-label">اختر الطلب</label>
                            <select name="order_id" id="orderSelect" class="form-select" required>
                                <option value="">-- اختر الطلب --</option>
                                @foreach($eligibleOrders as $order)
                                    <option value="{{ $order->id }}">
                                        طلب #{{ $order->id }} — {{ number_format($order->total_price, 2) }} د.م
                                        ({{ $order->created_at->format('d/m/Y') }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Order preview -->
                        <div id="orderPreview" class="order-preview d-none">
                            <h6 class="preview-title">الكتب في الطلب:</h6>
                            <div id="orderPreviewBooks"></div>
                        </div>

                        <div class="mb-3">
                            <label for="reasonTextarea" class="form-label">سبب الإرجاع</label>
                            <textarea name="reason" id="reasonTextarea" class="form-control" rows="4"
                                      placeholder="اكتب سبب طلب الإسترجاع..." required maxlength="1000"></textarea>
                            <div class="form-text">الحد الأقصى 1000 حرف</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary-custom">
                            <i class="fas fa-paper-plane me-1"></i>إرسال الطلب
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @include('footer')

    <!-- Scripts -->
    <script src="{{ asset('js/header.js') }}"></script>
    <script src="{{ asset('js/scripts.js') }}"></script>
    <script>
        // Rotate chevron icon on expand/collapse
        document.querySelectorAll('.return-card-header').forEach(function(header) {
            header.addEventListener('click', function() {
                const icon = this.querySelector('.expand-icon i');
                const target = document.querySelector(this.getAttribute('data-bs-target'));
                target.addEventListener('shown.bs.collapse', function() {
                    icon.classList.remove('fa-chevron-down');
                    icon.classList.add('fa-chevron-up');
                });
                target.addEventListener('hidden.bs.collapse', function() {
                    icon.classList.remove('fa-chevron-up');
                    icon.classList.add('fa-chevron-down');
                });
            });
        });

        // Order preview in modal
        const eligibleOrders = @json($eligibleOrders);
        const orderSelect = document.getElementById('orderSelect');
        const orderPreview = document.getElementById('orderPreview');
        const orderPreviewBooks = document.getElementById('orderPreviewBooks');

        orderSelect.addEventListener('change', function() {
            const orderId = parseInt(this.value);
            const order = eligibleOrders.find(o => o.id === orderId);

            if (order && order.order_details) {
                let html = '';
                order.order_details.forEach(function(item) {
                    const bookTitle = item.book ? item.book.title : 'كتاب محذوف';
                    const bookImage = item.book ? ('/' + (item.book.image || 'images/book-placeholder.png')) : '/images/book-placeholder.png';
                    html += `
                        <div class="preview-book-row">
                            <img src="${bookImage}" alt="${bookTitle}" class="preview-book-img">
                            <div class="preview-book-info">
                                <span class="preview-book-title">${bookTitle}</span>
                                <span class="preview-book-qty">الكمية: ${item.quantity} × ${parseFloat(item.price).toFixed(2)} د.م</span>
                            </div>
                        </div>
                    `;
                });
                orderPreviewBooks.innerHTML = html;
                orderPreview.classList.remove('d-none');
            } else {
                orderPreview.classList.add('d-none');
                orderPreviewBooks.innerHTML = '';
            }
        });
    </script>
</body>
</html>
