<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إكسسوارات القراءة - مكتبة الفقراء</title>
    @include('partials.meta-tags', [
        'metaTitle' => 'إكسسوارات القراءة - مكتبة الفقراء',
        'metaDescription' => 'تسوق إكسسوارات القراءة من مكتبة الفقراء. فواصل كتب، حوامل، أضواء قراءة والمزيد.',
    ])
    <!-- Stylesheets -->
    <link rel="stylesheet" href="{{ asset('css/headerstyle.css') }}">
    <link rel="stylesheet" href="{{ asset('css/by-category.css') }}">
    <link rel="stylesheet" href="{{ asset('css/listview.css') }}">
    <link rel="stylesheet" href="{{ asset('css/book-card.css') }}">
    <link rel="stylesheet" href="{{ asset('css/accessories.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">

    <!-- Favicon -->
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.rtl.min.css">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts - Tajawal -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    @include('header')

    <!-- Hero Banner -->
    <div class="category-hero accessories-hero">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title"><i class="fas fa-bookmark me-2"></i>إكسسوارات القراءة</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="{{ route('index.page') }}"><i class="fas fa-home home-icon"></i> الرئيسية</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">إكسسوارات القراءة</li>
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
                @if($categories->count() > 0)
                <div class="sidebar-card mb-4">
                    <div class="sidebar-card-header">
                        <h5 class="mb-0"><i class="fas fa-tags me-2"></i>الأصناف</h5>
                    </div>
                    <div class="sidebar-card-body">
                        <ul class="category-list">
                            <li>
                                <a href="{{ route('accessories.index') }}" class="category-item {{ !request('category') ? 'fw-bold text-primary' : '' }}">
                                    @if(!request('category'))
                                        <i class="bi bi-check-circle-fill text-primary"></i>
                                    @else
                                        <i class="bi bi-caret-left-fill"></i>
                                    @endif
                                    <span>جميع الإكسسوارات</span>
                                </a>
                            </li>
                            @foreach ($categories as $cat)
                                <li>
                                    <a href="{{ route('accessories.index', ['category' => $cat->id]) }}" class="category-item">
                                        @if(request('category') == $cat->id)
                                            <i class="bi bi-check-circle-fill text-primary"></i>
                                        @else
                                            <i class="bi bi-caret-left-fill"></i>
                                        @endif
                                        <span class="{{ request('category') == $cat->id ? 'fw-bold text-primary' : '' }}">
                                            {{ $cat->name }}
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                @endif

                <!-- Price Filter Card -->
                <div class="sidebar-card">
                    <div class="sidebar-card-header">
                        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>تصفية النتائج</h5>
                    </div>
                    <div class="sidebar-card-body">
                        <form action="{{ route('accessories.index') }}" method="GET">
                            @if(request('category'))
                                <input type="hidden" name="category" value="{{ request('category') }}">
                            @endif

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
                <!-- Sorting and View Options -->
                <div class="content-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="results-count">
                            <p>عرض <span class="fw-bold">{{ $accessories->count() }}</span> من <span class="fw-bold">{{ $accessories->total() }}</span> منتج</p>
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
                                    <li><a class="dropdown-item {{ request('sort') == 'newest' || !request('sort') ? 'active' : '' }}"
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
                                     <i class="fas fa-sort-alpha-down me-2"></i>الاسم: أ-ي
                                 </a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                @if ($accessories->isEmpty())
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="fas fa-box-open"></i></div>
                    <h3>لا توجد إكسسوارات متاحة</h3>
                    <p>لم نتمكن من العثور على إكسسوارات حالياً</p>
                    <a href="{{ route('index.page') }}" class="btn btn-primary">تصفح الكتب</a>
                </div>
                @else
                @php
                    $viewMode = request('view', 'grid');
                @endphp
                <div class="books-container {{ $viewMode === 'list' ? 'list-view' : 'grid-view' }}">
                    @foreach ($accessories as $book)
                        @if($viewMode === 'list')
                            @include('partials.book-card-list', ['book' => $book])
                        @else
                            @include('partials.book-card-grid', ['book' => $book])
                        @endif
                    @endforeach
                </div>

                <!-- Pagination -->
                @if($accessories instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <nav>
                    {{ $accessories->links('pagination::bootstrap-4') }}
                </nav>
                @endif
                @endif
            </div>
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
    </div>

    @include('footer')

    <!-- Scripts -->
    
    <script src="{{ asset('js/header.js') }}"></script>
    <script src="{{ asset('js/scripts.js') }}"></script>
    <script src="{{ asset('js/card.js') }}"></script>
</body>
</html>
