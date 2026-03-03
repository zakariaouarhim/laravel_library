<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $category->name }} - مكتبة الفقراء</title>
    @include('partials.meta-tags', [
        'metaTitle' => $category->name . ' - مكتبة الفقراء',
        'metaDescription' => 'تصفح كتب قسم ' . $category->name . ' المتوفرة في مكتبة الفقراء.',
    ])
    <!-- Stylesheets -->
    <link rel="stylesheet" href="{{ asset('css/headerstyle.css') }}">
    <link rel="stylesheet" href="{{ asset('css/by-category.css') }}">
    <link rel="stylesheet" href="{{ asset('css/listview.css') }}">
    <link rel="stylesheet" href="{{ asset('css/book-card.css') }}">
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
    
    @php
        $crumbs = [
            ['label' => 'الرئيسية', 'url' => route('index.page')],
            ['label' => 'الأقسام',  'url' => route('categories.index')],
        ];
        if (isset($category) && $category) {
            if ($category->parent) {
                $crumbs[] = ['label' => $category->parent->name, 'url' => route('by-category', ['category' => $category->parent->id])];
            }
            $crumbs[] = ['label' => $category->name];
        }
    @endphp
    @include('partials.page-hero', [
        'title'       => isset($category) ? $category->name : 'الأقسام',
        'icon'        => isset($category) ? ($category->categorie_icon ?? 'fas fa-tag') : 'fas fa-tag',
        'breadcrumbs' => $crumbs,
    ])
   
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
                        {{-- Logic to determine which list to show --}}
                        @php
                            $displayCategories = collect();

                            if (isset($category) && $category) {
                                if ($category->children->count() > 0) {
                                    // Case 1: Use is on a Parent category -> Show Children
                                    $displayCategories = $category->children;
                                } elseif ($category->parent) {
                                    // Case 2: User is on a Child category -> Show Siblings (Parent's children)
                                    $displayCategories = $category->parent->children;
                                }
                            }
                        @endphp

                        @if($displayCategories->count() > 0)
                            <ul class="category-list">
                                @foreach ($displayCategories as $item)
                                    {{-- Add 'active' class logic if you want to highlight the current child --}}
                                    <li class="{{ (isset($category) && $category->id == $item->id) ? 'active-category' : '' }}">
                                        <a href="{{ route('by-category', ['category' => $item->id]) }}" class="category-item">
                                            
                                            {{-- Change icon if this is the currently selected category --}}
                                            @if(isset($category) && $category->id == $item->id)
                                                <i class="bi bi-check-circle-fill text-primary"></i>
                                            @else
                                                <i class="bi bi-caret-left-fill"></i>
                                            @endif

                                            <span class="{{ (isset($category) && $category->id == $item->id) ? 'fw-bold text-primary' : '' }}">
                                                {{ $item->name }}
                                            </span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-muted text-center py-3">لا توجد أقسام فرعية</p>
                        @endif
                    </div>
                </div>
                
                @include('partials.book-filters', [
                    'filterAction' => route('by-category', ['category' => $category->id]),
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
                    <nav>
                        {{ $books->links('pagination::bootstrap-4') }}
                    </nav>
                    @endif
                
                @endif
                @else
                <div class="empty-state">
                    <div class="empty-state-icon"><i class="fas fa-exclamation-circle"></i></div>
                    <h3>القسم غير موجود</h3>
                    <p>لم يتم العثور على الفئة المطلوبة.</p>
                    <a href="{{ route('categories') }}" class="btn btn-primary">تصفح جميع الفئات</a>
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
   
    @include('footer')
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap @5.3.1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/header.js') }}"></script>
    <script src="{{ asset('js/scripts.js') }}"></script>
    <script src="{{ asset('js/by-category.js') }}"></script>
    <script src="{{ asset('js/card.js') }}"></script>
</body>
</html>