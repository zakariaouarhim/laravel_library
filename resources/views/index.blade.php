@extends('layouts.public')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/homepage.css') }}">
    <link rel="stylesheet" href="{{ asset('css/book-card.css') }}">
    <link rel="stylesheet" href="{{ asset('css/carouselstyle.css') }}?v={{ filemtime(public_path('css/carouselstyle.css')) }}">
    <link href="https://fonts.googleapis.com/css2?family=Amiri&family=Scheherazade+New&display=swap" rel="stylesheet">
@endpush

@push('head')
    <x-seo.json-ld :schema="$schemas['website']" />
    @isset($schemas['bookstore'])
        <x-seo.json-ld :schema="$schemas['bookstore']" />
    @endisset
@endpush

@section('content')
    @include('Index-searchbar')

    <div class="layout-indexpage">
        {{-- All carousels (built-in + custom) are admin-managed and resolved by
             HomeCarouselService, ordered by sort_order. Empty ones are filtered out. --}}
        @foreach($homeCarousels as $c)
            <div id="{{ $c->dom_id }}">
                @if($c->render === 'series')
                    <x-series-carousel :series="$c->payload" :title="$c->title" />
                @elseif($c->render === 'categories')
                    @include('categories_carousel2', ['categorieIcons' => $c->payload, 'title' => $c->title])
                @else
                    <x-book-carousel :books="$c->payload" :title="$c->title" />
                @endif
            </div>
        @endforeach
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
