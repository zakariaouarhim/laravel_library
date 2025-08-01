<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>أسير الكتب - تم إنجاز الطلب</title>
    
    <!-- Favicon -->
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.rtl.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Stylesheets -->
    <link rel="stylesheet" href="{{ asset('css/success.css') }}">
    <link rel="stylesheet" href="{{ asset('css/headerstyle.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">

</head>
<body>
    @include('header')
    
    <div class="floating-elements">
        <i class="fas fa-book floating-book"></i>
        <i class="fas fa-bookmark floating-book"></i>
        <i class="fas fa-book-open floating-book"></i>
        <i class="fas fa-graduation-cap floating-book"></i>
    </div>
    
    <div class="main-container">
        <div class="success-wrapper">
            <div class="card success-card">
                <div class="success-header">
                    <div class="success-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h1 class="success-title">تم إنجاز طلبك بنجاح!</h1>
                    <p class="success-subtitle">شكراً لك على ثقتك بنا. سيتم التواصل معك قريباً لتأكيد التفاصيل النهائية وترتيب عملية التسليم.</p>
                </div>
                
                <div class="order-summary">
                    <div class="order-card">
                        <h2 class="section-title">
                            <i class="fas fa-info-circle"></i>
                            تفاصيل الطلب
                        </h2>
                        
                        <div class="detail-grid">
                            <div class="detail-item">
                                <span class="detail-label">رقم الطلب</span>
                                <div class="detail-value">#{{ $order->id }}</div>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">المبلغ الإجمالي</span>
                                <div class="detail-value">{{ number_format($order->total_price, 2) }} ر.س</div>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">طريقة الدفع</span>
                                <div class="detail-value">{{ $order->payment_method == 'cod' ? 'الدفع عند الاستلام' : 'بطاقة ائتمان' }}</div>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">رقم التتبع</span>
                                <div class="detail-value">{{ $order->tracking_number }}</div>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">حالة الطلب</span>
                                <div class="detail-value">
                                    <span class="status-badge">{{ $order->status == 'pending' ? 'قيد المعالجة' : 'مكتمل' }}</span>
                                </div>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">تاريخ الطلب</span>
                                <div class="detail-value">{{ $order->created_at->format('Y-m-d H:i') }}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="order-card">
                        <h2 class="section-title">
                            <i class="fas fa-shopping-bag"></i>
                            المنتجات المطلوبة
                        </h2>
                        
                        <div class="order-items">
                            <div class="item-header">
                                قائمة الكتب المطلوبة
                            </div>
                            @foreach($order->orderDetails as $item)
                            <div class="order-item">
                                <div class="item-info">
                                    <div class="item-name">كتاب #{{ $item->book->title }}</div>
                                    <div class="item-quantity">الكمية: {{ $item->quantity }}</div>
                                </div>
                                <div class="item-price">{{ number_format($item->price * $item->quantity, 2) }} ر.س</div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                        <a href="{{ route('index.page') }}" class="btn btn-custom btn-primary-custom">
                            <i class="fas fa-home me-2"></i>
                            العودة للرئيسية
                        </a>
                        <button onclick="window.print()" class="btn btn-custom btn-outline-custom">
                            <i class="fas fa-print me-2"></i>
                            طباعة الفاتورة
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    @include('footer')

    
    <script src="{{ asset('js/header.js') }}"></script>
    <script>
        // Add some interactive elements
        document.addEventListener('DOMContentLoaded', function() {
            // Animate detail items on scroll
            const detailItems = document.querySelectorAll('.detail-item');
            detailItems.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    item.style.transition = 'all 0.5s ease';
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                }, index * 100);
            });
            
            // Animate order items
            const orderItems = document.querySelectorAll('.order-item');
            orderItems.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateX(30px)';
                setTimeout(() => {
                    item.style.transition = 'all 0.5s ease';
                    item.style.opacity = '1';
                    item.style.transform = 'translateX(0)';
                }, 1000 + (index * 150));
            });
        });
        
        // Add print functionality
        function printOrder() {
            window.print();
        }
    </script>
</body>
</html>