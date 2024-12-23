<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تفاصيل الكتاب</title>
    <!-- Custom CSS -->
        <link rel="stylesheet" href="{{ asset('css/moredetailstyle.css') }}">
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
    <header>
    @include('header')
    </header>
    
        <div class="container-fluid py-5">
            <div class="row g-0">
                <!-- Book Image Section -->
                <div class="col-md-5 d-flex align-items-center justify-content-center position-relative">
                    <img src="{{ asset('images/image.jfif') }}" alt="غلاف كتاب رحلة الألف ميل" class="img-fluid rounded shadow" aria-describedby="book-title">
                </div>

                <!-- Book Information Section -->
                <div class="col-md-7 p-4">
                    <h1 id="book-title" class="fw-bold mb-3">رحلة الألف ميل</h1>
                    <p class="text-muted">أحمد خالد توفيق</p>

                    <div class="d-flex align-items-center mb-3">
                        <span class="fs-4 text-primary fw-bold">29.99 ريال</span>
                        <span class="badge bg-danger ms-3">10% خصم</span>
                    </div>

                    <div class="mb-4">
                        <span class="badge bg-secondary">روايات</span>
                        <span class="badge bg-secondary">أدب عربي</span>
                    </div>

                    <p class="mb-4">رحلة استثنائية تمزج بين الواقع والخيال، تستكشف عمق التجربة الإنسانية من خلال سرد مبهر وشخصيات معقدة.</p>

                    <div class="d-flex align-items-center mb-4">
                        <div class="input-group" style="max-width: 120px;">
                            <input type="number" class="form-control text-center" value="1" min="1" aria-label="عدد النسخ">
                        </div>
                        <button class="btn btn-primary ms-3" id="addToCartButton" aria-label="أضف الكتاب للسلة">أضف إلى السلة</button>
                    </div>

                    <div class="row g-2">
                        <div class="col-sm-4">
                            <div class="p-2 border rounded">
                                <span class="fw-bold">اللغة:</span> العربية
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="p-2 border rounded">
                                <span class="fw-bold">عدد الصفحات:</span> 250
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="p-2 border rounded">
                                <span class="fw-bold">دار النشر:</span> دار الشروق
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Book Details Tabs -->
            <div class="mt-5">
                <ul class="nav nav-tabs" id="bookDetailsTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="description-tab" data-bs-toggle="tab" data-bs-target="#description" type="button" role="tab" aria-controls="description" aria-selected="true">الوصف</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab" aria-controls="details" aria-selected="false">تفاصيل إضافية</button>
                    </li>
                </ul>
                <div class="tab-content border rounded-bottom p-3" id="bookDetailsTabsContent">
                    <div class="tab-pane fade show active" id="description" role="tabpanel" aria-labelledby="description-tab">
                        <p>في رحلة الألف ميل، يقدم الكاتب رؤية عميقة للتحديات الإنسانية، مستكشفًا مواضيع الهوية والانتماء والصراع الداخلي. من خلال شخصيات متعددة الأبعاد، يرسم الكاتب لوحة فنية تعكس تعقيدات الحياة المعاصرة.</p>
                    </div>
                    <div class="tab-pane fade" id="details" role="tabpanel" aria-labelledby="details-tab">
                        <table class="table table-bordered">
                            <tr>
                                <th>ISBN</th>
                                <td>978-602-1234-56-7</td>
                            </tr>
                            <tr>
                                <th>تاريخ النشر</th>
                                <td>يناير 2024</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!--begin of the carousel-->
        <section id="featured-books" class="py-5"> 
                
                <h2 class="text-center my-5">كتب ذات صلة</h2>
                <!-- Featured Books Slider -->
                <div id="bookCarousel" class="carousel slide position-relative" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        
                    <!-- First Slide with 5 Books -->
                    <div class="carousel-item active">
                        <div class="row g-4">
                            <div class="col-md-2 col-sm-4 col-6">
                                <div class="card book-card">
                                    <img src="{{ asset('images/book1.png') }}" class="card-img-top" alt="كتاب 1" loading="lazy" >
                                    <div class="card-body text-center">
                                        <h5 class="card-title">عنوان الكتاب 1</h5>
                                        <p class="card-text">١٥٠ ر.س</p>
                                        <a href="#" class="btn btn-primary">شراء</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2 col-sm-4 col-6">
                                <div class="card book-card">
                                    <img src="{{ asset('images/book2.png') }}" class="card-img-top" alt="كتاب 2" loading="lazy" >
                                    <div class="card-body text-center">
                                        <h5 class="card-title">عنوان الكتاب 2</h5>
                                        <p class="card-text">١٢٥ ر.س</p>
                                        <a href="#" class="btn btn-primary">شراء</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2 col-sm-4 col-6">
                                <div class="card book-card">
                                    <img src="{{ asset('images/book3.png') }}" class="card-img-top" alt="كتاب 3" loading="lazy" >
                                    <div class="card-body text-center">
                                        <h5 class="card-title">عنوان الكتاب 3</h5>
                                        <p class="card-text">١٠٠ ر.س</p>
                                        <a href="#" class="btn btn-primary">شراء</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2 col-sm-4 col-6">
                                <div class="card book-card">
                                    <img src="{{ asset('images/book4.png') }}" class="card-img-top" alt="كتاب 4" loading="lazy" >
                                    <div class="card-body text-center">
                                        <h5 class="card-title">عنوان الكتاب 4</h5>
                                        <p class="card-text">٧٥ ر.س</p>
                                        <a href="#" class="btn btn-primary">شراء</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2 col-sm-4 col-6">
                                <div class="card book-card">
                                    <img src="{{ asset('images/book5.png') }}" class="card-img-top" alt="كتاب 5" loading="lazy" >
                                    <div class="card-body text-center">
                                        <h5 class="card-title">عنوان الكتاب 5</h5>
                                        <p class="card-text">٥٠ ر.س</p>
                                        <a href="#" class="btn btn-primary">شراء</a>
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
                                    <img src="{{ asset('images/book6.png') }}" class="card-img-top" alt="كتاب 6" loading="lazy" >
                                    <div class="card-body text-center">
                                        <h5 class="card-title">عنوان الكتاب 6</h5>
                                        <p class="card-text">٦٠ ر.س</p>
                                        <a href="#" class="btn btn-primary">شراء</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2 col-sm-4 col-6">
                                <div class="card book-card">
                                    <img src="{{ asset('images/book7.png') }}" class="card-img-top" alt="كتاب 7" loading="lazy" >
                                    <div class="card-body text-center">
                                        <h5 class="card-title">عنوان الكتاب 7</h5>
                                        <p class="card-text">٤٠ ر.س</p>
                                        <a href="#" class="btn btn-primary">شراء</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2 col-sm-4 col-6">
                                <div class="card book-card">
                                    <img src="{{ asset('images/book8.png') }}" class="card-img-top" alt="كتاب 7" loading="lazy" >
                                    <div class="card-body text-center">
                                        <h5 class="card-title">عنوان الكتاب 7</h5>
                                        <p class="card-text">٤٠ ر.س</p>
                                        <a href="#" class="btn btn-primary">شراء</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2 col-sm-4 col-6">
                                <div class="card book-card">
                                    <img src="{{ asset('images/book9.png') }}" class="card-img-top" alt="كتاب 7" loading="lazy" >
                                    <div class="card-body text-center">
                                        <h5 class="card-title">عنوان الكتاب 7</h5>
                                        <p class="card-text">٤٠ ر.س</p>
                                        <a href="#" class="btn btn-primary">شراء</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2 col-sm-4 col-6">
                                <div class="card book-card">
                                    <img src="{{ asset('images/book10.png') }}" class="card-img-top" alt="كتاب 7" loading="lazy" >
                                    <div class="card-body text-center">
                                        <h5 class="card-title">عنوان الكتاب 7</h5>
                                        <p class="card-text">٤٠ ر.س</p>
                                        <a href="#" class="btn btn-primary">شراء</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2 col-sm-4 col-6">
                                <div class="card book-card">
                                    <img src="{{ asset('images/book11.png') }}" class="card-img-top" alt="كتاب 8" loading="lazy" >
                                    <div class="card-body text-center">
                                        <h5 class="card-title">عنوان الكتاب 8</h5>
                                        <p class="card-text">٢٥ ر.س</p>
                                        <a href="#" class="btn btn-primary">شراء</a>
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
    
        <!-- Success Modal -->
        <div class="toast-container position-fixed top-0 end-0 p-3">
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

        <script src="{{ asset('js/scripts.js') }}"></script>
        <script src="{{ asset('js/moredetail.js') }}"></script>
        
        <br> <br>
        <footer>
        @include('footer')
        </footer>
</body>
</html>