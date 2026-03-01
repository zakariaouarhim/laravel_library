@extends('emails.layouts.base')

@section('title', 'تحديث طلب الإسترجاع')

@section('header-class', $returnRequest->status === 'approved' ? 'header-approved' : ($returnRequest->status === 'rejected' ? 'header-rejected' : ''))

@section('extra-styles')
<style>
    .header.header-approved { background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%); }
    .header.header-rejected { background: linear-gradient(135deg, #c0392b 0%, #e74c3c 100%); }
</style>
@endsection

@section('header-icon')
    @if($returnRequest->status === 'approved')
        &#10004;
    @elseif($returnRequest->status === 'rejected')
        &#10008;
    @elseif($returnRequest->status === 'refunded')
        &#128176;
    @else
        &#128337;
    @endif
@endsection

@section('header-title')
    @if($returnRequest->status === 'approved')
        تم قبول طلب الإسترجاع
    @elseif($returnRequest->status === 'rejected')
        تم رفض طلب الإسترجاع
    @elseif($returnRequest->status === 'refunded')
        تم استرداد المبلغ
    @else
        تحديث على طلب الإسترجاع
    @endif
@endsection

@section('content')
    <p>مرحباً <span class="name">{{ $customerName }}</span>,</p>

    <p>نود إعلامك بأنه تم تحديث حالة طلب الإسترجاع الخاص بك.</p>

    <!-- Return Request Details -->
    <div class="detail-box">
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
            <span class="detail-value">{{ $statusText }}</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">مبلغ الاسترداد:</span>
            <span class="detail-value">{{ number_format($returnRequest->refund_amount, 2) }} د.م</span>
        </div>
    </div>

    <!-- Admin Notes -->
    @if($returnRequest->admin_notes)
    <div class="note-section">
        <h3>ملاحظات الإدارة</h3>
        <p>{{ $returnRequest->admin_notes }}</p>
    </div>
    @endif

    <!-- Status specific messages -->
    @if($returnRequest->status === 'approved')
    <div class="info-box success">
        تم قبول طلب الإسترجاع الخاص بك. سيتم التواصل معك لترتيب عملية الإرجاع والاسترداد.
    </div>
    @elseif($returnRequest->status === 'rejected')
    <div class="info-box danger">
        نعتذر، تم رفض طلب الإسترجاع. يمكنك مراجعة ملاحظات الإدارة أعلاه لمعرفة السبب.
    </div>
    @elseif($returnRequest->status === 'refunded')
    <div class="info-box info">
        تم استرداد المبلغ بنجاح. سيظهر المبلغ في حسابك خلال فترة قصيرة حسب طريقة الدفع المستخدمة.
    </div>
    @endif

    <!-- Manage Link -->
    @if($manageUrl)
    <div class="btn-center">
        <a href="{{ $manageUrl }}" class="btn">عرض تفاصيل الطلب</a>
    </div>
    @endif

    <p>إذا كان لديك أي استفسار، لا تتردد في التواصل معنا.</p>
@endsection
