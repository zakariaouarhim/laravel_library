@extends('emails.layouts.base')

@section('title', 'مرحباً بك في مكتبة الفقراء')
@section('header-icon', '&#128214;')
@section('header-title', 'مرحباً بك في مكتبة الفقراء!')

@section('content')
    <p>مرحباً <span class="name">{{ $user->name }}</span>,</p>

    <p>نحن سعداء بانضمامك إلى <strong>مكتبة الفقراء</strong>! تم إنشاء حسابك بنجاح ويمكنك الآن الاستمتاع بتجربة تسوق كاملة.</p>

    <div class="detail-box">
        <h2>ماذا يمكنك فعله الآن؟</h2>
        <div class="detail-row">
            <span class="detail-label">&#128218;</span>
            <span class="detail-value">تصفح مئات الكتب في مكتبتنا</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">&#128722;</span>
            <span class="detail-value">إضافة الكتب إلى سلة التسوق والشراء</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">&#9829;</span>
            <span class="detail-value">حفظ الكتب المفضلة في قائمة الأمنيات</span>
        </div>
        <div class="detail-row">
            <span class="detail-label">&#11088;</span>
            <span class="detail-value">تقييم الكتب ومشاركة رأيك</span>
        </div>
    </div>

    <div class="btn-center">
        <a href="{{ url('/') }}" class="btn">ابدأ التصفح الآن</a>
    </div>

    <p>إذا كان لديك أي استفسار، لا تتردد في التواصل معنا.</p>
@endsection
