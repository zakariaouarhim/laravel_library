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
    
        <!-- Sample Books Data -->
        <div class="related-books">
            <h3>كتب ذات صلة</h3>
            <div class="carousel-container">
                <div class="carousel-wrapper" id="carouselWrapper">
                    <!-- Sample book cards -->
                    @foreach ($books as $index => $book)
                    <div class="book-card">
                        <a href="{{ route('moredetail.page', ['id' => $book->id]) }}">
                            <img src="{{ asset($book->image) }}" class="card-img-top" alt="{{ $book->title }}" loading="lazy">
                        </a>
                        <!-- Fixed: Added missing closing tag for h6 -->
                        <h6>{{ $book->title }}</h6>
                        <!-- Rating section (optional) -->
                        <p class="book-author">
                            <i class="fas fa-user-edit me-1"></i>
                            {{ $book->author }}
                        </p>
                        <div class="price-section">
                            <span class="price">{{ $book->price }} ر.س</span>
                            <!-- Fixed: Added missing closing quote and proper escaping -->
                            <button class="add-btn" onclick="addToCart({{ $book->id }},'{{ addslashes($book->title) }}', {{ $book->price }}, '{{ addslashes($book->image) }}')">
                                <i class="fas fa-shopping-cart"></i>
                            </button>
                        </div>
                    </div>
                    @endforeach
                </div>
                <button class="carousel-nav prev" id="prevBtn" onclick="moveCarousel(-1)">
                    <i class="fas fa-chevron-right"></i>
                </button>
                <button class="carousel-nav next" id="nextBtn" onclick="moveCarousel(1)">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <br>
                <div class="carousel-indicators" id="indicators"></div>
            </div>
        </div>

   
 <!-- Success Modal -->

 <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1050;">
    <div id="cartToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-success text-white">
            <strong class="me-auto">السلة</strong>
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
        <div class="category-card">
        <img src="{{ asset('images/novels.svg') }}" alt="روايات" class="category-icon">
        <h3>روايات</h3>
        <p>اكتشف أروع الروايات من الأدب العربي والعالمي.</p>
        </div>
        <div class="category-card">
        <img src="{{ asset('images/novels.svg') }}" alt="كتب دينية" class="category-icon">
        <h3>كتب دينية</h3>
        <p>تعرف على الكتب التي تقربك من الإيمان.</p>
        </div>
        <div class="category-card">
        <img src="{{ asset('images/novels.svg') }}" alt="تنمية ذاتية" class="category-icon">
        <h3>تنمية ذاتية</h3>
        <p>كتب تحفزك لتحقيق أفضل نسخة من نفسك.</p>
        </div>
        <div class="category-card">
        <img src="{{ asset('images/novels.svg') }}" alt="قصص الأطفال" class="category-icon">
        <h3>قصص الأطفال</h3>
        <p>قصص ممتعة ومفيدة للصغار.</p>
        </div>
    </div>
    </div>
    <!-- end first categories -->


     <!--begin of the carousel-->
     <section id="featured-books" class="py-5"> 
        @csrf
        <h2 class="text-center mb-4">كتب مميزة</h2>
        @if ($books->isEmpty())
            <div class="alert alert-info text-center">لا توجد كتب متاحة حاليًا.</div>
        @else
            <div id="bookCarousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="3000">
                <!-- Carousel Inner -->
                <div class="carousel-inner">
                    @foreach ($books as $index => $book)
                        <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                            <div class="row justify-content-center">
                                <!-- Wider Card Column -->
                                <div class="col-md-6 col-sm-8 col-12">
                                    <!-- Updated Card Structure -->
                                    <div class="card border-light">
                                        <div class="quick-actions">
                                                <button class="action-btn" title="إضافة للمفضلة"><i class="far fa-heart"></i></button>
                                                <button class="action-btn" title="إضافة للسلة"  onclick="addToCart({{ $book->id }},'{{ $book->title }}', {{ $book->price }}, '{{ $book->image }}')">
                                                    <i class="fas fa-shopping-cart"></i>
                                                </button>
                                            </div>
                                        <!-- Clickable image linking to moredetail page -->
                                        <div class="card-header">
                                            
                                            <a href="{{ route('moredetail.page', ['id' => $book->id]) }}">
                                                <img src="{{ asset($book->image) }}" class="card-img-top" alt="{{ $book->title }}" loading="lazy">
                                            </a>
                                        </div>
                                        <!-- Card body with book details -->
                                        <div class="card-body border-top border-light">
                                            <a href="{{ route('moredetail.page', ['id' => $book->id]) }}" class="h5">{{ $book->title }}</a>
                                            
                                            <!-- Rating section (optional) -->
                                            <p class="book-author">
                                                <i class="fas fa-user-edit me-1"></i> {{ $book->author }}
                                            </p>
                                            
                                            
                                        </div>
                                        <!-- Card footer with price and Add to Cart button -->
                                        <div class="card-footer border-top border-light p-4">
                                            <!-- Price Section -->
                                            <div class="text-center mb-3">
                                                <span class="h6 mb-0 text-gray text-through mr-2" style="text-decoration:line-through">
                                                    {{ $book->price + 50 }}  <!-- Example: Original price -->
                                                </span>
                                                <span class="h5 mb-0 text-danger">{{ $book->price }} ر.س</span> <!-- Discounted price -->
                                            </div>
                                            <!-- Add to Cart Button -->
                                            <div class="text-center">
                                                <button class="btn btn-primary" type="button" data-title="{{ $book->title }}" data-price="{{ $book->price }}" data-image="{{ asset($book->image) }}" aria-label="أضف الكتاب للسلة"  onclick="addToCart({{ $book->id }},'{{ $book->title }}', {{ $book->price }}, '{{ $book->image }}')">
                                                    <i class="fas fa-cart-plus"></i> 
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- End of Updated Card Structure -->
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <!-- Carousel Controls -->
                <button class="carousel-control-prev custom-prev" type="button" data-bs-target="#bookCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">السابق</span>
                </button>
                <button class="carousel-control-next custom-next" type="button" data-bs-target="#bookCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">التالي</span>
                </button>
            </div>
        @endif
    </section>
    <!-- $$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$ second categories $$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$$ -->
    <section>
        <div class="categories-section text-center">
        
        <div class="category-grid d-flex justify-content-center flex-wrap">
            <!-- بطاقة الفئة -->
            <div class="category-card">
            <img src="{{ asset('images/novels.svg') }}" alt="روايات" class="category-icon">
            <h3>فلسفة</h3>
            <p>اكتشف أروع الروايات من الأدب العربي والعالمي.</p>
            </div>
            <div class="category-card">
            <img src="{{ asset('images/novels.svg') }}" alt="كتب دينية" class="category-icon">
            <h3> كتب الفكر</h3>
            <p>تعرف على الكتب التي تقربك من الإيمان.</p>
            </div>
            <div class="category-card">
            <img src="{{ asset('images/novels.svg') }}" alt="تنمية ذاتية" class="category-icon">
            <h3>علم النفس</h3>
            <p>كتب تحفزك لتحقيق أفضل نسخة من نفسك.</p>
            </div>
            <div class="category-card">
            <img src="{{ asset('images/novels.svg') }}" alt="قصص الأطفال" class="category-icon">
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


