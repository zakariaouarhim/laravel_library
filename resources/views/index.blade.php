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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.rtl.min.css" integrity="sha384-gXt9imSW0VcJVHezoNQsP+TNrjYXoGcrqBZJpry9zJt8PCQjobwmhMGaDHTASo9N" crossorigin="anonymous">
    <!-- Correct CSS linking -->
    <link rel="stylesheet" href="{{ asset('css/headerstyle.css') }}">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/Index-searchbar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/book-card.css') }}">
    <link rel="stylesheet" href="{{ asset('css/carouselstyle.css') }}">
    <link rel="stylesheet" href="{{ asset('css/categories_carousel2.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
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

    <div class="layout-indexpage ">
            
        <div id="all-books">
            <x-book-carousel :books="$books" title=" All books " />
        </div>
        <!-- carousel categories -->
        @include('categories_carousel2')
        <div id="popular-books">
        <x-book-carousel :books="$popularBooks" title=" الأكثر مبيعا " />
        </div>
        <div id="Accessories">
        <x-book-carousel :books="$accessories" title="  إكسسوارات القراءة " />
        </div>
        <x-book-carousel :books="$EnglichBooks" title=" EnglichBooks  " />

        @if($recentlyViewed->count() > 0)
        <div id="recently-viewed">
            <x-book-carousel :books="$recentlyViewed" title=" شاهدت مؤخراً " />
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


