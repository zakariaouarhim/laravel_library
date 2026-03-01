@extends('emails.layouts.base')

@section('title', 'تحديث حالة الطلب')
@section('header-icon', '&#128230;')
@section('header-title', 'تحديث حالة طلبك')

@section('content')
    <p>مرحباً <span class="name">{{ $customerName }}</span>,</p>

    <p>نود إعلامك بأن حالة طلبك <strong>#{{ $order->id }}</strong> قد تم تحديثها.</p>

    <!-- Status Change -->
    <div class="detail-box" style="text-align: center;">
        <h2>تغيير الحالة</h2>
        <div class="status-arrow">
            <span class="status-new">{{ $newStatus }}</span>
            <span style="font-size: 20px; color: #999;">&larr;</span>
            <span class="status-old">{{ $oldStatus }}</span>
        </div>
    </div>

    <!-- Order Info -->
    <div class="detail-box">
        <div class="detail-row">
            <span class="detail-label">رقم الطلب:</span>
            <span class="detail-value">#{{ $order->id }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">رقم التتبع:</span>
            <span class="detail-value">{{ $order->tracking_number }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">المبلغ الإجمالي:</span>
            <span class="detail-value">{{ number_format($order->total_price, 2) }} د.م</span>
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
    <div class="btn-center">
        <a href="{{ $manageUrl }}" class="btn">تتبع وإدارة الطلب</a>
    </div>
    @endif

    <p>إذا كان لديك أي استفسار، لا تتردد في التواصل معنا.</p>
@endsection
