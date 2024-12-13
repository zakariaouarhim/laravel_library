<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مكتبة بيع الكتب</title>
    <!-- Correct CSS linking -->
    <link rel="stylesheet" href="{{ asset('css/style1.css') }}">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Swiper CSS -->
    <link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css">
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <img src="{{ asset('images/logo.svg') }}" alt="شعار المكتبة" class="d-inline-block align-text-top">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav ms-auto">
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
                    <li class="nav-item"><a class="nav-link" href="#bestsellers">الأكثر مبيعًا</a></li>
                    <li class="nav-item"><a class="nav-link" href="#new-releases">الإصدارات الحديثة</a></li>
                    <li class="nav-item"><a class="nav-link" href="#collections">المجموعات</a></li>
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
    <section id="featured-books" class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-4">الكتب المميزة</h2>

            <!-- Swiper Carousel -->
            <div class="swiper-container">
                <div class="swiper-wrapper">
                    <!-- Book 1 -->
                    <div class="swiper-slide">
                        <div class="card text-center">
                            <img src="{{ asset('images/book2.jpeg') }}" class="card-img-top" alt="كتاب 1">
                            <div class="card-body">
                                <h6 class="card-title">كتاب 1</h6>
                                <p class="card-text">وصف مختصر للكتاب 1</p>
                                <a href="#" class="btn btn-primary">تفاصيل أكثر</a>
                            </div>
                        </div>
                    </div>
                    <!-- Book 2 -->
                    <div class="swiper-slide">
                        <div class="card text-center">
                            <img src="{{ asset('images/book2.jpeg') }}" class="card-img-top" alt="كتاب 2">
                            <div class="card-body">
                                <h6 class="card-title">كتاب 2</h6>
                                <p class="card-text">وصف مختصر للكتاب 2</p>
                                <a href="#" class="btn btn-primary">تفاصيل أكثر</a>
                            </div>
                        </div>
                    </div>
                    <!-- Book 3 -->
                    <div class="swiper-slide">
                        <div class="card text-center">
                            <img src="{{ asset('images/book2.jpeg') }}" class="card-img-top" alt="كتاب 3">
                            <div class="card-body">
                                <h6 class="card-title">كتاب 3</h6>
                                <p class="card-text">وصف مختصر للكتاب 3</p>
                                <a href="#" class="btn btn-primary">تفاصيل أكثر</a>
                            </div>
                        </div>
                    </div>
                    <!-- Book 4 -->
                    <div class="swiper-slide">
                        <div class="card text-center">
                            <img src="{{ asset('images/book2.jpeg') }}" class="card-img-top" alt="كتاب 4">
                            <div class="card-body">
                                <h6 class="card-title">كتاب 4</h6>
                                <p class="card-text">وصف مختصر للكتاب 4</p>
                                <a href="#" class="btn btn-primary">تفاصيل أكثر</a>
                            </div>
                        </div>
                    </div>
                    <!-- Book 5 -->
                    <div class="swiper-slide">
                        <div class="card text-center">
                            <img src="{{ asset('images/book2.jpeg') }}" class="card-img-top" alt="كتاب 5">
                            <div class="card-body">
                                <h6 class="card-title">كتاب 5</h6>
                                <p class="card-text">وصف مختصر للكتاب 5</p>
                                <a href="#" class="btn btn-primary">تفاصيل أكثر</a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Additional Book Entries ... -->
                </div>

                <!-- Pagination and Navigation -->
                <div class="swiper-pagination"></div>
                <div class="swiper-button-next"></div>
                <div class="swiper-button-prev"></div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer id="contact" class="bg-dark text-white text-center py-4">
        <div class="container">
            <p class="mb-0">&copy; 2024 مكتبة الفقراء جميع الحقوق محفوظة.</p>
            <small>تم التصميم بمحبة ❤️.</small>
        </div>
    </footer>

    <!-- Include jQuery -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>

    <!-- Include Swiper JS -->
    <script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>

    <!-- Include Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Link to External JS File -->
    <script src="{{ asset('js/scriptsq.js') }}"></script>
</body>
</html>
