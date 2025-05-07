<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $category->name }} - عصير الكتب</title>
    <!-- Stylesheets -->
    <link rel="stylesheet" href="{{ asset('css/headerstyle.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    <link rel="stylesheet" href="{{ asset('css/by-category.css') }}">
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
</head>
<body>
    @include('header')
    
    <!-- Hero Banner for Category -->
    <div class="category-hero">
        <div class="container">
            <div class="hero-content">
                @if(isset($category) && $category)
                <h1 class="hero-title">{{ $category->name }}</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="{{ route('index.page') }}"><i class="fas fa-home home-icon"></i> الرئيسية</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="{{ route('categories.index') }}">الأقسام</a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $category->name }}</li>
                    </ol>
                </nav>
                @endif
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
                        <h5 class="mb-0"><i class="fas fa-book-open me-2"></i>الأقسام الفرعية</h5>
                    </div>
                    <div class="sidebar-card-body">
                        @if(isset($category) && $category && count($category->children) > 0)
                            <ul class="category-list">
                                @foreach ($category->children as $child)
                                    <li>
                                        <a href="{{ route('by-category', ['category' => $child->id]) }}" class="category-item">
                                            <i class="bi bi-caret-left-fill"></i>
                                            <span>{{ $child->name }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-muted text-center py-3">لا توجد أقسام فرعية</p>
                        @endif
                    </div>
                </div>
                
                <!-- Filter Card -->
                <div class="sidebar-card">
                    <div class="sidebar-card-header">
                        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>تصفية النتائج</h5>
                    </div>
                    <div class="sidebar-card-body">
                        <form action="" method="GET">
                            <!-- Publishers Filter -->
                            <div class="filter-section">
                                <h6 class="filter-title">دار النشر</h6>
                                <div class="custom-checkbox">
                                    <input class="custom-checkbox-input" type="checkbox" name="publishers[]" value="1" id="publisher1">
                                    <label class="custom-checkbox-label" for="publisher1">دار الساقي</label>
                                </div>
                                <div class="custom-checkbox">
                                    <input class="custom-checkbox-input" type="checkbox" name="publishers[]" value="2" id="publisher2">
                                    <label class="custom-checkbox-label" for="publisher2">المركز الثقافي العربي</label>
                                </div>
                                <div class="custom-checkbox">
                                    <input class="custom-checkbox-input" type="checkbox" name="publishers[]" value="3" id="publisher3">
                                    <label class="custom-checkbox-label" for="publisher3">دار النهضة العربية</label>
                                </div>
                            </div>
                            
                            <!-- Language Filter -->
                            <div class="filter-section">
                                <h6 class="filter-title">اللغة</h6>
                                <select class="form-select custom-select" name="language">
                                    <option value="">جميع اللغات</option>
                                    <option value="العربية">العربية</option>
                                    <option value="الإنجليزية">الإنجليزية</option>
                                    <option value="الفرنسية">الفرنسية</option>
                                </select>
                            </div>
                            
                            <!-- Price Range Filter -->
                            <div class="filter-section">
                                <h6 class="filter-title">نطاق السعر</h6>
                                <div class="price-range">
                                    <div class="range-slider" id="price-slider"></div>
                                    <div class="range-inputs mt-3">
                                        <div class="input-group">
                                            <span class="input-group-text">من</span>
                                            <input type="number" class="form-control" id="price-min" name="price_min" placeholder="0">
                                            <span class="input-group-text">ر.س</span>
                                        </div>
                                        <div class="input-group mt-2">
                                            <span class="input-group-text">إلى</span>
                                            <input type="number" class="form-control" id="price-max" name="price_max" placeholder="1000">
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
                @if(isset($category) && $category)
                <!-- Sorting and View Options -->
                <div class="content-header">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="results-count">
                            <p>عرض <span class="fw-bold">{{ $books->count() }}</span> من <span class="fw-bold">{{ $books->total() }}</span> كتاب</p>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="view-options me-3">
                                <button class="btn btn-view active" data-view="grid"><i class="fas fa-th"></i></button>
                                <button class="btn btn-view" data-view="list"><i class="fas fa-list"></i></button>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-sort dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-sort me-1"></i> ترتيب حسب
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="sortDropdown">
                                    <li><a class="dropdown-item" href="?sort=newest"><i class="fas fa-calendar-alt me-2"></i>الأحدث</a></li>
                                    <li><a class="dropdown-item" href="?sort=price_asc"><i class="fas fa-sort-amount-down-alt me-2"></i>السعر: من الأقل للأعلى</a></li>
                                    <li><a class="dropdown-item" href="?sort=price_desc"><i class="fas fa-sort-amount-down me-2"></i>السعر: من الأعلى للأقل</a></li>
                                    <li><a class="dropdown-item" href="?sort=title"><i class="fas fa-sort-alpha-down me-2"></i>العنوان: أ-ي</a></li>
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
                    <div class="books-container grid-view">
                        @foreach ($books as $book)
                        
                            <div class="book-item">
                                <div class="book-card">
                                    <div class="card-badges">
                                        @if($book->is_new ?? false)
                                            <span class="badge bg-success">جديد</span>
                                        @endif
                                        @if($book->discount ?? 0 > 0)
                                            <span class="badge bg-danger">خصم {{ $book->discount }}%</span>
                                        @endif
                                    </div>
                                    <div class="quick-actions">
                                        <button class="action-btn" title="إضافة للمفضلة"><i class="far fa-heart"></i></button>
                                        <button class="action-btn" title="إضافة للسلة"><i class="fas fa-shopping-cart"></i></button>
                                    </div>
                                    <a href="{{ route('moredetail.page', ['id' => $book->id]) }}" class="book-image-wrapper">
                                        <img src="{{ asset($book->image ?? 'images/book-placeholder.png') }}" class="book-image" alt="{{ $book->title }}">
                                    </a>
                                    <div class="book-details">
                                        <h5 class="book-title">
                                            <a href="{{ route('moredetail.page', ['id' => $book->id]) }}">{{ $book->title }}</a>
                                        </h5>
                                        <p class="book-author">
                                            <i class="fas fa-user-edit"></i> {{ $book->author }}
                                        </p>
                                        @if(isset($book->Publishing_House) && $book->Publishing_House)
                                        <p class="book-publisher">
                                            <i class="fas fa-building"></i> {{ $book->Publishing_House }}
                                        </p>
                                        @endif
                                        <div class="book-price-block">
                                            <p class="book-price">{{ $book->price }} <span class="currency">ر.س</span></p>
                                            @if($book->original_price ?? 0 > $book->price)
                                                <p class="original-price">{{ $book->original_price }} <span class="currency">ر.س</span></p>
                                            @endif
                                        </div>
                                        <div class="book-actions">
                                            
                                            <a href="{{ route('moredetail.page', ['id' => $book->id]) }}" class="view-btn">عرض التفاصيل</a>
                                            <div class="text-center">
                                            
                                        </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    <div class="pagination-container">
                        {{ $books->links() }}
                    </div>
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
    </div>
   
    @include('footer')
    
    <!-- Scripts -->
    
    <script src="{{ asset('js/header.js') }}"></script>
    <script src="{{ asset('js/scripts.js') }}"></script>
    <script src="{{ asset('js/by-category.js') }}"></script>
</body>
</html>