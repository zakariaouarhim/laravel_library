@extends('emails.layouts.base')

@section('title', 'تأكيد الطلب')
@section('header-icon', '&#10004;')
@section('header-title', 'تم تأكيد طلبك بنجاح')

@section('content')
    <p>مرحباً <span class="name">{{ $customerName }}</span>,</p>

    <p>شكراً لك على طلبك من <strong>مكتبة الفقراء</strong>. تم استلام طلبك وهو الآن قيد المعالجة.</p>

    <!-- Order Details -->
    <div class="detail-box">
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
            <span class="detail-value">{{ $order->payment_label }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">المبلغ الإجمالي:</span>
            <span class="detail-value total">{{ number_format($order->total_price, 2) }} د.م</span>
        </div>
    </div>

    <!-- Books List -->
    @if($order->orderDetails && $order->orderDetails->count())
    <div style="margin: 20px 0;">
        <h2 style="font-size: 16px; color: #333; margin-bottom: 10px;">الكتب المطلوبة</h2>
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
    <div class="detail-box" style="background: linear-gradient(135deg, #e8f4fd, #f0f4ff); text-align: center;">
        <h2>إدارة طلبك</h2>
        <p style="font-size: 14px;">يمكنك إدارة طلبك (إلغاء أو طلب إسترجاع) من خلال الرابط أدناه:</p>
        <div class="btn-center">
            <a href="{{ $manageUrl }}" class="btn">إدارة الطلب</a>
        </div>
        <div class="alt-link">
            <p><strong>أو انسخ هذا الرابط في متصفحك:</strong></p>
            <p><a href="{{ $manageUrl }}">{{ $manageUrl }}</a></p>
        </div>
    </div>

    <!-- Warning -->
    <div class="warning-box">
        &#9888; <strong>ملاحظة:</strong> احتفظ بهذا الرابط في مكان آمن. هو الوسيلة الوحيدة لإدارة طلبك إذا لم يكن لديك حساب.
    </div>

    <p>إذا كان لديك أي استفسار، لا تتردد في التواصل معنا.</p>
@endsection
