<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مكتبة الفقراء - كتب بأسعار مناسبة للجميع</title>
    @include('partials.meta-tags', [
        'metaTitle' => 'مكتبة الفقراء - كتب بأسعار مناسبة للجميع',
        'metaDescription' => 'مكتبة الفقراء - متجر إلكتروني لبيع الكتب بأسعار مناسبة. اكتشف تشكيلة واسعة من الكتب العربية والمترجمة في مختلف المجالات.',
    ])

    
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.rtl.min.css">
    <!-- CSS -->
    <link rel="stylesheet" href="{{ asset('css/headerstyle.css') }}">
    <link rel="stylesheet" href="{{ asset('css/homepage.css') }}">
    <link rel="stylesheet" href="{{ asset('css/book-card.css') }}">
    <link rel="stylesheet" href="{{ asset('css/carouselstyle.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/variables.css') }}">
    <!-- Font Awesome -->
    <link href="https://fonts.googleapis.com/css2?family=Amiri&family=Scheherazade+New&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Favicon -->
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth
        <meta name="auth-user" content="true">
    @endauth
</head>

<body>
    @include('header')
    @include('Index-searchbar')

    <div class="layout-indexpage">
        @if($fromFollows->count() > 0)
        <div id="from-follows">
            <x-book-carousel :books="$fromFollows" title="جديد من متابعاتك" />
        </div>
        @endif

        <div id="all-books">
            <x-book-carousel :books="$books" title="جميع الكتب" />
        </div>

        @include('categories_carousel2')

        <div id="popular-books">
            <x-book-carousel :books="$popularBooks" title="الأكثر مبيعا" />
        </div>

        <div id="Accessories">
            <x-book-carousel :books="$accessories" title="إكسسوارات القراءة" />
        </div>

        <div id="english-books">
            <x-book-carousel :books="$englishBooks" title="كتب بالإنجليزية" />
        </div>

        @if($recentlyViewed->count() > 0)
        <div id="recently-viewed">
            <x-book-carousel :books="$recentlyViewed" title="شاهدت مؤخراً" />
        </div>
        @endif
    </div>

    <footer>
        @include('footer')
    </footer>

    <script src="{{ asset('js/scripts.js') }}"></script>
    <script src="{{ asset('js/carousel.js') }}"></script>
    <script src="{{ asset('js/header.js') }}"></script>
    <script src="{{ asset('js/Index-searchbar.js') }}"></script>
    <script src="{{ asset('js/categories_carousel2.js') }}"></script>
    <script src="{{ asset('js/card.js') }}"></script>
</body>
</html>
