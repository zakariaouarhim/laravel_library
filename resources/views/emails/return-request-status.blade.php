<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تحديث طلب الإسترجاع</title>
    <link rel="stylesheet" href="{{ asset('css/emails/return-request-status.css') }}">
</head>
<body>
    <div class="email-container">
        <!-- Header -->
        <div class="header status-{{ $returnRequest->status }}">
            @if($returnRequest->status === 'approved')
                <div class="header-icon">&#10004;</div>
                <h1>تم قبول طلب الإسترجاع</h1>
            @elseif($returnRequest->status === 'rejected')
                <div class="header-icon">&#10008;</div>
                <h1>تم رفض طلب الإسترجاع</h1>
            @elseif($returnRequest->status === 'refunded')
                <div class="header-icon">&#128176;</div>
                <h1>تم استرداد المبلغ</h1>
            @else
                <div class="header-icon">&#128337;</div>
                <h1>تحديث على طلب الإسترجاع</h1>
            @endif
        </div>

        <!-- Content -->
        <div class="content">
            <p>مرحباً <span class="name">{{ $customerName }}</span>,</p>

            <p>نود إعلامك بأنه تم تحديث حالة طلب الإسترجاع الخاص بك.</p>

            <!-- Return Request Details -->
            <div class="return-details">
                <h2>تفاصيل طلب الإسترجاع</h2>
                <div class="detail-row">
                    <span class="detail-label">رقم طلب الإسترجاع:</span>
                    <span class="detail-value">#{{ $returnRequest->id }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">رقم الطلب الأصلي:</span>
                    <span class="detail-value">#{{ $returnRequest->order_id }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">الحالة الجديدة:</span>
                    <span class="detail-value status-text status-{{ $returnRequest->status }}">{{ $statusText }}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">مبلغ الاسترداد:</span>
                    <span class="detail-value">{{ number_format($returnRequest->refund_amount, 2) }} ر.س</span>
                </div>
            </div>

            <!-- Admin Notes -->
            @if($returnRequest->admin_notes)
            <div class="admin-notes">
                <h2>ملاحظات الإدارة</h2>
                <p>{{ $returnRequest->admin_notes }}</p>
            </div>
            @endif

            <!-- Status specific messages -->
            @if($returnRequest->status === 'approved')
            <div class="info-box approved">
                تم قبول طلب الإسترجاع الخاص بك. سيتم التواصل معك لترتيب عملية الإرجاع والاسترداد.
            </div>
            @elseif($returnRequest->status === 'rejected')
            <div class="info-box rejected">
                نعتذر، تم رفض طلب الإسترجاع. يمكنك مراجعة ملاحظات الإدارة أعلاه لمعرفة السبب.
            </div>
            @elseif($returnRequest->status === 'refunded')
            <div class="info-box refunded">
                تم استرداد المبلغ بنجاح. سيظهر المبلغ في حسابك خلال فترة قصيرة حسب طريقة الدفع المستخدمة.
            </div>
            @endif

            <!-- Manage Link -->
            @if($manageUrl)
            <div class="manage-section">
                <p>لمتابعة طلبك:</p>
                <div class="manage-button">
                    <a href="{{ $manageUrl }}">عرض تفاصيل الطلب</a>
                </div>
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
