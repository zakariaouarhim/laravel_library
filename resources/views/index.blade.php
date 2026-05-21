@extends('layouts.public')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/homepage.css') }}">
    <link rel="stylesheet" href="{{ asset('css/book-card.css') }}">
    <link rel="stylesheet" href="{{ asset('css/carouselstyle.css') }}?v={{ filemtime(public_path('css/carouselstyle.css')) }}">
    <link href="https://fonts.googleapis.com/css2?family=Amiri&family=Scheherazade+New&display=swap" rel="stylesheet">
@endpush

@section('content')
    @include('Index-searchbar')

    <div class="layout-indexpage">
        @if($recommendedForYou->count() > 0)
        <div id="recommended-for-you">
            <x-book-carousel :books="$recommendedForYou" title="موصى لك" />
        </div>
        @endif

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

        <div id="arabic-series">
            <x-series-carousel :series="$arabicSeries" title="سلاسل عربية" />
        </div>

        <div id="Accessories">
            <x-book-carousel :books="$accessories" title="إكسسوارات القراءة" />
        </div>

        <div id="english-books">
            <x-book-carousel :books="$englishBooks" title="كتب بالإنجليزية" />
        </div>

        <div id="english-series">
            <x-series-carousel :series="$englishSeries" title="سلاسل إنجليزية" />
        </div>

        @if($recentlyViewed->count() > 0)
        <div id="recently-viewed">
            <x-book-carousel :books="$recentlyViewed" title="شاهدت مؤخراً" />
        </div>
        @endif
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/scripts.js') }}" defer></script>
    <script src="{{ asset('js/carousel.js') }}" defer></script>
    <script src="{{ asset('js/header.js') }}" defer></script>
    <script src="{{ asset('js/Index-searchbar.js') }}" defer></script>
    <script src="{{ asset('js/categories_carousel2.js') }}" defer></script>
    <script src="{{ asset('js/card.js') }}" defer></script>
@endpush
