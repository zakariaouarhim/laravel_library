<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>طلباتي</title>

    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.rtl.min.css">
    <!-- Stylesheets -->
    <link rel="stylesheet" href="{{ asset('css/headerstyle.css') }}">
    <link rel="stylesheet" href="{{ asset('css/my-orders.css') }}">
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
    <div class="orders-hero">
        <div class="orders-hero-overlay"></div>
        <div class="container">
            <div class="hero-content text-center">
                <h1 class="hero-title"><i class="fas fa-shopping-bag me-2"></i>طلباتي</h1>
                <nav aria-label="breadcrumb" class="mt-3">
                    <ol class="breadcrumb justify-content-center">
                        <li class="breadcrumb-item"><a href="{{ route('index.page') }}"><i class="fas fa-home"></i> الرئيسية</a></li>
                        <li class="breadcrumb-item active" aria-current="page">طلباتي</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="container py-5">
        <!-- Status Filter Tabs -->
        <div class="status-tabs-wrapper mb-4">
            <ul class="nav nav-pills status-tabs">
                @php
                    $tabs = [
                        'all'        => ['label' => 'الكل',           'icon' => 'fas fa-list'],
                        'pending'    => ['label' => 'قيد الانتظار',    'icon' => 'fas fa-clock'],
                        'processing' => ['label' => 'قيد المعالجة',    'icon' => 'fas fa-cog'],
                        'shipped'    => ['label' => 'مشحون',          'icon' => 'fas fa-truck'],
                        'delivered'  => ['label' => 'تم التسليم',      'icon' => 'fas fa-check-circle'],
                        'cancelled'  => ['label' => 'ملغي',           'icon' => 'fas fa-times-circle'],
                    ];
                    $currentStatus = $status ?? 'all';
                @endphp
                @foreach($tabs as $key => $tab)
                    <li class="nav-item">
                        <a class="nav-link {{ $currentStatus == $key || (!$currentStatus && $key == 'all') ? 'active' : '' }}"
                           href="{{ route('my-orders.index', $key !== 'all' ? ['status' => $key] : []) }}">
                            <i class="{{ $tab['icon'] }} me-1"></i>
                            {{ $tab['label'] }}
                            <span class="badge-count">{{ $statusCounts[$key] ?? 0 }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>

        <!-- Orders List -->
        @if($orders->count() > 0)
            <div class="orders-list">
                @foreach($orders as $order)
                    <div class="order-card">
                        <!-- Order Header -->
                        <div class="order-card-header" data-bs-toggle="collapse" data-bs-target="#orderDetails{{ $order->id }}" aria-expanded="false">
                            <div class="order-header-main">
                                <div class="order-id-section">
                                    <span class="order-number">طلب #{{ $order->id }}</span>
                                    @if($order->tracking_number)
                                        <span class="tracking-number"><i class="fas fa-barcode me-1"></i>{{ $order->tracking_number }}</span>
                                    @endif
                                </div>
                                <div class="order-status">
                                    @php
                                        $statusMap = [
                                            'pending'    => ['class' => 'status-pending',    'text' => 'قيد الانتظار'],
                                            'processing' => ['class' => 'status-processing', 'text' => 'قيد المعالجة'],
                                            'shipped'    => ['class' => 'status-shipped',    'text' => 'مشحون'],
                                            'delivered'  => ['class' => 'status-delivered',  'text' => 'تم التسليم'],
                                            'cancelled'  => ['class' => 'status-cancelled',  'text' => 'ملغي'],
                                            'Failed'     => ['class' => 'status-failed',     'text' => 'فشل'],
                                            'Refunded'   => ['class' => 'status-refunded',   'text' => 'مسترجع'],
                                            'returned'   => ['class' => 'status-returned',   'text' => 'مرتجع'],
                                        ];
                                        $s = $statusMap[$order->status] ?? ['class' => 'status-pending', 'text' => $order->status];
                                    @endphp
                                    <span class="status-badge {{ $s['class'] }}">{{ $s['text'] }}</span>
                                </div>
                            </div>
                            <div class="order-header-info">
                                <div class="order-meta">
                                    <span><i class="fas fa-calendar-alt me-1"></i>{{ $order->created_at->format('d/m/Y') }}</span>
                                    <span><i class="fas fa-box me-1"></i>{{ $order->orderDetails->count() }} عنصر</span>
                                    <span class="order-total"><i class="fas fa-money-bill-wave me-1"></i>{{ number_format($order->total_price, 2) }} ر.س</span>
                                </div>
                                <div class="expand-icon">
                                    <i class="fas fa-chevron-down"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Order Details (Collapsed) -->
                        <div class="collapse" id="orderDetails{{ $order->id }}">
                            <div class="order-card-body">
                                <!-- Books List -->
                                @if($order->orderDetails->count() > 0)
                                    <div class="order-books">
                                        <h6 class="section-title"><i class="fas fa-book me-2"></i>الكتب المطلوبة</h6>
                                        @foreach($order->orderDetails as $item)
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
                                                    <span class="unit-price">{{ number_format($item->price, 2) }} ر.س</span>
                                                    <span class="line-total">{{ number_format($item->price * $item->quantity, 2) }} ر.س</span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                <!-- Order Summary -->
                                <div class="order-summary-row">
                                    <div class="summary-section">
                                        <h6 class="section-title"><i class="fas fa-receipt me-2"></i>ملخص الطلب</h6>
                                        @if($order->checkoutDetail)
                                            <div class="summary-line">
                                                <span>المجموع الفرعي</span>
                                                <span>{{ number_format($order->checkoutDetail->subtotal, 2) }} ر.س</span>
                                            </div>
                                            <div class="summary-line">
                                                <span>الشحن</span>
                                                <span>{{ number_format($order->checkoutDetail->shipping, 2) }} ر.س</span>
                                            </div>
                                            @if($order->checkoutDetail->discount > 0)
                                                <div class="summary-line discount">
                                                    <span>الخصم</span>
                                                    <span>- {{ number_format($order->checkoutDetail->discount, 2) }} ر.س</span>
                                                </div>
                                            @endif
                                            <div class="summary-line total">
                                                <span>الإجمالي</span>
                                                <span>{{ number_format($order->checkoutDetail->total, 2) }} ر.س</span>
                                            </div>
                                        @else
                                            <div class="summary-line total">
                                                <span>الإجمالي</span>
                                                <span>{{ number_format($order->total_price, 2) }} ر.س</span>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="shipping-section">
                                        <h6 class="section-title"><i class="fas fa-truck me-2"></i>معلومات الشحن</h6>
                                        @if($order->checkoutDetail)
                                            <div class="shipping-detail">
                                                <span class="label">الاسم</span>
                                                <span>{{ $order->checkoutDetail->first_name }} {{ $order->checkoutDetail->last_name }}</span>
                                            </div>
                                            <div class="shipping-detail">
                                                <span class="label">العنوان</span>
                                                <span>{{ $order->checkoutDetail->address }}{{ $order->checkoutDetail->city ? '، ' . $order->checkoutDetail->city : '' }}</span>
                                            </div>
                                            <div class="shipping-detail">
                                                <span class="label">الهاتف</span>
                                                <span>{{ $order->checkoutDetail->phone }}</span>
                                            </div>
                                            <div class="shipping-detail">
                                                <span class="label">الدفع</span>
                                                <span>{{ $order->checkoutDetail->payment_method == 'cod' ? 'الدفع عند الاستلام' : 'بطاقة ائتمان' }}</span>
                                            </div>
                                        @elseif($order->shipping_address)
                                            <div class="shipping-detail">
                                                <span class="label">العنوان</span>
                                                <span>{{ $order->shipping_address }}</span>
                                            </div>
                                            <div class="shipping-detail">
                                                <span class="label">الدفع</span>
                                                <span>{{ $order->payment_method == 'cod' ? 'الدفع عند الاستلام' : 'بطاقة ائتمان' }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- Order Actions -->
                                <div class="order-actions">
                                    @if($order->tracking_number)
                                        <form action="{{ route('trackmyorder') }}" method="POST" class="d-inline">
                                            @csrf
                                            <input type="hidden" name="trackOrderInput" value="{{ $order->tracking_number }}">
                                            <button type="submit" class="btn btn-track">
                                                <i class="fas fa-map-marker-alt me-1"></i>تتبع الطلب
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <nav class="mt-4">
                {{ $orders->links('pagination::bootstrap-4') }}
            </nav>
        @else
            <!-- Empty State -->
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <h3>لا توجد طلبات</h3>
                @if($status && $status !== 'all')
                    <p>لا توجد طلبات بهذه الحالة حالياً</p>
                    <a href="{{ route('my-orders.index') }}" class="btn btn-outline-primary">عرض جميع الطلبات</a>
                @else
                    <p>لم تقم بأي طلبات بعد. تصفح مكتبتنا واطلب كتبك المفضلة!</p>
                    <a href="{{ route('index.page') }}" class="btn btn-primary-custom">
                        <i class="fas fa-book-open me-2"></i>تصفح الكتب
                    </a>
                @endif
            </div>
        @endif
    </div>

    @include('footer')

    <!-- Scripts -->
    <script src="{{ asset('js/header.js') }}"></script>
    <script src="{{ asset('js/scripts.js') }}"></script>
    <script>
        // Rotate chevron icon on expand/collapse
        document.querySelectorAll('.order-card-header').forEach(function(header) {
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
    </script>
</body>
</html>
