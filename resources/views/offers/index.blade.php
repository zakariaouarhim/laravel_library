@extends('layouts.public')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/by-category.css') }}?v={{ filemtime(public_path('css/by-category.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/book-card.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        .books-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:1.2rem; }
        .offers-section-title { font-weight:700; margin:0 0 1.2rem; display:flex; align-items:center; gap:.5rem; }
        .promo-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:1.4rem; }
        .promo-card { background:#fff; border:1px solid #eee; border-radius:14px; overflow:hidden; box-shadow:0 4px 16px rgba(0,0,0,.06); display:flex; flex-direction:column; transition:transform .2s, box-shadow .2s; }
        .promo-card:hover { transform:translateY(-4px); box-shadow:0 10px 26px rgba(0,0,0,.12); }
        .promo-banner { position:relative; aspect-ratio:16/7; background:linear-gradient(135deg,#0d6efd,#6610f2); display:flex; align-items:center; justify-content:center; }
        .promo-banner img { width:100%; height:100%; object-fit:cover; }
        .promo-banner .promo-icon { color:#fff; font-size:2.5rem; opacity:.85; }
        .promo-deal-badge { position:absolute; top:.8rem; inset-inline-start:.8rem; background:#fff; color:#0d6efd; font-weight:800; border-radius:30px; padding:.35rem .9rem; font-size:.95rem; box-shadow:0 2px 8px rgba(0,0,0,.15); }
        .promo-body { padding:1.1rem 1.2rem; display:flex; flex-direction:column; gap:.6rem; flex:1; }
        .promo-title { font-weight:700; font-size:1.1rem; margin:0; }
        .promo-desc { color:#666; font-size:.9rem; margin:0; }
        .promo-countdown { font-size:.85rem; font-weight:600; color:#c0392b; }
        .promo-countdown.ended { color:#999; }
        .promo-cta { margin-top:auto; }
        .empty-discounts { text-align:center; color:#888; padding:2.5rem 1rem; }
    </style>
@endpush

@push('head')
    <x-seo.json-ld :schema="app(\App\Services\Seo\SchemaBuilder::class)->forBreadcrumbs([
        ['label' => 'الرئيسية', 'url' => url('/')],
        ['label' => 'العروض'],
    ])" />
@endpush

@section('content')
    @include('partials.page-hero', [
        'title'       => 'عروض المكتبة',
        'subtitle'    => 'وفّر أكثر مع عروضنا وتخفيضاتنا الحصرية',
        'icon'        => 'fas fa-tags',
        'breadcrumbs' => [
            ['label' => 'الرئيسية', 'url' => route('index.page')],
            ['label' => 'العروض'],
        ],
    ])

    <div class="container py-5">

        {{-- Library promos --}}
        @if($offers->count() > 0)
        <section class="mb-5">
            <h2 class="offers-section-title"><i class="fas fa-gift text-primary"></i> عروض المكتبة</h2>
            <div class="promo-grid">
                @foreach($offers as $offer)
                    <div class="promo-card">
                        <a href="{{ route('offer.show', $offer) }}" class="promo-banner">
                            @if($offer->banner_image)
                                <img src="{{ asset($offer->banner_image) }}" alt="{{ $offer->title }}" loading="lazy">
                            @else
                                <i class="fas fa-tags promo-icon"></i>
                            @endif
                            <span class="promo-deal-badge">{{ $offer->quantity }} كتاب بـ {{ number_format($offer->fixed_price, 0) }} د.م</span>
                        </a>
                        <div class="promo-body">
                            <h3 class="promo-title">{{ $offer->title }}</h3>
                            @if($offer->description)
                                <p class="promo-desc">{{ \Illuminate\Support\Str::limit($offer->description, 90) }}</p>
                            @endif
                            @if($offer->ends_at)
                                <div class="promo-countdown" data-ends="{{ $offer->ends_at->toIso8601String() }}">
                                    <i class="far fa-clock"></i> <span>...</span>
                                </div>
                            @endif
                            <div class="promo-cta">
                                <a href="{{ route('offer.show', $offer) }}" class="btn btn-primary btn-sm w-100">
                                    <i class="fas fa-arrow-left me-1"></i> اكتشف العرض
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
        @endif

        {{-- Discounted books --}}
        <section>
            <h2 class="offers-section-title"><i class="fas fa-percent text-danger"></i> كتب مخفّضة</h2>
            @if($discountedBooks->count() > 0)
                <div class="books-grid">
                    @foreach($discountedBooks as $book)
                        @include('partials.book-card-grid', ['book' => $book])
                    @endforeach
                </div>
                <nav class="mt-4">{{ $discountedBooks->links() }}</nav>
            @else
                <div class="empty-discounts">
                    <i class="fas fa-box-open fa-2x mb-3 d-block"></i>
                    لا توجد كتب مخفّضة حالياً. تابعنا للحصول على أحدث العروض!
                </div>
            @endif
        </section>

    </div>

    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1050;">
        <div id="cartToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <strong class="me-auto">السلة</strong>
                <small class="text-muted toast-time">الآن</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body" id="successToastMessage"></div>
        </div>
        <div id="notificationToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <strong class="me-auto">إشعار</strong>
                <small class="text-muted toast-time">الآن</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body" id="notificationToastMessage"></div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('js/header.js') }}" defer></script>
    <script src="{{ asset('js/scripts.js') }}" defer></script>
    <script src="{{ asset('js/card.js') }}" defer></script>
    <script>
        // Live countdown for promo cards with an end date.
        (function () {
            const els = document.querySelectorAll('.promo-countdown[data-ends]');
            if (!els.length) return;
            function tick() {
                const now = Date.now();
                els.forEach(function (el) {
                    const end = new Date(el.dataset.ends).getTime();
                    const span = el.querySelector('span');
                    let diff = Math.floor((end - now) / 1000);
                    if (diff <= 0) { el.classList.add('ended'); span.textContent = 'انتهى العرض'; return; }
                    const d = Math.floor(diff / 86400); diff %= 86400;
                    const h = Math.floor(diff / 3600);  diff %= 3600;
                    const m = Math.floor(diff / 60);
                    span.textContent = `ينتهي خلال ${d} يوم و ${h} ساعة و ${m} دقيقة`;
                });
            }
            tick();
            setInterval(tick, 60000);
        })();
    </script>
@endpush
