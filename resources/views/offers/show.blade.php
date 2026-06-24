@extends('layouts.public')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/by-category.css') }}?v={{ filemtime(public_path('css/by-category.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/book-card.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        .books-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:1.2rem; }
        .offer-hero { border-radius:16px; overflow:hidden; margin-bottom:2rem; background:linear-gradient(135deg,#0d6efd,#6610f2); color:#fff; }
        .offer-hero-banner { width:100%; max-height:320px; object-fit:cover; display:block; }
        .offer-hero-body { padding:1.6rem; }
        .offer-hero-deal { display:inline-block; background:#fff; color:#0d6efd; font-weight:800; border-radius:30px; padding:.5rem 1.2rem; font-size:1.1rem; margin-bottom:.8rem; }
        .offer-hero h1 { font-weight:800; margin:0 0 .5rem; }
        .offer-hero p { opacity:.95; margin:0; }
        .offer-meta { display:flex; flex-wrap:wrap; gap:1.2rem; margin-top:1rem; font-size:.92rem; }
        .offer-cta-note { background:#fff8e1; border:1px solid #ffe082; color:#8a6d00; border-radius:10px; padding:1rem 1.2rem; margin-bottom:1.6rem; font-size:.95rem; }
        .offers-section-title { font-weight:700; margin:0 0 1.2rem; display:flex; align-items:center; gap:.5rem; }
        /* Selectable offer books */
        .offer-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(150px,1fr)); gap:1rem; }
        .offer-book { position:relative; border:2px solid #e9ecef; border-radius:12px; padding:.6rem; cursor:pointer; background:#fff; transition:.15s; text-align:center; }
        .offer-book:hover { border-color:#9ec5fe; }
        .offer-book.selected { border-color:#0d6efd; box-shadow:0 0 0 3px rgba(13,110,253,.15); }
        .offer-book img { width:100%; aspect-ratio:2/3; object-fit:cover; border-radius:8px; margin-bottom:.4rem; }
        .offer-book .t { font-size:.85rem; font-weight:600; line-height:1.3; max-height:2.6em; overflow:hidden; }
        .offer-book .p { font-size:.8rem; color:#666; margin-top:.2rem; }
        .offer-book .check { position:absolute; top:.5rem; inset-inline-start:.5rem; width:26px; height:26px; border-radius:50%; background:#0d6efd; color:#fff; display:none; align-items:center; justify-content:center; font-size:.8rem; }
        .offer-book.selected .check { display:flex; }
        .offer-book.oos { opacity:.5; cursor:not-allowed; }
        .offer-book .oos-badge { position:absolute; top:.5rem; inset-inline-end:.5rem; background:#f8d7da; color:#721c24; border-radius:12px; padding:1px 8px; font-size:.7rem; }
        .offer-book .unit-badge { position:absolute; top:.5rem; inset-inline-end:.5rem; background:#0d6efd; color:#fff; border-radius:12px; padding:1px 8px; font-size:.7rem; font-weight:700; }
        .offer-sticky-bar { position:fixed; bottom:0; inset-inline:0; background:#fff; border-top:1px solid #e3e7ee; box-shadow:0 -4px 16px rgba(0,0,0,.08); padding:.8rem 1rem; z-index:1040; display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; }
        .offer-sticky-bar .info { font-weight:600; }
        .offer-sticky-bar .info.count-ok { color:#198754; }
        /* Search + filters bar (matches authors page design) */
        .filters-bar { background:white; border-radius:14px; padding:20px; box-shadow:0 2px 12px rgba(0,0,0,.05); margin-bottom:20px; }
        .filters-form { display:flex; gap:12px; align-items:center; flex-wrap:wrap; }
        .search-box { flex:1; min-width:200px; position:relative; }
        .search-box i { position:absolute; right:14px; top:50%; transform:translateY(-50%); color:#adb5bd; }
        .search-box input { width:100%; padding:10px 42px 10px 14px; border:2px solid var(--color-border); border-radius:10px; font-size:15px; font-family:'Tajawal',sans-serif; transition:border-color .3s; }
        .search-box input:focus { outline:none; border-color:var(--color-secondary); }
        .filter-select { padding:10px 14px; border:2px solid var(--color-border); border-radius:10px; font-size:14px; font-family:'Tajawal',sans-serif; background:white; min-width:140px; cursor:pointer; transition:border-color .3s; }
        .filter-select:focus { outline:none; border-color:var(--color-secondary); }
        .filter-btn { padding:10px 20px; background:var(--gradient-secondary); color:white; border:none; border-radius:10px; font-size:14px; font-weight:600; font-family:'Tajawal',sans-serif; cursor:pointer; transition:all .3s; display:flex; align-items:center; gap:6px; }
        .filter-btn:hover { background:var(--gradient-secondary-dark); transform:translateY(-1px); }
        .reset-btn { padding:10px 20px; background:var(--color-bg-light); color:var(--color-text-grey); border:2px solid var(--color-border); border-radius:10px; font-size:14px; font-weight:600; font-family:'Tajawal',sans-serif; cursor:pointer; transition:all .3s; display:flex; align-items:center; gap:6px; text-decoration:none; }
        .reset-btn:hover { background:var(--color-border); color:#495057; }
    </style>
@endpush

@push('head')
    <x-seo.json-ld :schema="app(\App\Services\Seo\SchemaBuilder::class)->forBreadcrumbs([
        ['label' => 'الرئيسية', 'url' => url('/')],
        ['label' => 'العروض', 'url' => route('offers.index')],
        ['label' => $offer->title],
    ])" />
@endpush

@section('content')
    @include('partials.page-hero', [
        'title'       => $offer->title,
        'icon'        => 'fas fa-tags',
        'breadcrumbs' => [
            ['label' => 'الرئيسية', 'url' => route('index.page')],
            ['label' => 'العروض', 'url' => route('offers.index')],
            ['label' => $offer->title],
        ],
    ])

    <div class="container py-5">

        <div class="offer-hero">
            @if($offer->banner_image)
                <img src="{{ asset($offer->banner_image) }}" alt="{{ $offer->title }}" class="offer-hero-banner">
            @endif
            <div class="offer-hero-body">
                <span class="offer-hero-deal">{{ $offer->quantity }} كتاب بـ {{ number_format($offer->fixed_price, 0) }} د.م</span>
                @if($offer->description)
                    <p>{{ $offer->description }}</p>
                @endif
                <div class="offer-meta">
                    @if($offer->ends_at)
                        <span class="promo-countdown" data-ends="{{ $offer->ends_at->toIso8601String() }}">
                            <i class="far fa-clock"></i> <span>...</span>
                        </span>
                    @endif
                    <span><i class="fas fa-book me-1"></i> {{ $eligibleBooks->total() + collect($units)->sum('count') }} كتاب متاح ضمن العرض</span>
                </div>
            </div>
        </div>

        <div class="offer-cta-note">
            <i class="fas fa-info-circle me-1"></i>
            اختر <strong>{{ $offer->quantity }}</strong> كتب على الأقل من القائمة أدناه واحصل عليها بسعر {{ number_format($offer->fixed_price, 0) }} درهم.
            @if(count($units) > 0)<span class="d-block mt-1 small">ملاحظة: اختيار سلسلة أو باقة يُحتسب بعدد كتبها.</span>@endif
        </div>

        <section>
            <h2 class="offers-section-title"><i class="fas fa-book-open text-primary"></i> اختر كتبك</h2>
            @if($eligibleBooks->total() > 0 || count($units) > 0)

                @if(count($units) > 0)
                    <h3 class="fs-6 fw-bold mb-2"><i class="fas fa-layer-group text-primary me-1"></i> سلاسل وباقات</h3>
                    <div class="offer-grid mb-4" id="offerUnits">
                        @foreach($units as $u)
                            <div class="offer-book offer-unit" data-book-ids='@json($u['book_ids'])' data-count="{{ $u['count'] }}">
                                <div class="check"><i class="fas fa-check"></i></div>
                                <span class="unit-badge">{{ $u['count'] }} كتب</span>
                                <img src="{{ $u['image'] ? asset($u['image']) : asset('images/book-placeholder.png') }}" alt="{{ $u['label'] }}" loading="lazy"
                                     onerror="this.src='{{ asset('images/book-placeholder.png') }}'">
                                <div class="t">{{ $u['label'] }}</div>
                                <div class="p">{{ $u['type'] === 'series' ? 'سلسلة' : 'باقة' }}</div>
                            </div>
                        @endforeach
                    </div>
                    @if($eligibleBooks->total() > 0)
                        <h3 class="fs-6 fw-bold mb-2"><i class="fas fa-book text-primary me-1"></i> كتب مفردة</h3>
                    @endif
                @endif

                <div class="filters-bar" @if($eligibleBooks->total() === 0) style="display:none;" @endif>
                    <div class="filters-form">
                        <div class="search-box">
                            <i class="fas fa-search"></i>
                            <input type="text" id="offerSearch" autocomplete="off" placeholder="ابحث بالعنوان أو اسم المؤلف...">
                        </div>
                        @if($languages->count() > 1)
                            <select id="offerFilterLang" class="filter-select">
                                <option value="">كل اللغات</option>
                                @foreach($languages as $lang)
                                    <option value="{{ $lang }}">{{ ['arabic' => 'العربية', 'english' => 'الإنجليزية', 'french' => 'الفرنسية'][strtolower($lang)] ?? $lang }}</option>
                                @endforeach
                            </select>
                        @endif
                        @if($filterCategories->count() > 1)
                            <select id="offerFilterCat" class="filter-select">
                                <option value="">كل التصنيفات</option>
                                @foreach($filterCategories as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        @endif
                        <button type="button" id="offerSearchBtn" class="filter-btn"><i class="fas fa-filter"></i> بحث</button>
                        <button type="button" id="offerResetBtn" class="reset-btn"><i class="fas fa-times"></i> الكل</button>
                    </div>
                </div>

                <div class="offer-grid" id="offerGrid"
                     data-books-url="{{ route('offer.books', $offer) }}">
                    @foreach($eligibleBooks as $book)
                        @php $oos = ($book->quantity ?? 0) <= 0; @endphp
                        <div class="offer-book {{ $oos ? 'oos' : '' }}" data-id="{{ $book->id }}" data-oos="{{ $oos ? 1 : 0 }}">
                            <div class="check"><i class="fas fa-check"></i></div>
                            @if($oos)<span class="oos-badge">نفد</span>@endif
                            <img src="{{ asset($book->image) }}" alt="{{ $book->title }}" loading="lazy"
                                 onerror="this.src='{{ asset('images/book-placeholder.png') }}'">
                            <div class="t">{{ $book->title }}</div>
                            <div class="p">{{ optional($book->primaryAuthor)->name ?? 'مؤلف غير معروف' }}</div>
                        </div>
                    @endforeach
                </div>

                <div id="offerNoResults" class="text-muted text-center py-3" style="display:none;">
                    لا توجد كتب مطابقة لبحثك.
                </div>

                <div class="text-center mt-4" id="loadMoreWrap" style="{{ $eligibleBooks->hasMorePages() ? '' : 'display:none;' }}">
                    <button type="button" id="loadMoreBtn" class="btn btn-outline-primary" data-next-page="2">
                        <i class="fas fa-plus me-1"></i> تحميل المزيد
                    </button>
                </div>
            @else
                <p class="text-muted">لا توجد كتب مضافة لهذا العرض بعد.</p>
            @endif
        </section>

    </div>

    @if($eligibleBooks->total() > 0 || count($units) > 0)
        <div class="offer-sticky-bar">
            <span class="info">اخترت <span id="selCount">0</span> / {{ $offer->quantity }} كتاب</span>
            <button id="addOfferBtn" class="btn btn-primary" disabled>
                <i class="fas fa-cart-plus me-1"></i> أضف العرض للسلة ({{ number_format($offer->fixed_price, 0) }} د.م)
            </button>
        </div>
        <div style="height:80px;"></div>
    @endif

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
        (function () {
            const els = document.querySelectorAll('.promo-countdown[data-ends]');
            if (!els.length) return;
            function tick() {
                const now = Date.now();
                els.forEach(function (el) {
                    const end = new Date(el.dataset.ends).getTime();
                    const span = el.querySelector('span');
                    let diff = Math.floor((end - now) / 1000);
                    if (diff <= 0) { span.textContent = 'انتهى العرض'; return; }
                    const d = Math.floor(diff / 86400); diff %= 86400;
                    const h = Math.floor(diff / 3600);  diff %= 3600;
                    const m = Math.floor(diff / 60);
                    span.textContent = `ينتهي خلال ${d} يوم و ${h} ساعة و ${m} دقيقة`;
                });
            }
            tick();
            setInterval(tick, 60000);
        })();

        // Offer selection: pick exactly N, then add the group to the cart.
        (function () {
            const N = {{ (int) $offer->quantity }};
            const addUrl = "{{ route('cart.offer.add', $offer) }}";
            const cartUrl = "{{ route('cart.page') }}";
            const token = "{{ csrf_token() }}";
            const placeholder = "{{ asset('images/book-placeholder.png') }}";
            const btn = document.getElementById('addOfferBtn');
            const countEl = document.getElementById('selCount');
            const grid = document.getElementById('offerGrid');
            if (!btn || !grid) return;
            const selected = new Set();

            function refresh() {
                countEl.textContent = selected.size;
                countEl.parentElement.classList.toggle('count-ok', selected.size >= N);
                btn.disabled = selected.size < N; // allow reaching N or more
            }

            // Loose book cards (event delegation; "load more" appends more).
            grid.addEventListener('click', function (e) {
                const card = e.target.closest('.offer-book');
                if (!card || card.dataset.oos === '1') return;
                const id = card.dataset.id;
                if (selected.has(id)) { selected.delete(id); card.classList.remove('selected'); }
                else { selected.add(id); card.classList.add('selected'); }
                refresh();
            });

            // Series/bundle unit cards: toggle all member books together.
            const unitsEl = document.getElementById('offerUnits');
            if (unitsEl) {
                unitsEl.addEventListener('click', function (e) {
                    const card = e.target.closest('.offer-unit');
                    if (!card) return;
                    let ids;
                    try { ids = JSON.parse(card.dataset.bookIds); } catch (_) { return; }
                    const allSelected = ids.every(id => selected.has(String(id)));
                    ids.forEach(id => allSelected ? selected.delete(String(id)) : selected.add(String(id)));
                    card.classList.toggle('selected', !allSelected);
                    refresh();
                });
            }

            btn.addEventListener('click', function () {
                if (selected.size < N) return;
                btn.disabled = true;
                fetch(addUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                    body: JSON.stringify({ book_ids: Array.from(selected).map(Number) }),
                })
                .then(r => r.json())
                .then(d => {
                    if (d.success) { window.location.href = cartUrl; }
                    else { alert(d.message || 'حدث خطأ'); btn.disabled = false; }
                })
                .catch(() => { alert('حدث خطأ في الاتصال'); btn.disabled = false; });
            });

            // Search within the offer + "load more", both driven by one paginated fetch.
            const booksUrl  = grid.dataset.booksUrl;
            const searchEl  = document.getElementById('offerSearch');
            const wrap      = document.getElementById('loadMoreWrap');
            const loadMore  = document.getElementById('loadMoreBtn');
            const noResults = document.getElementById('offerNoResults');
            const langEl    = document.getElementById('offerFilterLang');
            const catEl     = document.getElementById('offerFilterCat');
            let currentQuery = '';
            let loading = false;

            function buildCard(b) {
                const oos = !b.in_stock;
                const card = document.createElement('div');
                card.className = 'offer-book' + (oos ? ' oos' : '');
                card.dataset.id = b.id;
                card.dataset.oos = oos ? '1' : '0';
                if (selected.has(String(b.id))) card.classList.add('selected');
                card.innerHTML =
                    '<div class="check"><i class="fas fa-check"></i></div>' +
                    (oos ? '<span class="oos-badge">نفد</span>' : '') +
                    `<img src="${b.image}" alt="" loading="lazy" onerror="this.src='${placeholder}'">` +
                    '<div class="t"></div><div class="p"></div>';
                card.querySelector('.t').textContent = b.title || '';
                card.querySelector('.p').textContent = b.author || 'مؤلف غير معروف';
                return card;
            }

            function fetchPage(page, replace) {
                if (loading) return;
                loading = true;
                if (loadMore) { loadMore.disabled = true; loadMore.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> جاري التحميل...'; }

                const params = new URLSearchParams({ page });
                if (currentQuery) params.set('q', currentQuery);
                if (langEl && langEl.value) params.set('language', langEl.value);
                if (catEl && catEl.value) params.set('category', catEl.value);

                fetch(`${booksUrl}?${params.toString()}`, { headers: { 'Accept': 'application/json' } })
                    .then(r => r.json())
                    .then(d => {
                        if (replace) grid.innerHTML = '';
                        (d.books || []).forEach(b => grid.appendChild(buildCard(b)));

                        noResults.style.display = (replace && (!d.books || d.books.length === 0)) ? '' : 'none';

                        if (d.has_more) {
                            wrap.style.display = '';
                            loadMore.dataset.nextPage = d.next_page;
                        } else {
                            wrap.style.display = 'none';
                        }
                        if (loadMore) { loadMore.disabled = false; loadMore.innerHTML = '<i class="fas fa-plus me-1"></i> تحميل المزيد'; }
                        loading = false;
                    })
                    .catch(() => {
                        if (loadMore) { loadMore.disabled = false; loadMore.innerHTML = '<i class="fas fa-plus me-1"></i> تحميل المزيد'; }
                        loading = false;
                    });
            }

            if (loadMore) loadMore.addEventListener('click', () => fetchPage(loadMore.dataset.nextPage, false));

            // Language / category filters re-run the paginated fetch from page 1.
            if (langEl) langEl.addEventListener('change', () => fetchPage(1, true));
            if (catEl)  catEl.addEventListener('change', () => fetchPage(1, true));

            const searchBtn = document.getElementById('offerSearchBtn');
            const resetBtn  = document.getElementById('offerResetBtn');

            function runSearch() {
                currentQuery = searchEl.value.trim();
                fetchPage(1, true); // reset to filtered page 1
            }

            if (searchEl) {
                let timer = null;
                searchEl.addEventListener('input', function () {
                    clearTimeout(timer);
                    timer = setTimeout(runSearch, 300);
                });
                searchEl.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') { e.preventDefault(); clearTimeout(timer); runSearch(); }
                });
            }
            if (searchBtn) searchBtn.addEventListener('click', runSearch);
            if (resetBtn) resetBtn.addEventListener('click', function () {
                searchEl.value = '';
                if (langEl) langEl.value = '';
                if (catEl) catEl.value = '';
                currentQuery = '';
                fetchPage(1, true);
                searchEl.focus();
            });
        })();
    </script>
@endpush
