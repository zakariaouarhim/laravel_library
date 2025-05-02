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
   
</head>
<body>
    @include('header')
    
    <!-- Header with Breadcrumb -->
    <div class="page-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <a href="{{ route('index.page') }}"><i class="fas fa-home home-icon"></i> الرئيسية</a>
                        </li>
                        <li class="breadcrumb-item">
                            <a href="{{ route('categories.index') }}">الأقسام</a>
                        </li>
                        @if(isset($category) && $category)
                        <li class="breadcrumb-item active" aria-current="page">{{ $category->name }}</li>
                        @endif
                    </ol>
                </nav>
                @if(isset($category) && $category)
                <div class="d-flex align-items-center">
                    <div class="dropdown me-3">
                        <button class="btn btn-outline-primary dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-sort me-1"></i> ترتيب حسب
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="sortDropdown">
                            <li><a class="dropdown-item" href="?sort=newest">الأحدث</a></li>
                            <li><a class="dropdown-item" href="?sort=price_asc">السعر: من الأقل للأعلى</a></li>
                            <li><a class="dropdown-item" href="?sort=price_desc">السعر: من الأعلى للأقل</a></li>
                            <li><a class="dropdown-item" href="?sort=title">العنوان: أ-ي</a></li>
                        </ul>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
   
    <div class="container py-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3 mb-4">
                <!-- Categories Card -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">الأقسام</h5>
                         
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                           
                        </ul>
                    </div>
                </div>
                
                <!-- Filter Card -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">تصفية النتائج</h5>
                    </div>
                    <div class="card-body">
                        <form action="" method="GET">
                            <!-- Publishers Filter -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">دار النشر</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="publishers[]" value="1" id="publisher1">
                                    <label class="form-check-label" for="publisher1">دار الساقي</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="publishers[]" value="2" id="publisher2">
                                    <label class="form-check-label" for="publisher2">المركز الثقافي العربي</label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="publishers[]" value="3" id="publisher3">
                                    <label class="form-check-label" for="publisher3">دار النهضة العربية</label>
                                </div>
                            </div>
                            
                            <!-- Language Filter -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">اللغة</label>
                                <select class="form-select" name="language">
                                    <option value="">جميع اللغات</option>
                                    <option value="العربية">العربية</option>
                                    <option value="الإنجليزية">الإنجليزية</option>
                                    <option value="الفرنسية">الفرنسية</option>
                                </select>
                            </div>
                            
                            <!-- Price Range Filter -->
                            <div class="mb-3">
                                <label class="form-label fw-bold">نطاق السعر</label>
                                <div class="row g-2">
                                    <div class="col">
                                        <input type="number" class="form-control" placeholder="من" name="price_min">
                                    </div>
                                    <div class="col">
                                        <input type="number" class="form-control" placeholder="إلى" name="price_max">
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">تطبيق الفلتر</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-lg-9">
                @if(isset($category) && $category)
                <h2 class="category-title">{{ $category->name }}</h2>
                
                @if ($books->isEmpty())
                    <div class="empty-state">
                        <p>لا توجد كتب متاحة في هذه الفئة.</p>
                        <a href="{{ route('index.page') }}" class="btn btn-primary">تصفح الكتب</a>
                    </div>
                @else
                    <div class="row">
                        @foreach ($books as $book)
                            <div class="col-md-4 col-sm-6 mb-4">
                                <div class="book-card">
                                    <a href="{{ route('moredetail.page', ['id' => $book->id]) }}">
                                        <img src="{{ asset($book->image ?? 'images/book-placeholder.png') }}" class="book-image img-fluid" alt="{{ $book->title }}">
                                    </a>
                                    <div class="book-details">
                                        <h5 class="book-title">{{ $book->title }}</h5>
                                        <p class="book-author">
                                            <i class="fas fa-user-edit me-1"></i> {{ $book->author }}
                                        </p>
                                        @if(isset($book->Publishing_House) && $book->Publishing_House)
                                        <p class="book-publisher">
                                            <i class="fas fa-building me-1"></i> {{ $book->Publishing_House }}
                                        </p>
                                        @endif
                                        @if(isset($book->ISBN) && $book->ISBN)
                                        <p class="book-isbn">
                                            <i class="fas fa-barcode me-1"></i> {{ $book->ISBN }}
                                        </p>
                                        @endif
                                        <p class="book-price">{{ $book->price }} ر.س</p>
                                        <a href="{{ route('moredetail.page', ['id' => $book->id]) }}" class="view-btn">عرض التفاصيل</a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    <div class="d-flex justify-content-center mt-4">
                        {{ $books->links() }}
                    </div>
                @endif
                @else
                <div class="empty-state">
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
</body>
</html>