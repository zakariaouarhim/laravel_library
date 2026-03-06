<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الشروط والأحكام - مكتبة الفقراء</title>
    @include('partials.meta-tags', [
        'metaTitle' => 'الشروط والأحكام - مكتبة الفقراء',
        'metaDescription' => 'الشروط والأحكام لاستخدام متجر مكتبة الفقراء — الطلبات، الشحن، الإرجاع، وحقوقك.',
    ])

    <link rel="stylesheet" href="{{ asset('css/variables.css') }}">
    <link rel="stylesheet" href="{{ asset('css/headerstyle.css') }}">
    <link rel="stylesheet" href="{{ asset('css/about.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.rtl.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        .terms-body { padding: 60px 0; background: #f8f9fa; }
        .terms-card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,.06);
            padding: 48px 56px;
            max-width: 860px;
            margin: 0 auto;
            font-family: 'Tajawal', sans-serif;
            line-height: 1.9;
            color: #333;
        }
        .terms-card h6 { color: #1a1a2e; }
        .terms-card ul { padding-right: 20px; }
        .terms-card li { margin-bottom: 6px; }
        @media (max-width: 768px) {
            .terms-card { padding: 28px 20px; }
        }
    </style>
</head>
<body>
    @include('header')

    <section class="terms-body">
        <div class="container">
            <div class="text-center mb-5">
                <h1 class="fw-bold" style="font-family:'Tajawal',sans-serif;">
                    <i class="fas fa-file-contract me-2 text-primary"></i>الشروط والأحكام
                </h1>
            </div>
            <div class="terms-card">
                @include('partials.terms-content')
            </div>
        </div>
    </section>

    @include('footer')

    <script src="{{ asset('js/header.js') }}"></script>
</body>
</html>
