@extends('emails.layouts.base')

@section('title', 'الكتاب متوفر الآن')
@section('header-title', 'مكتبة الفقراء')

@section('content')
    <p>مرحباً،<br>
    يسعدنا إخبارك بأن الكتاب الذي طلبت التنبيه عند توفره أصبح متاحاً الآن في مكتبتنا:</p>

    <div class="detail-box">
        <div style="font-size: 1.1rem; font-weight: 700; color: #1a2f4e;">{{ $book->title }}</div>
        @if($book->author_name)
            <div style="font-size: .9rem; color: #666; margin-top: 6px;"><i>{{ $book->author_name }}</i></div>
        @endif
    </div>

    <p>بادر بزيارة صفحة الكتاب وإضافته إلى سلة التسوق قبل نفاد الكمية مجدداً.
    الكميات محدودة وقد تنفد بسرعة!</p>

    <div class="btn-center">
        <a href="{{ route('moredetail2.page', $book->id) }}" class="btn">عرض الكتاب والشراء الآن</a>
    </div>
@endsection

@section('footer-extra')
    <p>تلقيت هذا البريد لأنك طلبت التنبيه عند توفر هذا الكتاب.<br>
    إذا لم تطلب ذلك، يمكنك تجاهل هذه الرسالة بأمان.</p>
@endsection
