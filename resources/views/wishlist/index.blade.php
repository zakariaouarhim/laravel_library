<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>قائمة الأمنيات</title>
    <!-- Stylesheets -->
    <link rel="stylesheet" href="{{ asset('css/headerstyle.css') }}">
    <link rel="stylesheet" href="{{ asset('css/by-category.css') }}">
    <link rel="stylesheet" href="{{ asset('css/carouselstyle.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    <!-- Favicon -->
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.rtl.min.css">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts - Tajawal -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth
        <meta name="auth-user" content="true">
    @endauth
</head>
<body>
    @include('header')

    <!-- Hero Banner -->
    <div class="category-hero">
        <div class="container">
            <div class="hero-content text-center">
                <h1 class="hero-title"><i class="fas fa-heart me-2"></i> قائمة الأمنيات</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb justify-content-center">
                        <li class="breadcrumb-item"><a href="{{ url('/') }}"><i class="fas fa-home home-icon"></i> الرئيسية</a></li>
                        <li class="breadcrumb-item active" aria-current="page">قائمة الأمنيات</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="container py-4">
        @if($wishlist->count() > 0)
            <div class="content-header mb-4">
                <div class="results-count">
                    <p><strong>{{ $wishlist->count() }}</strong> كتاب في قائمة أمنياتك</p>
                </div>
            </div>

            <div class="books-container grid-view">
                @foreach ($wishlist as $book)
                    @include('partials.book-card-grid', ['book' => $book])
                @endforeach
            </div>
        @else
            <div class="empty-state text-center py-5">
                <div class="empty-state-icon"><i class="fas fa-heart-broken fa-4x text-muted mb-3"></i></div>
                <h3>قائمة الأمنيات فارغة</h3>
                <p class="text-muted">لم تقم بإضافة أي كتاب إلى قائمة أمنياتك بعد.</p>
                <a href="{{ url('/') }}" class="btn btn-primary mt-3">
                    <i class="fas fa-book me-2"></i>تصفح الكتب
                </a>
            </div>
        @endif
    </div>

    @include('footer')

    <!-- Scripts -->
    
    <script src="{{ asset('js/header.js') }}"></script>
    <script src="{{ asset('js/scripts.js') }}"></script>
    <script src="{{ asset('js/card.js') }}"></script>
</body>
</html>
