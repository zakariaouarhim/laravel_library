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
                        
                    </ol>
                </nav>
                
               
                
            </div>
        </div>
    </div>

<div class="container py-4">
    <div class="row">
        <!-- Loop through categories -->
        @foreach ($categorie as $category)
             <div class="col-md-6 col-lg-4 mb-3">
    <div class="category-card d-flex justify-content-between align-items-center p-3 border rounded">
        <!-- Category Name and Count -->
        <a href="{{ route('by-category', ['category' => $category->id]) }}" class="text-decoration-none text-dark flex-grow-1">
            <div>
                <h3 class="mb-0">{{ $category->name }}</h3>
                <span class="count text-muted">({{ $category->children()->count() }})</span>
            </div>
        </a>

        <!-- Toggle Button for Child Categories -->
        @if ($category->children->isNotEmpty())
            <span class="plus-icon" onclick="toggleChildCategories(event, {{ $category->id }})">+</span>
        @endif
    </div>

                <!-- Child Categories Container -->
                <div id="child-categories-{{ $category->id }}" class="child-categories mt-2" style="display: none;">
                    @foreach ($category->children as $child)
                        <div class="child-category">
                            <a href="{{ route('by-category', ['category' => $child->id]) }}" class="text-decoration-none text-dark">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-arrow-right"></i>
                                    <span>{{ $child->name }}</span>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
    @include('footer')

    <!--  JS  -->
    
    <script src="{{ asset('js/header.js') }}"></script>
    <script src="{{ asset('js/category.js') }}"></script>
     
</body>
</html>