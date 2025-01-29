<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مكتبة بيع الكتب</title>
    <!-- Correct CSS linking -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/headerstyle.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">

    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.rtl.min.css" integrity="sha384-gXt9imSW0VcJVHezoNQsP+TNrjYXoGcrqBZJpry9zJt8PCQjobwmhMGaDHTASo9N" crossorigin="anonymous">

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Favicon -->
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">

    <meta name="csrf-token" content="{{ csrf_token() }}">

</head>

<body>
    @include('header')

    <!-- Header with Search Bar -->
    <header class="header-section text-white text-center py-5" style="background: url('{{ asset('images/header-banner.svg') }}') no-repeat center center; background-size: cover; height: 400px;">
        <div class="container">
            <h1 class="display-4 fw-bold">ابحث عن كتابك المفضل</h1>
            <p class="lead">ابحث في مجموعتنا الكبيرة من الكتب عبر الأنواع والتصنيفات.</p>
            <form class="d-flex justify-content-center mt-4">
                <input type="text" class="form-control w-50 me-2" placeholder="ابحث عن كتاب بالعنوان، المؤلف، أو النوع">
                <button type="submit" class="btn btn-dark">بحث</button>
            </form>
        </div>
    </header>

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
                                    <!-- Clickable image linking to moredetail page -->
                                    <div class="card-header">
                                        <a href="{{ route('moredetail.page', ['id' => $book->id]) }}">
                                            <img src="{{ asset($book->image) }}" class="card-img-top" alt="{{ $book->title }}" loading="lazy">
                                        </a>
                                    </div>
                                    <!-- Card body with book details -->
                                    <div class="card-body border-top border-light">
                                        <a href="{{ route('moredetail.page', ['id' => $book->id]) }}" class="h5">{{ $book->title }}</a>
                                        <h6 class="font-weight-light text-gray mt-2">{{ $book->author }}</h6>
                                        <!-- Rating section (optional) -->
                                        <div class="d-flex mt-3">
                                            <i class="star fas fa-star text-warning mr-1"></i>
                                            <i class="star fas fa-star text-warning mr-1"></i>
                                            <i class="star fas fa-star text-warning mr-1"></i>
                                            <i class="star fas fa-star text-warning mr-1"></i>
                                            <i class="star fas fa-star text-warning"></i>
                                            <span class="badge badge-pill badge-gray ml-2">4.7</span>
                                        </div>
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
                                            <button class="btn btn-primary" type="button" data-title="{{ $book->title }}" data-price="{{ $book->price }}" data-image="{{ asset($book->image) }}" aria-label="أضف الكتاب للسلة"  onclick="addToCart({{ $book->id }})">
                                                <i class="fas fa-cart-plus"></i> أضف إلى السلة
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
 <!-- Success Modal -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1050;">
        <div id="cartSuccessToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header bg-success text-white">
                <strong class="me-auto">
                    <i class="fas fa-shopping-cart me-2"></i>
                    تمت الإضافة
                </strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                تمت إضافة الكتاب إلى السلة بنجاح
            </div>
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


    <!-- ********************************** carousel number 2 ************************************************** -->
    <section id="featured-books" class="py-5"> 
        <h2 class="text-center mb-4">كتب مميزة</h2>
        @if ($books->isEmpty())
            <div class="alert alert-info text-center">لا توجد كتب متاحة حاليًا.</div>
        @else
            <div id="bookCarousel2" class="carousel slide" data-bs-ride="carousel" data-bs-interval="3000">
                

                <!-- Carousel Inner -->
                <div class="carousel-inner">
                    @foreach ($books as $index => $book)
                    
                        <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                            <div class="row justify-content-center">
                                <div class="col-md-4 col-sm-6 col-12">
                                    <div class="card">
                                        <img src="{{ asset($book->image) }}" class="card-img-top" alt="{{ $book->title }}" loading="lazy">
                                        <div class="card-body text-center">
                                            <h5 class="card-title">{{ $book->title }}</h5>
                                            <p class="card-text">{{ $book->author }}</p>
                                            <p class="card-text">{{ $book->price }} ر.س</p>
                                            <a href="{{ route('moredetail.page', ['id' => $book->id]) }}" class="btn btn-primary">شراء</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                    @endforeach
                </div>
                
                <!-- Carousel Controls -->
                <button class="carousel-control-prev custom-prev" type="button" data-bs-target="#bookCarousel2" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">السابق</span>
                </button>
                <button class="carousel-control-next custom-next" type="button" data-bs-target="#bookCarousel2" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">التالي</span>
                </button>
            </div>
        @endif
    </section>

    <!-- ********************************** end carousel number 2 ************************************************** -->

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
    <script src="{{ asset('js/header.js') }}"></script>
    <footer>
        @include('footer')
    </footer>
        
    
    



</body>
</html>


