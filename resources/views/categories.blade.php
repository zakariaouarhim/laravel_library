<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الأقسام</title>
    <!-- Stylesheets -->
    <link rel="stylesheet" href="{{ asset('css/headerstyle.css') }}">
    <link rel="stylesheet" href="{{ asset('css/categories.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">

    <!-- Favicon -->
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
    
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.rtl.min.css">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts - Adding Tajawal for better Arabic display -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;700&display=swap" rel="stylesheet">
    
    
</head>
<body>
    @include('header')
    
    <!-- Header with Breadcrumb -->
    <div class="page-header">
        <div class="container">
            <h1 class="hero-title">الأقسام</h1>
            <div class="d-flex justify-content-between align-items-center">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-0">
                        <li class="breadcrumb-item">
                            <a href="{{ route('index.page') }}"><i class="fas fa-home home-icon"></i> الرئيسية</a>
                        </li>
                        <li class="breadcrumb-item active">
                            الأقسام
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
    
    <div class="container py-4">
        <h2 class="section-title mb-4">استكشف الأقسام</h2>
        
        <div class="row">
            @if($categorie->count() > 0)
                @foreach ($categorie as $category)
                    <div class="col-md-6 col-lg-4">
                        <div class="category-card">
                            <!-- Category Name and Count -->
                            <a href="{{ route('by-category', ['category' => $category->id]) }}" class="text-decoration-none text-dark flex-grow-1">
                                <div class="category-content">
                                    <div class="category-icon">
                                        <i class="fas fa-folder"></i>
                                    </div>
                                    <div>
                                        <h3>{{ $category->name }}</h3>
                                        <span class="count mt-1">{{ $category->children()->count() }}</span>
                                    </div>
                                </div>
                            </a>
                            
                            <!-- Toggle Button for Child Categories -->
                            @if ($category->children->isNotEmpty())
                                <div class="plus-icon" onclick="toggleChildCategories(event, {{ $category->id }})">+</div>
                            @endif
                        </div>
                        
                        <!-- Child Categories Container -->
                        <div id="child-categories-{{ $category->id }}" class="child-categories" style="display: none;">
                            @foreach ($category->children as $child)
                                <div class="child-category">
                                    <a href="{{ route('by-category', ['category' => $child->id]) }}">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-angle-left"></i>
                                            <span>{{ $child->name }}</span>
                                        </div>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @else
                <div class="col-12">
                    <div class="empty-categories">
                        <i class="fas fa-folder-open"></i>
                        <h3>لا توجد أقسام</h3>
                        <p>لم يتم إضافة أي أقسام حتى الآن</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
    
    @include('footer')
    
    <!-- JS -->
    <script src="{{ asset('js/header.js') }}"></script>
    <script src="{{ asset('js/category.js') }}"></script>
    
</body>
</html>