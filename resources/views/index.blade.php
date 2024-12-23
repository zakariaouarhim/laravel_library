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
   
    <!-- Featured Books Slider -->
    <section id="featured-books" class="py-5"> 
        <h2 class="text-center mb-4"> كتب ذات صلة</h2>
        <div id="bookCarousel" class="carousel slide position-relative" data-bs-ride="carousel">
            <div class="carousel-inner">
                
            <!-- First Slide with 5 Books -->
            <div class="carousel-item active">
                <div class="row g-4">
                    <div class="col-md-2 col-sm-4 col-6">
                        <div class="card book-card">
                            <img src="{{ asset('images/book1.png') }}" class="card-img-top" alt="كتاب 1">
                            <div class="card-body text-center">
                                <h5 class="card-title">عنوان الكتاب 1</h5>
                                <p class="card-text">١٥٠ ر.س</p>
                                <a href="{{ route('moredetail.page') }}" class="btn btn-primary">شراء</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-4 col-6">
                        <div class="card book-card">
                            <img src="{{ asset('images/book2.png') }}" class="card-img-top" alt="كتاب 2">
                            <div class="card-body text-center">
                                <h5 class="card-title">عنوان الكتاب 2</h5>
                                <p class="card-text">١٢٥ ر.س</p>
                                <a href="{{ route('moredetail.page') }}" class="btn btn-primary">شراء</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-4 col-6">
                        <div class="card book-card">
                            <img src="{{ asset('images/book3.png') }}" class="card-img-top" alt="كتاب 3">
                            <div class="card-body text-center">
                                <h5 class="card-title">عنوان الكتاب 3</h5>
                                <p class="card-text">١٠٠ ر.س</p>
                                <a href="{{ route('moredetail.page') }}" class="btn btn-primary">شراء</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-4 col-6">
                        <div class="card book-card">
                            <img src="{{ asset('images/book4.png') }}" class="card-img-top" alt="كتاب 4">
                            <div class="card-body text-center">
                                <h5 class="card-title">عنوان الكتاب 4</h5>
                                <p class="card-text">٧٥ ر.س</p>
                                <a href="{{ route('moredetail.page') }}" class="btn btn-primary">شراء</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-4 col-6">
                        <div class="card book-card">
                            <img src="{{ asset('images/book5.png') }}" class="card-img-top" alt="كتاب 5">
                            <div class="card-body text-center">
                                <h5 class="card-title">عنوان الكتاب 5</h5>
                                <p class="card-text">٥٠ ر.س</p>
                                <a href="{{ route('moredetail.page') }}" class="btn btn-primary">شراء</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Second Slide with 3 Books -->
            <div class="carousel-item">
                <div class="row g-4">
                    <div class="col-md-2 col-sm-4 col-6">
                        <div class="card book-card">
                            <img src="{{ asset('images/book6.png') }}" class="card-img-top" alt="كتاب 6">
                            <div class="card-body text-center">
                                <h5 class="card-title">عنوان الكتاب 6</h5>
                                <p class="card-text">٦٠ ر.س</p>
                                <a href="{{ route('moredetail.page') }}" class="btn btn-primary">شراء</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-4 col-6">
                        <div class="card book-card">
                            <img src="{{ asset('images/book7.png') }}" class="card-img-top" alt="كتاب 7">
                            <div class="card-body text-center">
                                <h5 class="card-title">عنوان الكتاب 7</h5>
                                <p class="card-text">٤٠ ر.س</p>
                                <a href="{{ route('moredetail.page') }}" class="btn btn-primary">شراء</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-4 col-6">
                        <div class="card book-card">
                            <img src="{{ asset('images/book8.png') }}" class="card-img-top" alt="كتاب 7">
                            <div class="card-body text-center">
                                <h5 class="card-title">عنوان الكتاب 8</h5>
                                <p class="card-text">٤٠ ر.س</p>
                                <a href="{{ route('moredetail.page') }}" class="btn btn-primary">شراء</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-4 col-6">
                        <div class="card book-card">
                            <img src="{{ asset('images/book9.png') }}" class="card-img-top" alt="كتاب 7">
                            <div class="card-body text-center">
                                <h5 class="card-title">عنوان الكتاب 9</h5>
                                <p class="card-text">٤٠ ر.س</p>
                                <a href="{{ route('moredetail.page') }}" class="btn btn-primary">شراء</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-4 col-6">
                        <div class="card book-card">
                            <img src="{{ asset('images/book10.png') }}" class="card-img-top" alt="كتاب 7">
                            <div class="card-body text-center">
                                <h5 class="card-title">عنوان الكتاب 10</h5>
                                <p class="card-text">٤٠ ر.س</p>
                                <a href="{{ route('moredetail.page') }}" class="btn btn-primary">شراء</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-4 col-6">
                        <div class="card book-card">
                            <img src="{{ asset('images/book11.png') }}" class="card-img-top" alt="كتاب 7">
                            <div class="card-body text-center">
                                <h5 class="card-title">عنوان الكتاب 11</h5>
                                <p class="card-text">٤٠ ر.س</p>
                                <a href="{{ route('moredetail.page') }}" class="btn btn-primary">شراء</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2 col-sm-4 col-6">
                        <div class="card book-card">
                            <img src="{{ asset('images/book12.png') }}" class="card-img-top" alt="كتاب 8">
                            <div class="card-body text-center">
                                <h5 class="card-title">عنوان الكتاب 12</h5>
                                <p class="card-text">٢٥ ر.س</p>
                                <a href="{{ route('moredetail.page') }}" class="btn btn-primary">شراء</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
    </section>
    <!--end of the carousel-->

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
    <!-- Featured Books -->
    <section id="featured-books2" class="py-5">
        <h2 class="text-center mb-4">الاكثر مبيعا</h2>
        <div id="bookCarousel2" class="carousel slide position-relative" data-bs-ride="carousel">
            <div class="carousel-inner">
                <!-- First Slide with 5 Books -->
                <div class="carousel-item active">
                    <div class="row g-4">
                        <div class="col-md-2 col-sm-4 col-6">
                            <div class="card book-card">
                                <img src="{{ asset('images/book1.png') }}" class="card-img-top" alt="كتاب 1">
                                <div class="card-body text-center">
                                    <h5 class="card-title">عنوان الكتاب 1</h5>
                                    <p class="card-text">١٥٠ ر.س</p>
                                    <a href="#" class="btn btn-primary">شراء</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-4 col-6">
                            <div class="card book-card">
                                <img src="{{ asset('images/book2.png') }}" class="card-img-top" alt="كتاب 2">
                                <div class="card-body text-center">
                                    <h5 class="card-title">عنوان الكتاب 2</h5>
                                    <p class="card-text">١٢٥ ر.س</p>
                                    <a href="#" class="btn btn-primary">شراء</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-4 col-6">
                            <div class="card book-card">
                                <img src="{{ asset('images/book3.png') }}" class="card-img-top" alt="كتاب 3">
                                <div class="card-body text-center">
                                    <h5 class="card-title">عنوان الكتاب 3</h5>
                                    <p class="card-text">١٠٠ ر.س</p>
                                    <a href="#" class="btn btn-primary">شراء</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-4 col-6">
                            <div class="card book-card">
                                <img src="{{ asset('images/book4.png') }}" class="card-img-top" alt="كتاب 4">
                                <div class="card-body text-center">
                                    <h5 class="card-title">عنوان الكتاب 4</h5>
                                    <p class="card-text">٧٥ ر.س</p>
                                    <a href="#" class="btn btn-primary">شراء</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-4 col-6">
                            <div class="card book-card">
                                <img src="{{ asset('images/book5.png') }}" class="card-img-top" alt="كتاب 5">
                                <div class="card-body text-center">
                                    <h5 class="card-title">عنوان الكتاب 5</h5>
                                    <p class="card-text">٥٠ ر.س</p>
                                    <a href="#" class="btn btn-primary">شراء</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Second Slide with 5 Books -->
                <div class="carousel-item">
                    <div class="row g-4">
                        <div class="col-md-2 col-sm-4 col-6">
                            <div class="card book-card">
                                <img src="{{ asset('images/book6.png') }}" class="card-img-top" alt="كتاب 6">
                                <div class="card-body text-center">
                                    <h5 class="card-title">عنوان الكتاب 6</h5>
                                    <p class="card-text">٦٠ ر.س</p>
                                    <a href="#" class="btn btn-primary">شراء</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-4 col-6">
                            <div class="card book-card">
                                <img src="{{ asset('images/book7.png') }}" class="card-img-top" alt="كتاب 7">
                                <div class="card-body text-center">
                                    <h5 class="card-title">عنوان الكتاب 7</h5>
                                    <p class="card-text">٤٠ ر.س</p>
                                    <a href="#" class="btn btn-primary">شراء</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-4 col-6">
                            <div class="card book-card">
                                <img src="{{ asset('images/book8.png') }}" class="card-img-top" alt="كتاب 8">
                                <div class="card-body text-center">
                                    <h5 class="card-title">عنوان الكتاب 8</h5>
                                    <p class="card-text">٢٥ ر.س</p>
                                    <a href="#" class="btn btn-primary">شراء</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2 col-sm-4 col-6">
                            <div class="card book-card">
                                <img src="{{ asset('images/book9.png') }}" class="card-img-top" alt="كتاب 9">
                                <div class="card-body text-center">
                                    <h5 class="card-title">عنوان الكتاب 9</h5>
                                    <p class="card-text">١٠٠ ر.س</p>
                                    <a href="#" class="btn btn-primary">شراء</a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-2 col-sm-4 col-6">
                            <div class="card book-card">
                                <img src="{{ asset('images/book10.png') }}" class="card-img-top" alt="كتاب 10">
                                <div class="card-body text-center">
                                    <h5 class="card-title">عنوان الكتاب 10</h5>
                                    <p class="card-text">٤٥ ر.س</p>
                                    <a href="#" class="btn btn-primary">شراء</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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
    <footer>
        @include('footer')
    </footer>
        
    
    


</body>
</html>


