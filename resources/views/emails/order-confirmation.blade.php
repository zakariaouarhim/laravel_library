<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تأكيد الطلب</title>
    <link rel="stylesheet" href="{{ asset('css/emails/order-confirmation.css') }}">
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <div class="header-icon">&#10004;</div>
            <h1>تم تأكيد طلبك بنجاح</h1>
        </div>

        <!-- Content -->
        <div class="content">
            <p>مرحباً <span class="name">{{ $customerName }}</span>,</p>

            <p>شكراً لك على طلبك من <strong>أسير الكتب</strong>. تم استلام طلبك وهو الآن قيد المعالجة.</p>

            <!-- Order Details -->
            <div class="order-details">
                <h2>تفاصيل الطلب</h2>
                <div class="detail-row">
                    <span class="detail-label">رقم الطلب:</span>
                    <span class="detail-value">#{{ $order->id }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">رقم التتبع:</span>
                    <span class="detail-value">{{ $order->tracking_number }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">طريقة الدفع:</span>
                    <span class="detail-value">{{ $order->payment_method == 'cod' ? 'الدفع عند الاستلام' : 'بطاقة ائتمان' }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">المبلغ الإجمالي:</span>
                    <span class="detail-value total">{{ number_format($order->total_price, 2) }} د.م</span>
                </div>
            </div>

            <!-- Books List -->
            @if($order->orderDetails && $order->orderDetails->count())
            <div class="books-section">
                <h2>الكتب المطلوبة</h2>
                @foreach($order->orderDetails as $item)
                <div class="book-row">
                    <span class="book-name">{{ $item->book ? $item->book->title : 'كتاب #'.$item->book_id }}</span>
                    <span class="book-qty">x{{ $item->quantity }}</span>
                    <span class="book-price">{{ number_format($item->price * $item->quantity, 2) }} د.م</span>
                </div>
                @endforeach
            </div>
            @endif

            <!-- Management Link -->
            <div class="manage-section">
                <h2>إدارة طلبك</h2>
                <p>يمكنك إدارة طلبك (إلغاء أو طلب إسترجاع) من خلال الرابط أدناه:</p>
                <div class="manage-button">
                    <a href="{{ $manageUrl }}">إدارة الطلب</a>
                </div>
                <div class="alternative-link">
                    <p><strong>أو انسخ هذا الرابط في متصفحك:</strong></p>
                    <p><a href="{{ $manageUrl }}">{{ $manageUrl }}</a></p>
                </div>
            </div>

            <!-- Warning -->
            <div class="warning">
                &#9888; <strong>ملاحظة:</strong> احتفظ بهذا الرابط في مكان آمن. هو الوسيلة الوحيدة لإدارة طلبك إذا لم يكن لديك حساب.
            </div>

            <p>إذا كان لديك أي استفسار، لا تتردد في التواصل معنا.</p>

            <p>شكراً لك،<br>
            <strong>فريق أسير الكتب</strong></p>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p>&copy; {{ date('Y') }} أسير الكتب. جميع الحقوق محفوظة.</p>
        </div>
    </div>
</body>
</html>
