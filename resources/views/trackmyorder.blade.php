<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>تتبع الطلب - مكتبة الفقراء</title>
    
    <!-- Favicon -->
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.rtl.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <!-- Correct CSS linking -->
    <link rel="stylesheet" href="{{ asset('css/headerstyle.css') }}">
    <link rel="stylesheet" href="{{ asset('css/trackmyorder.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
     @include('header') 
    
    <div class="floating-elements">
        <i class="fas fa-book floating-book"></i>
        <i class="fas fa-bookmark floating-book"></i>
        <i class="fas fa-book-open floating-book"></i>
        <i class="fas fa-truck floating-book"></i>
    </div>

    <div class="main-content">
        <div class="main-container">
            <div class="tracking-wrapper">
                <div class="tracking-card">
                    <div class="tracking-header">
                        <h1 class="tracking-title">تتبع طلبك</h1>
                        <div class="tracking-number-display">
                            رقم الطلب: #{{ $order->id }}
                        </div>
                    </div>

                    <div class="progress-wrapper">
                        <div class="progress-label">
                            <i class="fas fa-tasks"></i>
                            حالة التقدم
                        </div>
                        <div class="progress">
                            <div class="progress-bar" style="width: {{ $progress }}%"></div>
                        </div>
                    </div>

                    @php
                        $history = $order->statusHistory ?? collect();
                        $historyByStatus = $history->keyBy('status');
                        $isCancelled = in_array($order->status, ['cancelled', 'Failed', 'Refunded', 'returned']);
                        $statusOrder = ['pending', 'processing', 'shipped', 'delivered'];
                        $currentIndex = array_search($order->status, $statusOrder);
                    @endphp

                    <div class="timeline-section">
                        <div class="timeline-container">
                            <!-- الطلب المؤكد -->
                            @php
                                $pendingHistory = $historyByStatus->get('pending');
                            @endphp
                            <div class="timeline-item @if(!$isCancelled) completed @else failed @endif">
                                <div class="timeline-dot-icon">
                                    @if(!$isCancelled) <i class="fas fa-check"></i> @else <i class="fas fa-box"></i> @endif
                                </div>
                                <div class="timeline-content">
                                    <div class="timeline-status">تم تأكيد الطلب</div>
                                    <div class="timeline-date">
                                        {{ $pendingHistory ? \Carbon\Carbon::parse($pendingHistory->created_at)->format('d-m-Y H:i') : $order->created_at->format('d-m-Y H:i') }}
                                    </div>
                                    <div class="timeline-description">تم استلام طلبك بنجاح وتأكيد جميع التفاصيل</div>
                                </div>
                            </div>

                            <!-- تحت المعالجة -->
                            @php
                                $processingHistory = $historyByStatus->get('processing');
                                $processingClass = 'pending';
                                if ($isCancelled) $processingClass = $processingHistory ? 'completed' : 'failed';
                                elseif ($currentIndex !== false && $currentIndex >= 1) $processingClass = 'completed';
                                elseif ($order->status === 'processing') $processingClass = 'active';
                            @endphp
                            <div class="timeline-item {{ $processingClass }}">
                                <div class="timeline-dot-icon">
                                    @if($processingClass === 'completed') <i class="fas fa-check"></i>
                                    @elseif($processingClass === 'failed') <i class="fas fa-times"></i>
                                    @else <i class="fas fa-cog"></i> @endif
                                </div>
                                <div class="timeline-content">
                                    <div class="timeline-status">تحت المعالجة</div>
                                    @if($processingHistory)
                                        <div class="timeline-date">{{ \Carbon\Carbon::parse($processingHistory->created_at)->format('d-m-Y H:i') }}</div>
                                        <div class="timeline-description">تم تجهيز طلبك بنجاح</div>
                                    @elseif($order->status === 'processing')
                                        <div class="timeline-date">حالياً</div>
                                        <div class="timeline-description">جاري تجهيز طلبك وتحضيره للشحن</div>
                                    @elseif($processingClass === 'failed')
                                        <div class="timeline-date">لم يكتمل</div>
                                        <div class="timeline-description">لم يتم تجهيز الطلب</div>
                                    @else
                                        <div class="timeline-date">قريباً</div>
                                        <div class="timeline-description">سيتم تجهيز طلبك قريباً</div>
                                    @endif
                                </div>
                            </div>

                            <!-- الشحن -->
                            @php
                                $shippedHistory = $historyByStatus->get('shipped');
                                $shippedClass = 'pending';
                                if ($isCancelled) $shippedClass = $shippedHistory ? 'completed' : 'failed';
                                elseif ($currentIndex !== false && $currentIndex >= 2) $shippedClass = 'completed';
                                elseif ($order->status === 'shipped') $shippedClass = 'active';
                            @endphp
                            <div class="timeline-item {{ $shippedClass }}">
                                <div class="timeline-dot-icon">
                                    @if($shippedClass === 'completed') <i class="fas fa-check"></i>
                                    @elseif($shippedClass === 'failed') <i class="fas fa-times"></i>
                                    @else <i class="fas fa-truck"></i> @endif
                                </div>
                                <div class="timeline-content">
                                    <div class="timeline-status">تم الشحن</div>
                                    @if($shippedHistory)
                                        <div class="timeline-date">{{ \Carbon\Carbon::parse($shippedHistory->created_at)->format('d-m-Y H:i') }}</div>
                                        <div class="timeline-description">
                                            @if($order->tracking_number)
                                                رقم التتبع: <strong>{{ $order->tracking_number }}</strong>
                                            @else
                                                طلبك في الطريق إليك
                                            @endif
                                            @if($order->estimated_delivery_date && in_array($order->status, ['shipped']))
                                                <br>التسليم المتوقع: <strong>{{ \Carbon\Carbon::parse($order->estimated_delivery_date)->format('d/m/Y') }}</strong>
                                            @endif
                                        </div>
                                        @if($shippedHistory->note)
                                            <div class="timeline-description" style="margin-top: 4px; color: #3498db;">
                                                <i class="fas fa-info-circle me-1"></i>{{ $shippedHistory->note }}
                                            </div>
                                        @endif
                                    @elseif($shippedClass === 'failed')
                                        <div class="timeline-date">لم يتم</div>
                                        <div class="timeline-description">لم يتم شحن الطلب</div>
                                    @else
                                        <div class="timeline-date">قريباً</div>
                                        <div class="timeline-description">سيتم شحن طلبك قريباً</div>
                                    @endif
                                </div>
                            </div>

                            <!-- التسليم -->
                            @php
                                $deliveredHistory = $historyByStatus->get('delivered');
                                $deliveredClass = 'pending';
                                if ($order->status === 'delivered') $deliveredClass = 'completed';
                                elseif ($isCancelled) $deliveredClass = 'failed';
                            @endphp
                            <div class="timeline-item {{ $deliveredClass }}">
                                <div class="timeline-dot-icon">
                                    @if($deliveredClass === 'completed') <i class="fas fa-check"></i>
                                    @elseif($deliveredClass === 'failed') <i class="fas fa-times"></i>
                                    @else <i class="fas fa-home"></i> @endif
                                </div>
                                <div class="timeline-content">
                                    <div class="timeline-status">
                                        @if($order->status == 'delivered') تم التسليم
                                        @elseif($order->status == 'returned') تم الإرجاع
                                        @elseif($order->status == 'Refunded') تم استرجاع المبلغ
                                        @elseif($order->status == 'cancelled') تم إلغاء الطلب
                                        @elseif($order->status == 'Failed') فشل الطلب
                                        @else في انتظار التسليم @endif
                                    </div>
                                    @if($deliveredHistory)
                                        <div class="timeline-date">{{ \Carbon\Carbon::parse($deliveredHistory->created_at)->format('d-m-Y H:i') }}</div>
                                        <div class="timeline-description">شكراً لك! تم استلام طلبك بنجاح</div>
                                    @elseif($isCancelled)
                                        <div class="timeline-date">{{ $order->updated_at->format('d-m-Y') }}</div>
                                        <div class="timeline-description">
                                            @if($order->status == 'returned') تم إرجاع الطلب بنجاح
                                            @elseif($order->status == 'Refunded') تم استرجاع المبلغ إلى حسابك
                                            @elseif($order->status == 'cancelled') تم إلغاء الطلب من قبلك
                                            @else حدث خطأ في معالجة الطلب @endif
                                        </div>
                                    @else
                                        <div class="timeline-date">قريباً</div>
                                        <div class="timeline-description">سيصل طلبك إليك قريباً</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="order-details">
                        <h3 class="books-title">
                            <i class="fas fa-info-circle"></i>
                            تفاصيل الطلب
                        </h3>
                        <div class="details-grid">
                            <div class="detail-box">
                                <span class="detail-label">المبلغ الإجمالي</span>
                                <div class="detail-value">{{ number_format($order->total_price, 2) }} د.م</div>
                            </div>
                            <div class="detail-box">
                                <span class="detail-label">طريقة الدفع</span>
                                <div class="detail-value">
                                    {{ $order->payment_label }}
                                </div>
                            </div>
                            <div class="detail-box">
                                <span class="detail-label">عدد العناصر</span>
                                <div class="detail-value">{{ $order->orderDetails->count() }} عنصر</div>
                            </div>
                            <div class="detail-box">
                                <span class="detail-label">تاريخ الطلب</span>
                                <div class="detail-value">{{ $order->created_at->format('d-m-Y') }}</div>
                            </div>
                            <div class="detail-box">
                                <span class="detail-label">رقم التتبع</span>
                                <div class="detail-value">
                                    @if($order->tracking_number)
                                        {{ $order->tracking_number }}
                                    @else
                                        قيد المعالجة
                                    @endif
                                </div>
                            </div>
                            <div class="detail-box">
                                <span class="detail-label">حالة الطلب</span>
                                <div class="detail-value">
                                    @switch($order->status)
                                        @case('pending')
                                            قيد الانتظار
                                            @break
                                        @case('processing')
                                            قيد المعالجة
                                            @break
                                        @case('shipped')
                                            مشحون
                                            @break
                                        @case('delivered')
                                            تم التسليم
                                            @break
                                        @case('cancelled')
                                            ملغى
                                            @break
                                        @case('Failed')
                                            فشل
                                            @break
                                        @case('Refunded')
                                            مسترجع
                                            @break
                                        @case('returned')
                                            مرتجع
                                            @break
                                        @default
                                            {{ $order->status }}
                                    @endswitch
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($order->orderDetails->count() > 0)
                    <div class="books-section">
                        <h3 class="books-title">
                            <i class="fas fa-shopping-bag"></i>
                            الكتب المطلوبة
                        </h3>
                        @foreach($order->orderDetails as $item)
                        <div class="book-item">
                            <div class="book-info">
                                <div class="book-name">{{ $item->book->title }}</div>
                                <div class="book-quantity">الكمية: {{ $item->quantity }}</div>
                            </div>
                            <div class="book-price">{{ number_format($item->price * $item->quantity, 2) }} د.م</div>
                        </div>
                        @endforeach
                    </div>
                    @endif

                    <div class="action-buttons">
                        <button onclick="window.print()" class="btn btn-custom btn-outline-custom">
                            <i class="fas fa-print"></i> طباعة
                        </button>
                        <a href="{{ route('index.page') }}" class="btn btn-custom btn-primary-custom">
                            <i class="fas fa-arrow-right"></i> العودة للرئيسية
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>


     @include('footer') 

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Animate timeline items on scroll
            const timelineItems = document.querySelectorAll('.timeline-item');
            timelineItems.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateX(30px)';
                setTimeout(() => {
                    item.style.transition = 'all 0.5s ease';
                    item.style.opacity = '1';
                    item.style.transform = 'translateX(0)';
                }, index * 150);
            });

            // Animate detail boxes
            const detailBoxes = document.querySelectorAll('.detail-box');
            detailBoxes.forEach((box, index) => {
                box.style.opacity = '0';
                box.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    box.style.transition = 'all 0.5s ease';
                    box.style.opacity = '1';
                    box.style.transform = 'translateY(0)';
                }, 1200 + (index * 100));
            });
        });
    </script>
</body>
</html>