<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نتائج البحث - مكتبة الفقراء</title>
    @include('partials.meta-tags', [
        'metaTitle' => 'نتائج البحث - مكتبة الفقراء',
        'metaDescription' => 'ابحث عن كتابك المفضل في مكتبة الفقراء. تصفح وفلتر النتائج حسب التصنيف والسعر واللغة.',
    ])

    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.rtl.min.css">
    <!-- Stylesheets -->
    <link rel="stylesheet" href="{{ asset('css/headerstyle.css') }}">
    <link rel="stylesheet" href="{{ asset('css/by-category.css') }}">
    <link rel="stylesheet" href="{{ asset('css/listview.css') }}">
    <link rel="stylesheet" href="{{ asset('css/searchresult.css') }}">
    <link rel="stylesheet" href="{{ asset('css/book-card.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Amiri&family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Favicon -->
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">

    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    @include('header')

    <!-- Hero Banner with Search Box -->
    <div class="search-hero">
        <div class="container">
            <div class="hero-content text-center">
                <h1 class="hero-title">ابحث عن كتابك المفضل</h1>
                <form action="{{ route('search.results') }}" method="GET" class="search-box">
                    <input type="hidden" name="category" value="{{ request('category') }}">
                    <input type="hidden" name="sort" value="{{ request('sort') }}">
                    <input
                        type="text"
                        name="query"
                        placeholder="ابحث عن كتاب بالعنوان، المؤلف، أو النوع..."
                        oninput="searchBooksAutocomplete(this.value)"
                        value="{{ $query }}">
                    <button type="submit">
                        <i class="fas fa-search"></i> بحث
                    </button>
                    <div id="searchResults" class="search-results" style="z-index: 9999 !important;">
                    </div>
                </form>
                <nav aria-label="breadcrumb" class="mt-3">
                    <ol class="breadcrumb justify-content-center">
                        <li class="breadcrumb-item"><a href="{{ route('index.page') }}"><i class="fas fa-home home-icon"></i> الرئيسية</a></li>
                        <li class="breadcrumb-item active" aria-current="page">نتائج البحث: "{{ $query }}"</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="container py-5">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3 mb-4">
                <!-- Categories Card -->
                <div class="sidebar-card mb-4">
                    <div class="sidebar-card-header">
                        <h5 class="mb-0"><i class="fas fa-book-open me-2"></i>التصنيفات</h5>
                    </div>
                    <div class="sidebar-card-body">
                        <div class="category-list">
                            @foreach ($categories as $index => $category)
                                @php
                                    $hasChildren = $category->children->count() > 0;
                                    $isParentActive = request('category') == $category->id;
                                    $isChildActive = $hasChildren && $category->children->pluck('id')->contains((int) request('category'));
                                    $isExpanded = $isParentActive || $isChildActive;
                                @endphp
                                <div class="category-parent {{ $index >= 7 ? 'd-none extra-category' : '' }} {{ $isParentActive ? 'active-category' : '' }}">
                                    <div class="d-flex align-items-center category-item-row">
                                        <a href="{{ route('search.results', array_merge(request()->query(), ['category' => $category->id, 'page' => 1])) }}" class="category-item flex-grow-1">
                                            @if($isParentActive)
                                                <i class="bi bi-check-circle-fill text-primary"></i>
                                            @else
                                                <i class="bi bi-caret-left-fill"></i>
                                            @endif
                                            <span class="{{ $isParentActive ? 'fw-bold text-primary' : '' }}">
                                                {{ $category->name }}
                                            </span>
                                        </a>
                                        @if($hasChildren)
                                            <button type="button" class="category-toggle {{ $isExpanded ? '' : 'collapsed' }}"
                                                data-bs-toggle="collapse"
                                                data-bs-target="#catChildren{{ $category->id }}"
                                                aria-expanded="{{ $isExpanded ? 'true' : 'false' }}">
                                                <i class="bi bi-chevron-down"></i>
                                            </button>
                                        @endif
                                    </div>
                                    @if($hasChildren)
                                        <div class="collapse {{ $isExpanded ? 'show' : '' }}" id="catChildren{{ $category->id }}">
                                            @foreach ($category->children as $child)
                                                <div class="category-child {{ request('category') == $child->id ? 'active-category' : '' }}">
                                                    <a href="{{ route('search.results', array_merge(request()->query(), ['category' => $child->id, 'page' => 1])) }}" class="category-item">
                                                        @if(request('category') == $child->id)
                                                            <i class="bi bi-check-circle-fill text-primary"></i>
                                                        @else
                                                            <i class="bi bi-dash"></i>
                                                        @endif
                                                        <span class="{{ request('category') == $child->id ? 'fw-bold text-primary' : '' }}">
                                                            {{ $child->name }}
                                                        </span>
                                                    </a>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        @if($categories->count() > 7)
                            <button type="button" id="showMoreCategoriesBtn" class="btn btn-link mt-2 p-0 w-100 text-center" style="font-size: 0.9rem;">
                                <i class="fas fa-chevron-down me-1"></i> عرض المزيد
                            </button>
                        @endif

                        @if(request('category'))
                            <a href="{{ route('search.results', ['query' => $query]) }}" class="btn btn-sm btn-outline-secondary w-100 mt-2">
                                <i class="fas fa-times me-1"></i>إزالة فلتر التصنيف
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Filter Card -->
                <div class="sidebar-card">
                    <div class="sidebar-card-header">
                        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>تصفية النتائج</h5>
                    </div>
                    <div class="sidebar-card-body">
                        <form method="GET" action="{{ route('search.results') }}">
                            <input type="hidden" name="query" value="{{ $query }}">
                            @if(request('category'))
                                <input type="hidden" name="category" value="{{ request('category') }}">
                            @endif
                            @if(request('sort'))
                                <input type="hidden" name="sort" value="{{ request('sort') }}">
                            @endif

                            <!-- Language Filter -->
                            <div class="filter-section">
                                <h6 class="filter-title">اللغة</h6>
                                <select class="form-select custom-select" name="language">
                                    <option value="">جميع اللغات</option>
                                    @foreach(App\Models\Book::LANGUAGES as $lang)
                                        <option value="{{ $lang }}" {{ request('language') == $lang ? 'selected' : '' }}>
                                            {{ App\Models\Book::LANGUAGE_LABELS[$lang] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Price Range Filter -->
                            <div class="filter-section">
                                <h6 class="filter-title">نطاق السعر</h6>
                                <div class="price-range">
                                    <div class="range-inputs mt-3">
                                        <div class="input-group">
                                            <span class="input-group-text">من</span>
                                            <input type="number" class="form-control" name="price_min"
                                                placeholder="0" value="{{ request('price_min') }}">
                                            <span class="input-group-text">د.م</span>
                                        </div>
                                        <div class="input-group mt-2">
                                            <span class="input-group-text">إلى</span>
                                            <input type="number" class="form-control" name="price_max"
                                                placeholder="1000" value="{{ request('price_max') }}">
                                            <span class="input-group-text">د.م</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-filter w-100">
                                <i class="fas fa-filter me-2"></i>تطبيق الفلتر
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-9">
                <!-- Content Header -->
                <div class="content-header">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="results-count">
                            <p>نتائج البحث عن: <strong>"{{ $query }}"</strong> — عرض <span class="fw-bold">{{ $books->count() }}</span> من <span class="fw-bold">{{ $allBooksCount }}</span> كتاب</p>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="view-options me-3">
                                <a href="{{ request()->fullUrlWithQuery(['view' => 'grid']) }}"
                                    class="btn btn-view {{ request('view', 'grid') == 'grid' ? 'active' : '' }}">
                                    <i class="fas fa-th"></i>
                                </a>
                                <a href="{{ request()->fullUrlWithQuery(['view' => 'list']) }}"
                                    class="btn btn-view {{ request('view') == 'list' ? 'active' : '' }}">
                                    <i class="fas fa-list"></i>
                                </a>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-sort dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-sort me-1"></i> ترتيب حسب
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="sortDropdown">
                                    <li><a class="dropdown-item {{ request('sort') == 'newest' ? 'active' : '' }}"
                                        href="{{ request()->fullUrlWithQuery(['sort' => 'newest']) }}">
                                        <i class="fas fa-calendar-alt me-2"></i>الأحدث
                                    </a></li>
                                    <li><a class="dropdown-item {{ request('sort') == 'price_asc' ? 'active' : '' }}"
                                        href="{{ request()->fullUrlWithQuery(['sort' => 'price_asc']) }}">
                                        <i class="fas fa-sort-amount-down-alt me-2"></i>السعر: من الأقل للأعلى
                                    </a></li>
                                    <li><a class="dropdown-item {{ request('sort') == 'price_desc' ? 'active' : '' }}"
                                        href="{{ request()->fullUrlWithQuery(['sort' => 'price_desc']) }}">
                                        <i class="fas fa-sort-amount-down me-2"></i>السعر: من الأعلى للأقل
                                    </a></li>
                                    <li><a class="dropdown-item {{ request('sort') == 'title' ? 'active' : '' }}"
                                        href="{{ request()->fullUrlWithQuery(['sort' => 'title']) }}">
                                        <i class="fas fa-sort-alpha-down me-2"></i>العنوان: أ-ي
                                    </a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                @if($books->count() > 0)
                    @php $viewMode = request('view', 'grid'); @endphp
                    <div class="books-container {{ $viewMode === 'list' ? 'list-view' : 'grid-view' }}">
                        @foreach ($books as $book)
                            @if($viewMode === 'list')
                                @include('partials.book-card-list', ['book' => $book])
                            @else
                                @include('partials.book-card-grid', ['book' => $book])
                            @endif
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    <nav>
                        {{ $books->links('pagination::bootstrap-4') }}
                    </nav>

                    <!-- Related Books -->
                    @if($relatedBooks && $relatedBooks->count() > 0)
                        <div class="related-section mt-5">
                            <h4 class="mb-3"><i class="fas fa-book me-2"></i>كتب ذات صلة</h4>
                            <div class="books-container grid-view">
                                @foreach ($relatedBooks as $book)
                                    @include('partials.book-card-grid', ['book' => $book])
                                @endforeach
                            </div>
                        </div>
                    @endif
                @else
                    <!-- No Results State -->
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="fas fa-search"></i></div>
                        <h3>لم نعثر على نتائج</h3>
                        <p>
                            عذراً، لم نتمكن من العثور على كتب تطابق بحثك. جرب كلمات مفتاحية أخرى أو
                            <a href="{{ route('index.page') }}">تصفح اقتراحاتنا</a>
                        </p>

                        @if ($relatedCategories->isNotEmpty())
                            <div class="suggestions mt-4">
                                <h4>
                                    {{ request('category') ? 'تصنيفات ذات صلة:' : 'تصنيفات شائعة:' }}
                                </h4>
                                <div class="suggestion-tags d-flex flex-wrap gap-2 justify-content-center mt-3">
                                    @foreach ($relatedCategories as $cat)
                                        <a href="{{ route('by-category', ['category' => $cat->id]) }}" class="btn btn-outline-primary btn-sm rounded-pill">
                                            {{ $cat->name }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <!-- Toast Notifications -->
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
    </div>

    @include('footer')

    <!-- Scripts -->
    <script src="{{ asset('js/header.js') }}"></script>
    <script src="{{ asset('js/Index-searchbar.js') }}"></script>
    <script src="{{ asset('js/scripts.js') }}"></script>
    <script src="{{ asset('js/card.js') }}"></script>
    <script>
        // Show more categories toggle
        const showMoreBtn = document.getElementById('showMoreCategoriesBtn');
        if (showMoreBtn) {
            let expanded = false;
            showMoreBtn.addEventListener('click', function () {
                expanded = !expanded;
                document.querySelectorAll('.extra-category').forEach(el => {
                    el.classList.toggle('d-none', !expanded);
                });
                this.innerHTML = expanded
                    ? '<i class="fas fa-chevron-up me-1"></i> عرض أقل'
                    : '<i class="fas fa-chevron-down me-1"></i> عرض المزيد';
            });
        }
    </script>
</body>
</html>
