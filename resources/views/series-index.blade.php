{{--
    Public "all series" index. Modelled on publishers.blade.php (same filters
    bar, results line, empty state and pagination), with the series cards from
    components/series-carousel.blade.php.

    The cards sit in .books-grid rather than a .carousel-wrapper on purpose: the
    card's sizing rules live under `.carousel-wrapper .book-card` in
    carouselstyle.css, so a carousel-less page needs the grid that
    series.blade.php already uses for a series' volumes.
--}}
@extends('layouts.public')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/publisher-pages.css') }}">
    <link rel="stylesheet" href="{{ asset('css/book-card.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
@endpush

@section('content')
    @include('partials.page-hero', [
        'title'       => 'السلاسل',
        'icon'        => 'fas fa-layer-group',
        'subtitle'    => 'تصفح جميع السلاسل المتوفرة واكتشف أجزاءها كاملة',
        'breadcrumbs' => [
            ['label' => 'الرئيسية', 'url' => route('index.page')],
            ['label' => 'السلاسل'],
        ],
    ])

    <div class="container py-4">

        <!-- Search & Filters -->
        <div class="filters-bar">
            <form method="GET" action="{{ route('series.index') }}" class="filters-form">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="ابحث عن سلسلة..." autocomplete="off">
                </div>
                <select name="language" class="filter-select">
                    <option value="">كل اللغات</option>
                    @foreach($languages as $lang)
                        <option value="{{ $lang }}" {{ request('language') === $lang ? 'selected' : '' }}>
                            {{ \App\Models\Book::LANGUAGE_LABELS[$lang] ?? $lang }}
                        </option>
                    @endforeach
                </select>
                <select name="sort" class="filter-select">
                    <option value="books" {{ request('sort', 'books') === 'books' ? 'selected' : '' }}>الأكثر أجزاءً</option>
                    <option value="name" {{ request('sort') === 'name' ? 'selected' : '' }}>الاسم</option>
                    <option value="newest" {{ request('sort') === 'newest' ? 'selected' : '' }}>الأحدث</option>
                </select>
                <button type="submit" class="filter-btn">
                    <i class="fas fa-filter"></i> بحث
                </button>
                <a href="{{ route('series.index') }}" class="reset-btn">
                    <i class="fas fa-times"></i> الكل
                </a>
            </form>
        </div>

        <!-- Results count -->
        <div class="results-info">
            <span>{{ $series->total() }} {{ $series->total() === 1 ? 'سلسلة' : 'سلسلة' }}</span>
            @if(request('language'))
                <span class="search-term">{{ \App\Models\Book::LANGUAGE_LABELS[request('language')] ?? request('language') }}</span>
            @endif
            @if(request('q'))
                <span class="search-term">نتائج: "{{ request('q') }}"</span>
            @endif
        </div>

        @if($series->count() > 0)
            <div class="books-grid">
                @foreach($series as $s)
                    @php
                        $bundle = $s->bundle;
                        $bundleAvailable = $bundle && ($bundle->quantity ?? 0) > 0;
                        // total_volumes is admin-entered and nullable, so fall back
                        // to the volumes we actually hold rather than render a bare
                        // "أجزاء" with no number in front of it.
                        $volumes = $s->total_volumes ?: $s->books_count;
                    @endphp
                    <div class="book-card series-card">
                        <a class="book-image-wrapper" href="{{ route('series.show', $s) }}">
                            <img src="{{ $s->cover_image ? asset('storage/' . $s->cover_image) : asset('images/book-placeholder.png') }}"
                                 alt="{{ $s->name }}" width="200" height="280" loading="lazy"
                                 onerror="this.onerror=null;this.src='{{ asset('images/book-placeholder.png') }}'">
                        </a>

                        <div class="card-badges">
                            @if($s->is_complete)
                                <span class="badge bg-success">مكتملة</span>
                            @else
                                <span class="badge bg-warning text-dark">مستمرة</span>
                            @endif
                        </div>

                        <h6><a href="{{ route('series.show', $s) }}">{{ $s->name }}</a></h6>

                        @if($s->author)
                            <p class="book-author"><i class="fas fa-user-edit"></i> {{ $s->author->name }}</p>
                        @endif

                        <p class="series-meta">
                            <i class="fas fa-layer-group"></i>
                            {{ $volumes }} {{ $volumes == 1 ? 'جزء' : 'أجزاء' }}
                        </p>

                        @if($bundleAvailable)
                            <div class="price-section">
                                <span class="price">
                                    {{ number_format((float) $bundle->price, 2) }} <span class="currency">د.م</span>
                                    <small class="bundle-price-label">السلسلة كاملة</small>
                                </span>
                                <button class="add-btn" title="إضافة الباقة للسلة"
                                        onclick="addToCart({{ $bundle->id }},'{{ addslashes($bundle->title) }}', {{ $bundle->price }}, '{{ addslashes($bundle->image) }}')">
                                    <i class="fas fa-shopping-cart"></i>
                                </button>
                            </div>
                        @elseif($bundle)
                            <div class="price-section">
                                <span class="price">
                                    {{ number_format((float) $bundle->price, 2) }} <span class="currency">د.م</span>
                                    <small class="bundle-price-label">السلسلة كاملة</small>
                                </span>
                                <a class="add-btn" href="{{ route('moredetail2.page', $bundle) }}" title="عرض الباقة">
                                    <i class="fas fa-box"></i>
                                </a>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="pagination-wrapper">
                @if($series instanceof \Illuminate\Pagination\LengthAwarePaginator)
                    <nav>
                        {{ $series->links('pagination::bootstrap-4') }}
                    </nav>
                @endif
            </div>
        @else
            <div class="empty-state">
                <i class="fas fa-layer-group"></i>
                <h3>لا توجد نتائج</h3>
                <p>لم نعثر على سلاسل مطابقة لبحثك.</p>
                <a href="{{ route('series.index') }}" class="btn btn-primary">عرض جميع السلاسل</a>
            </div>
        @endif

    </div>
@endsection

@push('scripts')
    {{-- The bundle add-to-cart button calls addToCart(), which lives in
         scripts.js together with updateCartCount() and showCartAlert(). Not
         cart.js - that one owns the cart page itself. csrf.js is already loaded
         globally by layouts/public, so the 419-recovery wrapper is in place. --}}
    <script src="{{ asset('js/scripts.js') }}" defer></script>
@endpush
