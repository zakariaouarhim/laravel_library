<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مكتبة بيع الكتب</title>
    <!-- Correct CSS linking -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/carouselstyle.css') }}">
    <link rel="stylesheet" href="{{ asset('css/headerstyle.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">

    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.rtl.min.css" integrity="sha384-gXt9imSW0VcJVHezoNQsP+TNrjYXoGcrqBZJpry9zJt8PCQjobwmhMGaDHTASo9N" crossorigin="anonymous">

    <!-- Font Awesome -->
    <link href="https://fonts.googleapis.com/css2?family=Amiri&family=Scheherazade+New&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Favicon -->
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">

    <meta name="csrf-token" content="{{ csrf_token() }}">

</head>

<body>
    @include('header')

    <!-- Header with Animated Arabic Letters Background -->
    <header class="header-section text-white text-center py-5">
        <!-- Background with falling Arabic letters -->
        <div class="letters-background" id="letters-container"></div>
        
        <!-- Content container with search -->
        <div class="container search-container">
            <h1 class="display-4 fw-bold">ابحث عن كتابك المفضل</h1>
            <p class="lead">ابحث في مجموعتنا الكبيرة من الكتب عبر الأنواع والتصنيفات.</p>
            <form class="d-flex justify-content-center mt-4">
                <input 
                type="text"
                id="searchInput"
                class="form-control w-50 me-2" 
                placeholder="ابحث عن كتاب بالعنوان، المؤلف، أو النوع"
                oninput="searchBooks(this.value)">
                
                <button type="submit" class="btn btn-dark">بحث</button>
                <!-- Search Results Container -->
                <div id="searchResults" class="search-results" style="display: none; position: absolute; top: 100%; left: 25%; width: 50%; z-index: 1000; background-color: white; color: black; border-radius: 0 0 4px 4px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); text-align: right;">
                    <!-- Search results will be inserted here dynamically -->
                </div>
            </form>
        </div>
        <BR></BR>
        <br>
        <br>
        <br>
        <!-- Category buttons below the search bar -->
        
    <div class="categories-wrapper">
        <div class="category-rows">
            
            
            
            <div class="category-row">
                 @foreach ($categorie as $category)
                <button class="category-btn small" onclick="window.location.href='{{ route('by-category', ['category' => $category->id]) }}'" >{{ $category->name }}</button>
                 @endforeach
                <button class="category-btn small" onclick="window.location.href='{{ route('categories.index') }}'">المزيد</button>
            </div>
            
            
        </div>
    </div>
    </header>
    
        <!-- First Carousel - Arabic Books -->
<div class="related-books">
    <h3>كتب ذات صلة</h3>
    
    <div class="carousel-container">
        <div class="carousel-wrapper" id="carouselWrapper1">
            <!-- Sample book cards -->
            @foreach ($books as $index => $book)
            <div class="book-card">
                <a href="{{ route('moredetail.page', ['id' => $book->id]) }}">
                    <img src="{{ asset($book->image ?? 'images/books/default-book.png')  }} " class="card-img-top" alt="{{ $book->title }}" loading="lazy">
                </a>
                <h6>{{ $book->title }}</h6>
                <p class="book-author">
                    <i class="fas fa-user-edit me-1"></i>
                    {{ $book->author }}
                </p>
                <div class="price-section">
                    <div class="text-center mb-3">
                        <span class="h6 mb-0 text-gray text-through mr-2" style="text-decoration:line-through">
                            {{ $book->price + 50 }}
                        </span>
                        <span class="h5 mb-0 text-danger">{{ $book->price }} درهم</span>
                    </div>
                    <button class="add-btn" onclick="addToCart({{ $book->id }},'{{ addslashes($book->title) }}', {{ $book->price }}, '{{ addslashes($book->image) }}')">
                        <i class="fas fa-shopping-cart"></i>
                    </button>
                </div>
            </div>
            @endforeach
        </div>
        <button class="carousel-nav prev" id="prevBtn1" onclick="moveCarousel(-1, 'carousel1')">
            <i class="fas fa-chevron-right"></i>
        </button>
        <button class="carousel-nav next" id="nextBtn1" onclick="moveCarousel(1, 'carousel1')">
            <i class="fas fa-chevron-left"></i>
        </button>
        <br>
        <div class="carousel-indicators" id="indicators1"></div>
    </div>
</div>


   
 <!-- Success Modal -->

 <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1050;">
    <div id="cartToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true" >
        <div class="toast-header bg-success text-white">
            <strong class="me-auto" >السلة</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body" id="toastMessage"></div>
    </div>
</div>
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="cartToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-primary text-white">
            <strong class="me-auto">إشعار</strong>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body"></div>
    </div>
</div>
    <!-- first categories -->
    <div class="categories-section text-center">
    <h2 class="section-title">اكتشف حسب الفئة</h2>
    <div class="category-grid d-flex justify-content-center flex-wrap">
        <!-- بطاقة الفئة -->
        <div class="category-card" onclick="window.location.href='{{ route('by-category', ['category' => 1]) }}'">
        <img src="{{ asset('images/novels.svg') }}" alt="روايات" class="category-icon" >
        <h3>روايات</h3>
        <p>اكتشف أروع الروايات من الأدب العربي والعالمي.</p>
        </div>
        <div class="category-card" onclick="window.location.href='{{ route('by-category', ['category' => 2]) }}'">
        <img src="{{ asset('images/religion.svg') }}" alt="كتب دينية" class="category-icon">
        <h3>كتب دينية</h3>
        <p>تعرف على الكتب التي تقربك من الإيمان.</p>
        </div>
        <div class="category-card" onclick="window.location.href='{{ route('by-category', ['category' => 3]) }}'">
        <img src="{{ asset('images/devlopment personell.svg') }}" alt="تنمية ذاتية" class="category-icon">
        <h3>التنمية البشرية وتطوير الذات</h3>
        <p>كتب تحفزك لتحقيق أفضل نسخة من نفسك.</p>
        </div>
        <div class="category-card" onclick="window.location.href='{{ route('by-category', ['category' => 4]) }}'">
        <img src="{{ asset('images/children.svg') }}" alt="قصص الأطفال" class="category-icon">
        <h3>قصص الأطفال</h3>
        <p>قصص ممتعة ومفيدة للصغار.</p>
        </div>
    </div>
    </div>
    <!-- end first categories -->

    <!-- Second Carousel - English Books -->
