<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تحديث حالة الطلب</title>
    <link rel="stylesheet" href="{{ asset('css/emails/order-status-update.css') }}">
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header">
            <div class="header-icon">&#128230;</div>
            <h1>تحديث حالة طلبك</h1>
        </div>

        <!-- Content -->
        <div class="content">
            <p>مرحباً <span class="name">{{ $customerName }}</span>,</p>

            <p>نود إعلامك بأن حالة طلبك <strong>#{{ $order->id }}</strong> قد تم تحديثها.</p>

            <!-- Status Change -->
            <div class="status-change">
                <h2>تغيير الحالة</h2>
                <div class="status-arrow">
                    <span class="status-new">{{ $newStatus }}</span>
                    <span class="arrow-icon">&larr;</span>
                    <span class="status-old">{{ $oldStatus }}</span>
                </div>
            </div>

            <!-- Order Info -->
            <div class="order-info">
                <div class="info-row">
                    <span class="info-label">رقم الطلب:</span>
                    <span class="info-value">#{{ $order->id }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">رقم التتبع:</span>
                    <span class="info-value">{{ $order->tracking_number }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">المبلغ الإجمالي:</span>
                    <span class="info-value">{{ number_format($order->total_price, 2) }} د.م</span>
                </div>
            </div>

            <!-- Admin Note -->
            @if($note)
            <div class="note-section">
                <h3>ملاحظة:</h3>
                <p>{{ $note }}</p>
            </div>
            @endif

            <!-- Estimated Delivery -->
            @if($order->estimated_delivery_date)
            <div class="delivery-estimate">
                <div class="label">التسليم المتوقع</div>
                <div class="date">{{ \Carbon\Carbon::parse($order->estimated_delivery_date)->format('d/m/Y') }}</div>
            </div>
            @endif

            <!-- Manage Button -->
            @if($manageUrl)
            <div class="manage-button">
                <a href="{{ $manageUrl }}">تتبع وإدارة الطلب</a>
            </div>
            @endif

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
