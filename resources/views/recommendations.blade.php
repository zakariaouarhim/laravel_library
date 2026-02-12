<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ترشيحات لك - أسير الكتب</title>

    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.rtl.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <link rel="stylesheet" href="{{ asset('css/headerstyle.css') }}">
    <link rel="stylesheet" href="{{ asset('css/recommendations.css') }}"> 
    <link rel="stylesheet" href="{{ asset('css/listview.css') }}">
    <link rel="stylesheet" href="{{ asset('css/book-card.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">

    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    @include('header')

    <!-- Hero Banner -->
    <div class="rec-hero">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title"><i class="fas fa-lightbulb me-2"></i>ترشيحات لك</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="{{ route('index.page') }}"><i class="fas fa-home home-icon"></i> الرئيسية</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="{{ route('account.page') }}">حسابي</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">ترشيحات لك</li>
                    </ol>
                </nav>
                <p class="hero-subtitle">كتب مختارة بناءً على تقييماتك واهتماماتك</p>
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
                        <h5 class="mb-0"><i class="fas fa-bookmark me-2"></i>الأقسام</h5>
                    </div>
                    <div class="sidebar-card-body">
                        <ul class="category-list">
                            <!-- All categories option -->
                            <li class="{{ !request('category') ? 'active-category' : '' }}">
                                <a href="{{ route('recommendations.index', array_merge(request()->except('category', 'page'), [])) }}" class="category-item">
                                    @if(!request('category'))
                                        <i class="bi bi-check-circle-fill text-primary"></i>
                                    @else
                                        <i class="bi bi-caret-left-fill"></i>
                                    @endif
                                    <span class="{{ !request('category') ? 'fw-bold text-primary' : '' }}">
                                        جميع الأقسام المقترحة
                                    </span>
                                </a>
                            </li>

                            @foreach($categories as $parentCat)
                                @php
                                    $isRecommended = $favoriteCategories->contains($parentCat->id);
                                    $hasRecommendedChild = $parentCat->children->pluck('id')->intersect($favoriteCategories)->isNotEmpty();
                                    $isActive = request('category') == $parentCat->id;
                                @endphp
                                <li class="{{ $isActive ? 'active-category' : '' }}">
                                    <a href="{{ route('recommendations.index', array_merge(request()->except('page'), ['category' => $parentCat->id])) }}" class="category-item">
                                        @if($isActive)
                                            <i class="bi bi-check-circle-fill text-primary"></i>
                                        @else
                                            <i class="bi bi-caret-left-fill"></i>
                                        @endif
                                        <span class="{{ $isActive ? 'fw-bold text-primary' : '' }}">
                                            {{ $parentCat->name }}
                                        </span>
                                        @if($isRecommended)
                                            <span class="rec-badge">مقترح لك</span>
                                        @endif
                                    </a>
                                </li>

                                @foreach($parentCat->children as $childCat)
                                    @php
                                        $isChildRecommended = $favoriteCategories->contains($childCat->id);
                                        $isChildActive = request('category') == $childCat->id;
                                    @endphp
                                    <li class="sub-category {{ $isChildActive ? 'active-category' : '' }}">
                                        <a href="{{ route('recommendations.index', array_merge(request()->except('page'), ['category' => $childCat->id])) }}" class="category-item">
                                            @if($isChildActive)
                                                <i class="bi bi-check-circle-fill text-primary"></i>
                                            @else
                                                <i class="bi bi-dash"></i>
                                            @endif
                                            <span class="{{ $isChildActive ? 'fw-bold text-primary' : '' }}">
                                                {{ $childCat->name }}
                                            </span>
                                            @if($isChildRecommended)
                                                <span class="rec-badge">مقترح لك</span>
                                            @endif
                                        </a>
                                    </li>
                                @endforeach
                            @endforeach
                        </ul>
                    </div>
                </div>

                <!-- Filter Card -->
                <div class="sidebar-card">
                    <div class="sidebar-card-header">
                        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>تصفية النتائج</h5>
                    </div>
                    <div class="sidebar-card-body">
                        <form action="{{ route('recommendations.index') }}" method="GET">
                            @if(request('category'))
                                <input type="hidden" name="category" value="{{ request('category') }}">
                            @endif

                            <!-- Hide Reviewed Toggle -->
                            <div class="filter-section">
                                <h6 class="filter-title">الكتب المقروءة</h6>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="hide_reviewed" value="1"
                                        id="hideReviewed" {{ $hideReviewed === '1' ? 'checked' : '' }}>
                                    <label class="form-check-label" for="hideReviewed">إخفاء الكتب التي قيّمتها</label>
                                </div>
                            </div>

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
                                            <span class="input-group-text">ر.س</span>
                                        </div>
                                        <div class="input-group mt-2">
                                            <span class="input-group-text">إلى</span>
                                            <input type="number" class="form-control" name="price_max"
                                                placeholder="1000" value="{{ request('price_max') }}">
                                            <span class="input-group-text">ر.س</span>
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
                <!-- Sorting and View Options -->
                <div class="content-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="results-count">
                            <p>عرض <span class="fw-bold">{{ $books->count() }}</span> من <span class="fw-bold">{{ $books->total() }}</span> كتاب</p>
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
                                    <li><a class="dropdown-item {{ request('sort', 'newest') == 'newest' ? 'active' : '' }}"
                                        href="{{ request()->fullUrlWithQuery(['sort' => 'newest']) }}">
                                        <i class="fas fa-calendar-alt me-2"></i>الأحدث
                                    </a></li>
                                    <li><a class="dropdown-item {{ request('sort') == 'rating' ? 'active' : '' }}"
                                        href="{{ request()->fullUrlWithQuery(['sort' => 'rating']) }}">
                                        <i class="fas fa-star me-2"></i>الأعلى تقييماً
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

                @if($books->isEmpty())
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="fas fa-lightbulb"></i></div>
                    <h3>لا توجد ترشيحات حالياً</h3>
                    <p>جرّب تغيير الفلتر أو اختيار قسم آخر</p>
                    <a href="{{ route('recommendations.index') }}" class="btn btn-primary">عرض جميع الترشيحات</a>
                </div>
                @else
                @php
                    $viewMode = request('view', 'grid');
                @endphp
                <div class="books-container {{ $viewMode === 'list' ? 'list-view' : 'grid-view' }}">
                    @foreach($books as $book)
                        @if($viewMode === 'list')
                            @include('partials.book-card-list', ['book' => $book])
                        @else
                            @include('partials.book-card-grid', ['book' => $book])
                        @endif
                    @endforeach
                </div>

                <!-- Pagination -->
                @if($books instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <nav>
                    {{ $books->links('pagination::bootstrap-4') }}
                </nav>
                @endif
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/header.js') }}"></script>
    <script src="{{ asset('js/scripts.js') }}"></script>
    <script src="{{ asset('js/card.js') }}"></script>
</body>
</html>
