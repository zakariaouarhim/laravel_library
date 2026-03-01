@extends('emails.layouts.base')

@section('title', 'تم استلام رسالتك')
@section('header-icon', '&#9993;')
@section('header-title', 'تم استلام رسالتك بنجاح')

@section('content')
    <p>مرحباً <span class="name">{{ $customerName }}</span>,</p>

    <p>نشكرك على تواصلك معنا. تم استلام رسالتك بخصوص "<strong>{{ $subject }}</strong>" وسيقوم فريقنا بمراجعتها والرد عليك في أقرب وقت ممكن.</p>

    <div class="info-box info">
        عادةً ما نرد على الرسائل خلال 24-48 ساعة في أيام العمل.
    </div>

    <p>إذا كان لديك أي استفسار إضافي، لا تتردد في التواصل معنا مجدداً.</p>
@endsection
