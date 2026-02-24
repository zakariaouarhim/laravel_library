<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>المؤلفون - مكتبة الفقراء</title>
    @include('partials.meta-tags', [
        'metaTitle' => 'المؤلفون - مكتبة الفقراء',
        'metaDescription' => 'تصفح جميع المؤلفين المتوفرين في مكتبة الفقراء. اكتشف كتبهم وسيرهم الذاتية.',
    ])

    <!-- Stylesheets -->
    <link rel="stylesheet" href="{{ asset('css/headerstyle.css') }}">
    <link rel="stylesheet" href="{{ asset('css/authors-browse.css') }}">
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

    @include('partials.page-hero', [
        'title'       => 'المؤلفون',
        'icon'        => 'fas fa-pen-fancy',
        'subtitle'    => 'تصفح مجموعتنا من المؤلفين واكتشف أعمالهم',
        'breadcrumbs' => [
            ['label' => 'الرئيسية', 'url' => route('index.page')],
            ['label' => 'المؤلفون'],
        ],
    ])

    <div class="container py-4">

        <!-- Search & Filters -->
        <div class="filters-bar">
            <form method="GET" action="{{ route('authors.index') }}" class="filters-form" id="authorsFilterForm">
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" name="q" id="authorSearchInput" value="{{ request('q') }}" placeholder="ابحث عن مؤلف..." autocomplete="off">
                </div>
                <select name="nationality" id="nationalityFilter" class="filter-select" onchange="liveFilter()">
                    <option value="">كل الجنسيات</option>
                    @foreach($nationalities as $nat)
                        <option value="{{ $nat }}" {{ request('nationality') == $nat ? 'selected' : '' }}>{{ $nat }}</option>
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
                <a href="{{ route('authors.index') }}" class="reset-btn" id="resetBtn">
                    <i class="fas fa-times"></i> الكل
                </a>
            </form>
        </div>

        <!-- Results count -->
        <div class="results-info">
            <span id="resultsCount">{{ $authors->total() }} مؤلف</span>
            @if(request('q'))
                <span class="search-term" id="searchTermBadge">نتائج: "{{ request('q') }}"</span>
            @endif
        </div>

        <!-- Authors Grid -->
        <div class="authors-grid" id="authorsGrid">
            @foreach($authors as $author)
                <div class="author-card">
                    <a href="{{ route('author.show', $author->id) }}" class="author-card-avatar">
                        @if($author->profile_image)
                            <img src="{{ asset('storage/' . $author->profile_image) }}" alt="{{ $author->name }}">
                        @else
                            <div class="author-card-placeholder">
                                <span>{{ mb_substr($author->name, 0, 1) }}</span>
                            </div>
                        @endif
                    </a>
                    <div class="author-card-info">
                        <a href="{{ route('author.show', $author->id) }}" class="author-card-name-link">
                            <h3 class="author-card-name">{{ $author->name }}</h3>
                        </a>
                        @if($author->nationality)
                            <span class="author-card-nationality">
                                <i class="fas fa-globe-africa"></i> {{ $author->nationality }}
                            </span>
                        @endif
                        <span class="author-card-books">
                            <i class="fas fa-book"></i> {{ $author->primary_books_count }} كتاب
                        </span>
                    </div>
                    @auth
                        @php $isFollowing = \App\Models\Follow::isFollowing(Auth::id(), 'author', $author->id); @endphp
                        <button class="follow-btn-card {{ $isFollowing ? 'following' : '' }}"
                                onclick="toggleFollow('author', {{ $author->id }}, this)"
                                title="{{ $isFollowing ? 'إلغاء المتابعة' : 'متابعة' }}">
                            <i class="fas {{ $isFollowing ? 'fa-check' : 'fa-plus' }}"></i>
                        </button>
                    @endauth
                </div>
            @endforeach
        </div>

        <!-- Empty state (hidden by default, shown via JS) -->
        <div class="empty-state" id="emptyState" style="{{ $authors->count() === 0 ? '' : 'display:none;' }}">
            <i class="fas fa-user-slash"></i>
            <h3>لا توجد نتائج</h3>
            <p>لم نعثر على مؤلفين مطابقين لبحثك.</p>
            <a href="{{ route('authors.index') }}" class="btn btn-primary">عرض جميع المؤلفين</a>
        </div>

        <!-- Pagination -->
        <div id="paginationWrapper">
            @if($authors instanceof \Illuminate\Pagination\Paginator || $authors instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <nav>
                    {{ $authors->links('pagination::bootstrap-4') }}
                </nav>
            @endif
        </div>

    </div>

    @include('footer')

    <script>
        let searchTimer = null;
        const searchInput = document.getElementById('authorSearchInput');
        const nationalityFilter = document.getElementById('nationalityFilter');
        const sortFilter = document.getElementById('sortFilter');
        const authorsGrid = document.getElementById('authorsGrid');
        const resultsCount = document.getElementById('resultsCount');
        const emptyState = document.getElementById('emptyState');
        const paginationWrapper = document.getElementById('paginationWrapper');
        const baseUrl = "{{ route('authors.index') }}";
        const authorShowUrl = "{{ url('/author') }}";
        const storageUrl = "{{ asset('storage') }}";

        // Live search on typing
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(liveFilter, 400);
        });

        function liveFilter() {
            const q = searchInput.value.trim();
            const nationality = nationalityFilter.value;
            const sort = sortFilter.value;

            const params = new URLSearchParams();
            if (q) params.set('q', q);
            if (nationality) params.set('nationality', nationality);
            if (sort && sort !== 'name') params.set('sort', sort);

            fetch(baseUrl + '?' + params.toString(), {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                renderAuthors(data.authors);
                resultsCount.textContent = data.total + ' مؤلف';

                // Hide pagination during live search
                paginationWrapper.style.display = 'none';

                // Show/hide empty state
                if (data.authors.length === 0) {
                    emptyState.style.display = '';
                    authorsGrid.style.display = 'none';
                } else {
                    emptyState.style.display = 'none';
                    authorsGrid.style.display = '';
                }
            })
            .catch(err => console.error('Search error:', err));
        }

        function renderAuthors(authors) {
            let html = '';
            const isAuth = document.querySelector('meta[name="csrf-token"]') !== null;
            authors.forEach(author => {
                const firstLetter = author.name ? author.name.charAt(0) : '?';
                const avatarHtml = author.profile_image
                    ? `<img src="${storageUrl}/${author.profile_image}" alt="${author.name}">`
                    : `<div class="author-card-placeholder"><span>${firstLetter}</span></div>`;

                const nationalityHtml = author.nationality
                    ? `<span class="author-card-nationality"><i class="fas fa-globe-africa"></i> ${author.nationality}</span>`
                    : '';

                const booksCount = author.primary_books_count || 0;

                const followBtnHtml = isAuth
                    ? `<button class="follow-btn-card" onclick="toggleFollow('author', ${author.id}, this)" title="متابعة">
                           <i class="fas fa-plus"></i>
                       </button>`
                    : '';

                html += `
                    <div class="author-card">
                        <a href="${authorShowUrl}/${author.id}" class="author-card-avatar">${avatarHtml}</a>
                        <div class="author-card-info">
                            <a href="${authorShowUrl}/${author.id}" class="author-card-name-link">
                                <h3 class="author-card-name">${author.name}</h3>
                            </a>
                            ${nationalityHtml}
                            <span class="author-card-books">
                                <i class="fas fa-book"></i> ${booksCount} كتاب
                            </span>
                        </div>
                        ${followBtnHtml}
                    </div>
                `;
            });
            authorsGrid.innerHTML = html;
        }

        function toggleFollow(type, id, btn) {
            var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            btn.disabled = true;
            fetch('/follow/' + type + '/' + id, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                btn.disabled = false;
                if (data.redirect) { window.location.href = data.redirect; return; }
                if (data.success) {
                    if (data.following) {
                        btn.classList.add('following');
                        btn.querySelector('i').className = 'fas fa-check';
                        btn.title = 'إلغاء المتابعة';
                    } else {
                        btn.classList.remove('following');
                        btn.querySelector('i').className = 'fas fa-plus';
                        btn.title = 'متابعة';
                    }
                }
            })
            .catch(function() { btn.disabled = false; });
        }
    </script>
</body>
</html>
