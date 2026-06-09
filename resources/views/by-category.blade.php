@extends('layouts.public')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/by-category.css') }}">
    <link rel="stylesheet" href="{{ asset('css/listview.css') }}">
    <link rel="stylesheet" href="{{ asset('css/book-card.css') }}">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
@endpush

@push('head')
    @isset($schemas)
        <x-seo.json-ld :schema="$schemas['collection']" />
        <x-seo.json-ld :schema="$schemas['breadcrumbs']" />
    @endisset
@endpush

@section('content')
    @php
        $crumbs = [
            ['label' => 'الرئيسية', 'url' => route('index.page')],
            ['label' => 'الأقسام',  'url' => route('categories.index')],
        ];
        if (isset($category) && $category) {
            if ($category->parent) {
                $crumbs[] = ['label' => $category->parent->name, 'url' => route('by-category', $category->parent)];
            }
            $crumbs[] = ['label' => $category->name];
        }
    @endphp
    @include('partials.page-hero', [
        'title'       => isset($category) ? $category->name : 'الأقسام',
        'icon'        => isset($category) ? ($category->categorie_icon ?? 'fas fa-tag') : 'fas fa-tag',
        'breadcrumbs' => $crumbs,
    ])

    @if(isset($category) && !empty($category->editorial_content))
        <section class="category-editorial bg-light border-bottom py-4">
            <div class="container">
                <div class="category-editorial-body">
                    {!! $category->editorial_content_html !!}
                </div>
            </div>
        </section>
    @endif

    <div class="container py-5">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3 mb-4">
                <!-- Categories Card -->
                <div class="sidebar-card mb-4">
                   <div class="sidebar-card-header">
                        <h5 class="mb-0"><i class="fas fa-book-open me-2"></i>الأقسام الفرعية</h5>
                    </div>
                    <div class="sidebar-card-body">
                        @php
                            $displayCategories = $displayCategories ?? collect();

                            // Ensure the currently active category is always visible (even if not in top 10).
                            $activeIndex = isset($category) ? $displayCategories->search(fn($c) => $c->id === $category->id) : false;
                            $forceShowActive = $activeIndex !== false && $activeIndex >= 10;

                            $sidebarLimit = 10;
                            $hasMore = $displayCategories->count() > $sidebarLimit;
                        @endphp

                        @if($displayCategories->count() > 0)
                            <ul class="category-list" id="sidebarCategoryList">
                                @foreach ($displayCategories as $index => $item)
                                    @php
                                        $isActive = isset($category) && $category->id == $item->id;
                                        $isHidden = $index >= $sidebarLimit && !($forceShowActive && $isActive);
                                    @endphp
                                    <li class="{{ $isActive ? 'active-category' : '' }} {{ $isHidden ? 'category-extra d-none' : '' }}">
                                        <a href="{{ route('by-category', $item) }}" class="category-item">
                                            @if($isActive)
                                                <i class="bi bi-check-circle-fill text-primary"></i>
                                            @else
                                                <i class="bi bi-caret-left-fill"></i>
                                            @endif

                                            <span class="{{ $isActive ? 'fw-bold text-primary' : '' }}">
                                                {{ $item->name }}
                                            </span>
                                            @if(isset($item->books_count))
                                                <span class="badge">{{ $item->books_count }}</span>
                                            @endif
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                            @if($hasMore)
                                <button type="button" class="toggle-sidebar-categories"
                                    data-show-html='<i class="fas fa-chevron-down"></i><span>عرض الكل ({{ $displayCategories->count() }})</span>'
                                    data-hide-html='<i class="fas fa-chevron-up"></i><span>عرض أقل</span>'>
                                    <i class="fas fa-chevron-down"></i>
                                    <span>عرض الكل ({{ $displayCategories->count() }})</span>
                                </button>
                            @endif
                        @else
                            <p class="text-muted text-center py-3">لا توجد أقسام فرعية</p>
                        @endif
                    </div>
                </div>

                @include('partials.book-filters', [
                    'filterAction' => route('by-category', $category),
                    'publishingHouses' => $publishingHouses,
                ])
            </div>

            <!-- Main Content -->
            <div class="col-lg-9">
                @if(isset($category) && $category)
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


                @if ($books->isEmpty())
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="fas fa-book-open"></i></div>
                    <h3>لا توجد كتب متاحة</h3>
                    <p>لم نتمكن من العثور على كتب في قسم "{{ $category->name }}"</p>
                    <a href="{{ route('index.page') }}" class="btn btn-primary">تصفح الكتب</a>
                </div>
                @else
                @php
                    $viewMode = request('view', 'grid'); // Default is grid
                @endphp
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
                    @if($books instanceof \Illuminate\Pagination\Paginator || $books instanceof \Illuminate\Pagination\LengthAwarePaginator)
                    {{-- Desktop: full numbered pagination --}}
                    <nav class="d-none d-lg-block">
                        {{ $books->links('pagination::bootstrap-4') }}
                    </nav>
                    {{-- Mobile: prev / next only --}}
                    <nav class="d-lg-none">
                        <ul class="pagination pagination-mobile">
                            <li class="page-item {{ $books->onFirstPage() ? 'disabled' : '' }}">
                                <a class="page-link" href="{{ $books->previousPageUrl() ?? '#' }}" aria-label="Previous">
                                   &laquo; السابق
                                </a>
                            </li>
                            <li class="page-item {{ !$books->hasMorePages() ? 'disabled' : '' }}">
                                <a class="page-link" href="{{ $books->nextPageUrl() ?? '#' }}" aria-label="Next">
                                     التالي&raquo;
                                </a>
                            </li>
                        </ul>
                    </nav>
                    @endif

                @endif
                @else
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="fas fa-exclamation-circle"></i></div>
                    <h3>القسم غير موجود</h3>
                    <p>لم يتم العثور على الفئة المطلوبة.</p>
                    <a href="{{ route('categories.index') }}" class="btn btn-primary">تصفح جميع الفئات</a>
                </div>
                @endif
            </div>
        </div>
        <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1050;">
        <!-- Success toast -->
        <div id="cartToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <strong class="me-auto">السلة</strong>
                <small class="text-muted toast-time">الآن</small>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        <div class="toast-body" id="successToastMessage"></div>
    </div>

        <!-- Notification toast -->
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
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="{{ asset('js/header.js') }}" defer></script>
    <script src="{{ asset('js/scripts.js') }}" defer></script>
    <script src="{{ asset('js/by-category.js') }}" defer></script>
    <script src="{{ asset('js/card.js') }}" defer></script>
@endpush
