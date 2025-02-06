<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
   <!-- Stylesheets -->
    <link rel="stylesheet" href="{{ asset('css/headerstyle.css') }}">
    <link rel="stylesheet" href="{{ asset('css/checkout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    
    <!-- Favicon -->
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">

    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.rtl.min.css">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body class="bg-light">
    <header>
        @include('header')
    </header>
    

    <main class="container my-5">
        <div class="row g-4">
            <!-- Cart Items Section -->
            <section class="col-lg-7">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h2 class="fs-5 m-0">عربة التسوق</h2>
                    </div>
                    <div class="card-body">
                        <!-- Cart Item -->
                        <div class="d-flex align-items-center mb-4">
                            <img src="{{ asset('images/image.jfif') }}" alt="Book Thumbnail" class="img-fluid rounded me-3" style="width: 80px;">
                            <div class="flex-grow-1">
                                <h3 class="fs-6 mb-1">اسم الكتاب</h3>
                                <p class="mb-0 text-muted">الكاتب: اسم الكاتب</p>
                            </div>
                            <div class="d-flex align-items-center">
                                <span class="fw-bold text-primary me-3">50.00 ر.س</span>
                                <button class="btn btn-outline-danger btn-sm"><i class="fas fa-trash"></i></button>
                            </div>
                        </div>
                        <hr>
                        <div class="text-center">
                            <p class="text-muted mb-0">إجمالي الكتب: 3</p>
                        </div>
                    </div>
                </div>

                <!-- Shipping Address Section -->
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-white">
                        <h2 class="fs-5 m-0">عنوان الشحن</h2>
                    </div>
                    <div class="card-body">
                        <form id="shipping-form">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="first-name" class="form-label">الاسم الأول</label>
                                    <input type="text" id="first-name" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="last-name" class="form-label">الاسم الأخير</label>
                                    <input type="text" id="last-name" class="form-control" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="address" class="form-label">العنوان</label>
                                <input type="text" id="address" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="country" class="form-label">الدولة</label>
                                <select id="country" class="form-select">
                                    <option value="المغرب">المغرب</option>
                                    <!-- Add other countries if needed -->
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="region" class="form-label">المنطقة</label>
                                <input type="text" id="region" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="city" class="form-label">المدينة</label>
                                <input type="text" id="city" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="postal-code" class="form-label">الرمز البريدي</label>
                                <input type="text" id="postal-code" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="phone-number" class="form-label">رقم الهاتف</label>
                                <input type="tel" id="phone-number" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="alternate-phone" class="form-label">رقم هاتف آخر</label>
                                <input type="tel" id="alternate-phone" class="form-control">
                            </div>
                        </form>
                    </div>
                </div>
                <br>
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h2 class="fs-5 m-0">وسيلة الدفع</h2>
                    </div>
                    <div class="card-body">
                        
                        <div class="form-check mb-3 text-end">
                            <input class="form-check-input me-2" type="radio" name="paymentMethod" id="cashOnDelivery" checked>
                            <label class="form-check-label" for="cashOnDelivery">
                                الدفع عند الاستلام
                            </label>
                        </div>
                        <div class="form-check mb-3 text-end">
                            <input class="form-check-input me-2" type="radio" name="paymentMethod" id="creditCard" required>
                            <label class="form-check-label" for="creditCard">
                                بطاقة ائتمان
                            </label>
                        </div>

                        <!-- Credit Card Information -->
                        <div id="creditCardInfo" class="mt-3" style="display: none;">
                            <h3 class="fs-6 mb-3">معلومات بطاقة الائتمان</h3>
                            <div class="mb-3">
                                <label for="cardNumber" class="form-label">رقم البطاقة</label>
                                <input type="text" id="cardNumber" class="form-control" placeholder="1234 5678 9012 3456">
                            </div>
                            <div class="mb-3">
                                <label for="cardName" class="form-label">اسم صاحب البطاقة</label>
                                <input type="text" id="cardName" class="form-control" placeholder="اسم صاحب البطاقة">
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="expiryDate" class="form-label">تاريخ الانتهاء</label>
                                    <input type="text" id="expiryDate" class="form-control" placeholder="MM/YY">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="cvv" class="form-label">CVV</label>
                                    <input type="text" id="cvv" class="form-control" placeholder="123">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                



                
            </section>
            
            <!-- Order Summary and Payment Method Section -->
            <section class="col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h2 class="fs-5 m-0">ملخص الطلب</h2>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-4">
                            <li class="d-flex justify-content-between">
                                <span>إجمالي المنتجات:</span>
                                <span>150.00 ر.س</span>
                            </li>
                            <li class="d-flex justify-content-between">
                                <span>الشحن:</span>
                                <span>20.00 ر.س</span>
                            </li>
                            <li class="d-flex justify-content-between fw-bold">
                                <span>الإجمالي:</span>
                                <span>170.00 ر.س</span>
                            </li>
                        </ul>

                        <h3 class="fs-6 mb-3">وسيلة الدفع</h3>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="radio" id="credit-card" name="payment-method" class="form-check-input" required>
                                <label for="credit-card" class="form-check-label">بطاقة ائتمان</label>
                            </div>
                            <div class="form-check">
                                <input type="radio" id="cod" name="payment-method" class="form-check-input">
                                <label for="cod" class="form-check-label">الدفع عند الاستلام</label>
                            </div>
                        </div>

                        <button class="btn btn-primary w-100">إتمام الدفع</button>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- JS -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/slick-carousel/slick/slick.min.js"></script>
    <script src="{{ asset('js/checkout.js') }}"></script>
    <script src="{{ asset('js/header.js') }}"></script>


    <footer>
    @include('footer')
    </footer>
    
</body>
</html>
