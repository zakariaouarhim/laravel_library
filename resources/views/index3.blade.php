<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مكتبة بيع الكتب</title>
    <!-- Correct CSS linking -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Slick Carousel CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/slick-carousel/slick/slick.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/slick-carousel/slick/slick-theme.css">

     <!-- Include jQuery -->
     <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>

    <!-- Include Slick Carousel -->
    <script src="https://cdn.jsdelivr.net/npm/slick-carousel/slick/slick.min.js"></script>

    <!-- Include Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Corrected favicon link -->
    <link rel="icon" href="{{ asset('images/logo.svg') }}">

</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <!-- Logo -->
        <a class="navbar-brand" href="#">
            <img src="{{ asset('images/logo.svg') }}" alt="شعار المكتبة" class="d-inline-block align-text-top">
            <span class="ms-2"></span>
        </a>
        <!-- Toggler for mobile view -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <span class="navbar-toggler-icon"></span>
        </button>
        <!-- Navbar Content -->
        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav ms-auto">
                <!-- Dropdown for Arabic Books -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="arabicBooksDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        الكتب العربية
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="arabicBooksDropdown">
                        <li><a class="dropdown-item" href="#fiction-arabic">روايات</a></li>
                        <li><a class="dropdown-item" href="#science-arabic">الكتب العلمية</a></li>
                        <li><a class="dropdown-item" href="#history-arabic">التاريخ</a></li>
                        <li><a class="dropdown-item" href="#children-arabic">كتب الأطفال</a></li>
                    </ul>
                </li>

                <!-- Dropdown for English Books -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="englishBooksDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        الكتب الإنجليزية
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="englishBooksDropdown">
                        <li><a class="dropdown-item" href="#fiction-english">Fiction</a></li>
                        <li><a class="dropdown-item" href="#science-english">Science</a></li>
                        <li><a class="dropdown-item" href="#history-english">History</a></li>
                        <li><a class="dropdown-item" href="#children-english">Children's Books</a></li>
                    </ul>
                </li>

                <!-- Static Links -->
                <li class="nav-item">
                    <a class="nav-link" href="#bestsellers">الأكثر مبيعًا</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#new-releases">الإصدارات الحديثة</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#collections">المجموعات</a>
                </li>
                
            </ul>
        </div>
    </div>
</nav>


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

    <!-- Featured Books -->
    <section id="featured-books" class="py-5">
    <div class="container">
        <h2 class="text-center mb-4">الكتب المميزة</h2>
        <div id="bookCarousel" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <div class="row">
                        <div class="col-6 col-md-2">
                            <div class="card">
                                <img src="{{ asset('images/book1.png') }}" class="card-img-top" alt="Book 1">
                                <div class="card-body">
                                    <h5 class="card-title">عنوان الكتاب 1</h5>
                                    <p class="card-text">وصف مختصر للكتاب 5</p>
                                    <a href="#" class="btn btn-primary">تفاصيل أكثر</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-2">
                            <div class="card">
                                <img src="{{ asset('images/book2.png') }}" class="card-img-top" alt="Book 2">
                                <div class="card-body">
                                    <h5 class="card-title">عنوان الكتاب 2</h5>
                                    <p class="card-text">وصف مختصر للكتاب 5</p>
                                    <a href="#" class="btn btn-primary">تفاصيل أكثر</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-2">
                            <div class="card">
                                <img src="{{ asset('images/book3.png') }}" class="card-img-top" alt="Book 3">
                                <div class="card-body">
                                    <h5 class="card-title">عنوان الكتاب 3</h5>
                                    <p class="card-text">وصف مختصر للكتاب 5</p>
                                    <a href="#" class="btn btn-primary">تفاصيل أكثر</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-2">
                            <div class="card">
                                <img src="{{ asset('images/book4.png') }}" class="card-img-top" alt="Book 4">
                                <div class="card-body">
                                    <h5 class="card-title">عنوان الكتاب 4</h5>
                                    <p class="card-text">وصف مختصر للكتاب 5</p>
                                    <a href="#" class="btn btn-primary">تفاصيل أكثر</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-2">
                            <div class="card">
                                <img src="{{ asset('images/book5.png') }}" class="card-img-top" alt="Book 5">
                                <div class="card-body">
                                    <h5 class="card-title">عنوان الكتاب 5</h5>
                                    <p class="card-text">وصف مختصر للكتاب 5</p>
                                    <a href="#" class="btn btn-primary">تفاصيل أكثر</a>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                </div>
                <div class="carousel-item">
                    <div class="row">
                        <!-- Repeat similar book cards here -->
                        <div class="col-6 col-md-2">
                            <div class="card">
                                <img src="{{ asset('images/book6.png') }}" class="card-img-top" alt="Book 6">
                                <div class="card-body">
                                    <h5 class="card-title">عنوان الكتاب 6</h5>
                                    <p class="card-text">وصف مختصر للكتاب 5</p>
                                    <a href="#" class="btn btn-primary">تفاصيل أكثر</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Controls -->
            <button class="carousel-control-prev" type="button" data-bs-target="#bookCarousel" data-bs-slide="prev" >
                <span class="carousel-control-prev-icon" aria-hidden="true" ></span>
                <span class="visually-hidden">السابق</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#bookCarousel" data-bs-slide="next" >
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">التالي</span>
            </button>
        </div>
    </div>
</section>
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


