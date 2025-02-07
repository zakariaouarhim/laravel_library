<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>أسير الكتب - إتمام الشراء</title>
    
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
<body>
    @include('header')
    
    <div class="container checkout-section py-5">
        <div class="row g-4">
            <div class="col-lg-8">
                <!-- Shopping Cart Section -->
                
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h2 class="fs-5 m-0">سلة التسوق</h2>
                        <span class="text-muted">3 منتجات</span>
                    </div>
                    <div class="card-body">
                        @if(session('checkout_cart'))
                        <h3 class="mb-4">مراجعة الطلب</h3>
                        @php $cart = session('checkout_cart'); @endphp
                        <div class="cart-items">
                            @foreach($cart as $item)
                                <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                                    <img src="{{ asset($item['image']) }}" class="img-fluid rounded me-3" style="width: 80px; height: 110px; object-fit: cover;">
                                    <div class="flex-grow-1">
                                        <h3 class="fs-6 mb-1">{{ $item['title'] }}</h3>
                                        <div class="d-flex align-items-center">
                                            <label class="me-2">الكمية:</label>
                                            <input type="number" class="form-control form-control-sm" style="width: 70px;" value="{{ $item['quantity'] }}" min="1">
                                        </div>
                                    </div>
                                    <div>
                                        <span class="fw-bold text-primary">{{ $item['price'] }} ر.س</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @endif

                    </div>
                </div>

                <!-- Shipping Information Section -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h2 class="fs-5 m-0">معلومات الشحن</h2>
                    </div>
                    <div class="card-body">
                        <form id="shipping-form">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">الاسم الأول</label>
                                    <input type="text" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">الاسم الأخير</label>
                                    <input type="text" class="form-control" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">البريد الإلكتروني</label>
                                    <input type="email" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">رقم الهاتف</label>
                                    <input type="tel" class="form-control" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">العنوان التفصيلي</label>
                                <input type="text" class="form-control" placeholder="رقم المنزل، الشارع، المدينة" required>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">المدينة</label>
                                    <select class="form-select">
                                        <option>الرياض</option>
                                        <option>جدة</option>
                                        <option>مكة المكرمة</option>
                                        <option>المدينة المنورة</option>
                                        <option>الدمام</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">الرمز البريدي</label>
                                    <input type="text" class="form-control" required>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Payment Method Section -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h2 class="fs-5 m-0">طريقة الدفع</h2>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="radio" name="paymentMethod" id="cashOnDelivery" checked>
                                    <label class="form-check-label" for="cashOnDelivery">
                                        الدفع عند الاستلام
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="radio" name="paymentMethod" id="creditCard">
                                    <label class="form-check-label" for="creditCard">
                                        بطاقة الائتمان
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Credit Card Section with Validation -->
                        <div id="creditCardInfo" class="mt-3" style="display: none;">
                            
                            <div class="row">
                                 <div class="mb-3">
                                    <label for="cardName" class="form-label">اسم صاحب البطاقة</label>
                                    <input type="text" id="cardName" class="form-control" placeholder="اسم صاحب البطاقة">
                                </div>
                                <div class="col-12 mb-3">
                                    <label for="cardNumber" class="form-label">رقم البطاقة </label>
                                    <input type="text" id="cardNumber" class="form-control" 
                                           placeholder="1234 5678 9012 3456" 
                                           pattern="\d{4}\s\d{4}\s\d{4}\s\d{4}"
                                           required>
                                    <div class="invalid-feedback">رقم بطاقة غير صحيح</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="expiryDate" class="form-label">تاريخ الانتهاء</label>
                                    <input type="text" id="expiryDate" class="form-control" placeholder="MM/YY">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="cvv" class="form-label">CVV</label>
                                    <input type="text" id="cvv" class="form-control" placeholder="123">
                                </div>
                            </div>

                            <!-- Rest of credit card fields with similar validation -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Summary Section -->
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-white">
                        <h2 class="fs-5 m-0">ملخص الطلب</h2>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">كود الخصم</label>
                            <div class="input-group">
                                <input type="text" class="form-control" placeholder="أدخل كود الخصم">
                                <button class="btn btn-outline-secondary" type="button" id="applyCoupon">تطبيق</button>
                            </div>
                        </div>
                        <ul class="list-unstyled mb-4">
                            <li class="d-flex justify-content-between mb-2">
                                <span>المجموع الفرعي</span>
                                <span id="subtotal">105.00 ر.س</span>
                            </li>
                            <li class="d-flex justify-content-between mb-2">
                                <span>رسوم الشحن</span>
                                <span id="shipping">25.00 ر.س</span>
                            </li>
                            <li class="d-flex justify-content-between mb-2">
                                <span>الخصم</span>
                                <span class="text-success" id="discount">-10.00 ر.س</span>
                            </li>
                            <li class="d-flex justify-content-between fw-bold">
                                <span>الإجمالي</span>
                                <span id="total">120.00 ر.س</span>
                            </li>
                        </ul>
                        <button class="btn btn-primary w-100" id="completeOrder">إتمام عملية الشراء</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @include('footer')

    <!-- Scripts -->
    
    <script src="{{ asset('js/checkout.js') }}"></script>
    <script src="{{ asset('js/header.js') }}"></script>
    
</body>
</html>