<div class="related-books">
    <h3>ENGLISH BOOKS</h3>
    
    @if($EnglichBooks && $EnglichBooks->count() > 0)
        <div class="carousel-container">
            <div class="carousel-wrapper" id="carouselWrapper2">
                @foreach($EnglichBooks as $EnglichBook)
                <div class="book-card">
                    <a href="{{ route('moredetail.page', ['id' => $EnglichBook->id]) }}">
                        <img src="{{ asset($EnglichBook->image) }}" class="card-img-top" alt="{{ $EnglichBook->title }}" loading="lazy">
                    </a>
                    <h6>{{ $EnglichBook->title }}</h6>
                    <p class="book-author">
                        <i class="fas fa-user-edit me-1"></i>
                        {{ $EnglichBook->author }}
                    </p>
                    <div class="price-section">
                        <span class="price">{{ $EnglichBook->price }} ر.س</span>
                        <button class="add-btn" 
                        data-book-id="{{ $EnglichBook->id }}"
                        data-book-title="{{ htmlspecialchars($EnglichBook->title, ENT_QUOTES) }}"
                        data-book-price="{{ $EnglichBook->price }}"
                        data-book-image="{{ htmlspecialchars($EnglichBook->image, ENT_QUOTES) }}"
                        onclick="addCarouselBookToCart(this)">
                            <i class="fas fa-shopping-cart"></i>
                        </button>
                    </div>
                </div>
                @endforeach
            </div>
            <button class="carousel-nav prev" id="prevBtn2" onclick="moveCarousel(-1, 'carousel2')">
                <i class="fas fa-chevron-right"></i>
            </button>
            <button class="carousel-nav next" id="nextBtn2" onclick="moveCarousel(1, 'carousel2')">
                <i class="fas fa-chevron-left"></i>
            </button>
            <br>
            <div class="carousel-indicators" id="indicators2"></div>
        </div>
    @else
        <!-- Empty state message -->
        <div class="empty-carousel-message">
            <div class="empty-state-card text-center p-5 border rounded bg-light">
                <div class="empty-state-icon mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 rounded-circle" 
                         style="width: 80px; height: 80px;">
                        <i class="fas fa-books text-primary" style="font-size: 2.5rem;"></i>
                    </div>
                </div>
                <h4 class="text-dark mb-3">لا توجد كتب ذات صلة</h4>
                <p class="text-muted mb-4">
                    عذراً، لا توجد كتب أخرى متاحة في نفس فئة هذا الكتاب حالياً.<br>
                    يمكنك تصفح مجموعتنا الكاملة من الكتب للعثور على المزيد من الخيارات المثيرة.
                </p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="{{ route('index.page') }}" class="btn btn-primary">
                        <i class="fas fa-home me-2"></i>العودة للرئيسية
                    </a>
                    <a href="#" class="btn btn-outline-primary" onclick="window.history.back();">
                        <i class="fas fa-arrow-right me-2"></i>العودة للخلف
                    </a>
                </div>
            </div>
        </div>
    @endif
</div>
    <section>
        <div class="categories-section text-center">
        
        <div class="category-grid d-flex justify-content-center flex-wrap">
            <!-- بطاقة الفئة -->
            <div class="category-card" onclick="window.location.href='{{ route('by-category', ['category' => 5]) }}'">
            <img src="{{ asset('images/philosophy.svg') }}" alt="روايات" class="category-icon">
            <h3>فلسفة</h3>
            <p>اكتشف أروع الروايات من الأدب العربي والعالمي.</p>
            </div>
            <div class="category-card" onclick="window.location.href='{{ route('by-category', ['category' => 6]) }}'">
            <img src="{{ asset('images/novels.svg') }}" alt="كتب دينية" class="category-icon">
            <h3> كتب الفكر</h3>
            <p>تعرف على الكتب التي تقربك من الإيمان.</p>
            </div>
            <div class="category-card" onclick="window.location.href='{{ route('by-category', ['category' => 7]) }}'">
            <img src="{{ asset('images/psychology.svg') }}" alt="تنمية ذاتية" class="category-icon">
            <h3>علم النفس</h3>
            <p>كتب تحفزك لتحقيق أفضل نسخة من نفسك.</p>
            </div>
            <div class="category-card" onclick="window.location.href='{{ route('by-category', ['category' => 8]) }}'">
            <img src="{{ asset('images/sociologie.svg') }}" alt="قصص الأطفال" class="category-icon">
            <h3> علم الاجتماع</h3>
            <p>قصص ممتعة ومفيدة للصغار.</p>
            </div>
        </div>
        </div>
    </section>  
    <!--$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$ end second categories $$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$ -->
    

        
    <script src="{{ asset('js/scripts.js') }}"></script>
    <script src="{{ asset('js/carousel.js') }}"></script>
    <script src="{{ asset('js/header.js') }}"></script>
    <footer>
        @include('footer')
    </footer>
        
    
    



</body>
</html>


