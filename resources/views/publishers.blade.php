<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>دور النشر - مكتبة الفقراء</title>
    @include('partials.meta-tags', [
        'metaTitle' => 'دور النشر - مكتبة الفقراء',
        'metaDescription' => 'تصفح جميع دور النشر المتوفرة في مكتبة الفقراء. اكتشف كتبهم ومنشوراتهم.',
    ])

    <!-- Stylesheets -->
    <link rel="stylesheet" href="{{ asset('css/headerstyle.css') }}">
    <link rel="stylesheet" href="{{ asset('css/publishers-browse.css') }}">
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
</head>
<body>
    @include('header')

    <!-- Hero -->
    <div class="publishers-hero">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">دور النشر</h1>
                <p class="hero-subtitle">تصفح مجموعتنا من دور النشر واكتشف منشوراتهم</p>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('index.page') }}"><i class="fas fa-home"></i> الرئيسية</a></li>
                        <li class="breadcrumb-item active">دور النشر</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="container py-4">

        <!-- Search & Filters -->
        <div class="filters-bar">
            <form method="GET" action="{{ route('publishers.index') }}" class="filters-form" id="publishersFilterForm">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" name="q" id="publisherSearchInput" value="{{ request('q') }}" placeholder="ابحث عن دار نشر..." autocomplete="off">
                </div>
                <select name="country" id="countryFilter" class="filter-select" onchange="liveFilter()">
                    <option value="">كل الدول</option>
                    @foreach($countries as $country)
                        <option value="{{ $country }}" {{ request('country') == $country ? 'selected' : '' }}>{{ $country }}</option>
                    @endforeach
                </select>
                <select name="sort" id="sortFilter" class="filter-select" onchange="liveFilter()">
                    <option value="name" {{ request('sort', 'name') == 'name' ? 'selected' : '' }}>الاسم</option>
                    <option value="books" {{ request('sort') == 'books' ? 'selected' : '' }}>الأكثر كتباً</option>
                    <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>الأحدث</option>
                </select>
                <button type="submit" class="filter-btn">
                    <i class="fas fa-filter"></i> بحث
                </button>
                <a href="{{ route('publishers.index') }}" class="reset-btn">
                    <i class="fas fa-times"></i> الكل
                </a>
            </form>
        </div>

        <!-- Results count -->
        <div class="results-info">
            <span id="resultsCount">{{ $publishers->total() }} دار نشر</span>
            @if(request('q'))
                <span class="search-term">نتائج: "{{ request('q') }}"</span>
            @endif
        </div>

        <!-- Publishers Grid -->
        <div class="publishers-grid" id="publishersGrid">
            @foreach($publishers as $publisher)
                <a href="{{ route('publisher.show', $publisher->id) }}" class="publisher-card">
                    <div class="publisher-card-logo">
                        @if($publisher->logo)
                            <img src="{{ asset('storage/' . $publisher->logo) }}" alt="{{ $publisher->name }}">
                        @else
                            <div class="publisher-card-placeholder">
                                <i class="fas fa-building"></i>
                            </div>
                        @endif
                    </div>
                    <div class="publisher-card-info">
                        <h3 class="publisher-card-name">{{ $publisher->name }}</h3>
                        @if($publisher->country)
                            <span class="publisher-card-country">
                                <i class="fas fa-map-marker-alt"></i> {{ $publisher->country }}
                            </span>
                        @endif
                        <span class="publisher-card-books">
                            <i class="fas fa-book"></i> {{ $publisher->books_count }} كتاب
                        </span>
                    </div>
                </a>
            @endforeach
        </div>

        <!-- Empty state -->
        <div class="empty-state" id="emptyState" style="{{ $publishers->count() === 0 ? '' : 'display:none;' }}">
            <i class="fas fa-building"></i>
            <h3>لا توجد نتائج</h3>
            <p>لم نعثر على دور نشر مطابقة لبحثك.</p>
            <a href="{{ route('publishers.index') }}" class="btn btn-primary">عرض جميع دور النشر</a>
        </div>

        <!-- Pagination -->
        <div id="paginationWrapper">
            @if($publishers instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <nav>
                    {{ $publishers->links('pagination::bootstrap-4') }}
                </nav>
            @endif
        </div>

    </div>

    @include('footer')

    <script>
        let searchTimer = null;
        const searchInput = document.getElementById('publisherSearchInput');
        const countryFilter = document.getElementById('countryFilter');
        const sortFilter = document.getElementById('sortFilter');
        const publishersGrid = document.getElementById('publishersGrid');
        const resultsCount = document.getElementById('resultsCount');
        const emptyState = document.getElementById('emptyState');
        const paginationWrapper = document.getElementById('paginationWrapper');
        const baseUrl = "{{ route('publishers.index') }}";
        const publisherShowUrl = "{{ url('/publisher') }}";
        const storageUrl = "{{ asset('storage') }}";

        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(liveFilter, 400);
        });

        function liveFilter() {
            const q = searchInput.value.trim();
            const country = countryFilter.value;
            const sort = sortFilter.value;

            const params = new URLSearchParams();
            if (q) params.set('q', q);
            if (country) params.set('country', country);
            if (sort && sort !== 'name') params.set('sort', sort);

            fetch(baseUrl + '?' + params.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                renderPublishers(data.publishers);
                resultsCount.textContent = data.total + ' دار نشر';
                paginationWrapper.style.display = 'none';

                if (data.publishers.length === 0) {
                    emptyState.style.display = '';
                    publishersGrid.style.display = 'none';
                } else {
                    emptyState.style.display = 'none';
                    publishersGrid.style.display = '';
                }
            })
            .catch(err => console.error('Search error:', err));
        }

        function renderPublishers(publishers) {
            let html = '';
            publishers.forEach(pub => {
                const logoHtml = pub.logo
                    ? `<img src="${storageUrl}/${pub.logo}" alt="${pub.name}">`
                    : `<div class="publisher-card-placeholder"><i class="fas fa-building"></i></div>`;

                const countryHtml = pub.country
                    ? `<span class="publisher-card-country"><i class="fas fa-map-marker-alt"></i> ${pub.country}</span>`
                    : '';

                const booksCount = pub.books_count || 0;

                html += `
                    <a href="${publisherShowUrl}/${pub.id}" class="publisher-card">
                        <div class="publisher-card-logo">${logoHtml}</div>
                        <div class="publisher-card-info">
                            <h3 class="publisher-card-name">${pub.name}</h3>
                            ${countryHtml}
                            <span class="publisher-card-books">
                                <i class="fas fa-book"></i> ${booksCount} كتاب
                            </span>
                        </div>
                    </a>
                `;
            });
            publishersGrid.innerHTML = html;
        }
    </script>
</body>
</html>
