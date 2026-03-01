@extends('emails.layouts.base')

@section('title', 'إعادة تعيين كلمة المرور')
@section('header-icon', '&#128274;')
@section('header-title', 'إعادة تعيين كلمة المرور')

@section('content')
    <p>مرحباً <span class="name">{{ $userName }}</span>,</p>

    <p>لقد تلقينا طلباً لإعادة تعيين كلمة المرور الخاصة بك. اضغط على الزر أدناه لإعادة تعيين كلمة المرور الخاصة بك.</p>

    <!-- Reset Button -->
    <div class="btn-center">
        <a href="{{ $resetLink }}" class="btn">إعادة تعيين كلمة المرور</a>
    </div>

    <!-- Alternative Link -->
    <div class="alt-link">
        <p><strong>أو انسخ والصق هذا الرابط في متصفحك:</strong></p>
        <p><a href="{{ $resetLink }}">{{ $resetLink }}</a></p>
    </div>

    <!-- Security Warning -->
    <div class="warning-box">
        &#9888; <strong>ملاحظة أمان:</strong> هذا الرابط سينتهي صلاحيته خلال ساعة واحدة. إذا لم تطلب إعادة تعيين كلمة المرور، يمكنك تجاهل هذا البريد الإلكتروني.
    </div>

    <p>إذا كان لديك أي مشاكل في إعادة تعيين كلمة المرور الخاصة بك، يرجى الاتصال بفريق الدعم لدينا.</p>
@endsection

@section('footer-extra')
    <p>إذا كنت لم تطلب هذا البريد الإلكتروني، يرجى حذفه فوراً.</p>
@endsection