<!-- second carousel  -->

<!-- Featured Books -->
<section id="featured-books" class="py-5">
    <div class="container">
        <h2 class="text-center mb-4">الاكثر مبيعا</h2>
        <div id="bookCarousel2" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <div class="row">
                        <div class="col-6 col-md-2">
                            <div class="card">
                                <img src="{{ asset('images/book1.png') }}" class="card-img-top" alt="Book 1">
                                <div class="card-body">
                                    <h5 class="card-title">عنوان الكتاب 1</h5>
                                    <p class="card-text">وصف مختصر للكتاب 5</p>
                                    <a href="#" class="btn btn-primary">تفاصيل أكثر</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-2">
                            <div class="card">
                                <img src="{{ asset('images/book2.png') }}" class="card-img-top" alt="Book 2">
                                <div class="card-body">
                                    <h5 class="card-title">عنوان الكتاب 2</h5>
                                    <p class="card-text">وصف مختصر للكتاب 5</p>
                                    <a href="#" class="btn btn-primary">تفاصيل أكثر</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-2">
                            <div class="card">
                                <img src="{{ asset('images/book3.png') }}" class="card-img-top" alt="Book 3">
                                <div class="card-body">
                                    <h5 class="card-title">عنوان الكتاب 3</h5>
                                    <p class="card-text">وصف مختصر للكتاب 5</p>
                                    <a href="#" class="btn btn-primary">تفاصيل أكثر</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-2">
                            <div class="card">
                                <img src="{{ asset('images/book4.png') }}" class="card-img-top" alt="Book 4">
                                <div class="card-body">
                                    <h5 class="card-title">عنوان الكتاب 4</h5>
                                    <p class="card-text">وصف مختصر للكتاب 5</p>
                                    <a href="#" class="btn btn-primary">تفاصيل أكثر</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-2">
                            <div class="card">
                                <img src="{{ asset('images/book5.png') }}" class="card-img-top" alt="Book 5">
                                <div class="card-body">
                                    <h5 class="card-title">عنوان الكتاب 5</h5>
                                    <p class="card-text">وصف مختصر للكتاب 5</p>
                                    <a href="#" class="btn btn-primary">تفاصيل أكثر</a>
                                </div>
                            </div>
                        </div>
                        
                    </div>
                </div>
                <div class="carousel-item">
                    <div class="row">
                        <!-- Repeat similar book cards here -->
                        <div class="col-6 col-md-2">
                            <div class="card">
                                <img src="{{ asset('images/book6.png') }}" class="card-img-top" alt="Book 6">
                                <div class="card-body">
                                    <h5 class="card-title">عنوان الكتاب 6</h5>
                                    <p class="card-text">وصف مختصر للكتاب 5</p>
                                    <a href="#" class="btn btn-primary">تفاصيل أكثر</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Controls -->
            <button class="carousel-control-prev" type="button" data-bs-target="#bookCarousel2" data-bs-slide="prev" >
                <span class="carousel-control-prev-icon" aria-hidden="true" ></span>
                <span class="visually-hidden">السابق</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#bookCarousel2" data-bs-slide="next" >
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">التالي</span>
            </button>
        </div>
    </div>
</section>


 <!-- end second carousel  -->

<!-- ********************************** carousel number 3 ************************************************** -->


<!-- Bootstrap 4 CSS -->
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<div id="carouselExampleControls" class="carousel slide" data-ride="carousel">
  <div class="carousel-inner">
    <div class="carousel-item active">
        <div class="card" style="width: 18rem;">
            <img class="card-img-top" src="{{ asset('images/book6.png') }}" alt="Card image cap">
            <div class="card-body">
            <h5 class="card-title">Card title</h5>
            <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
            <a href="#" class="btn btn-primary">Go somewhere</a>
        </div>
    </div>
    </div>
    <div class="carousel-item">
      <div class="card" style="width: 18rem;">
            <img class="card-img-top" src="{{ asset('images/book6.png') }}" alt="Card image cap">
            <div class="card-body">
            <h5 class="card-title">Card title</h5>
            <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
            <a href="#" class="btn btn-primary">Go somewhere</a>
        </div>
    </div>
    <div class="carousel-item">
      <div class="card" style="width: 18rem;">
            <img class="card-img-top" src="{{ asset('images/book6.png') }}" alt="Card image cap">
            <div class="card-body">
            <h5 class="card-title">Card title</h5>
            <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
            <a href="#" class="btn btn-primary">Go somewhere</a>
        </div>
    </div>
  </div>
  <a class="carousel-control-prev" href="#carouselExampleControls" role="button" data-slide="prev">
    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
    <span class="sr-only">Previous</span>
  </a>
  <a class="carousel-control-next" href="#carouselExampleControls" role="button" data-slide="next">
    <span class="carousel-control-next-icon" aria-hidden="true"></span>
    <span class="sr-only">Next</span>
  </a>
</div>

<!-- end third carousel  --> 


    <!-- Footer -->
    <footer id="contact" class="bg-dark text-white text-center py-4">
        <div class="container">
            <p class="mb-0">&copy; 2024 مكتبة الفقراء جميع الحقوق محفوظة.</p>
            <small>تم التصميم بمحبة ❤️.</small>
        </div>
    </footer>
   

    <!-- Custom JS -->
    <script src="{{ asset('js/scripts.js') }}"></script>



</body>
</html>

