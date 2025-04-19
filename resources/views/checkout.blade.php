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
        <form id="checkoutForm" action=" route('checkout.submit') }}" method="POST">
            @csrf
            <div class="row g-4">
                <div class="col-lg-8">
                    <!-- Shopping Cart Section -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h2 class="fs-5 m-0">سلة التسوق</h2>
                           @php $cart = session('checkout_cart');
                            if ($cart=="") {
                                 $cart = $cart ?? [];
                            }
                            @endphp
                            @php
                            // Calculate subtotal
                            $subtotal = 0;
                            foreach($cart as $item) {
                                $subtotal += $item['price'] * $item['quantity'];
                            }

                            // Fixed shipping cost
                            $shipping = 25.00;

                            // Initialize discount (you can integrate coupon logic later)
                            $discount = 0.00;

                            // Calculate total
                            $total = $subtotal + $shipping - $discount;
                            @endphp
                            <span class="text-muted">{{ count($cart) }} منتجات</span>
                        </div>
                        <div class="card-body">
                            
                            @if(count($cart) > 0)
                            <h3 class="mb-4">مراجعة الطلب</h3>
                            <div class="cart-items">
                                @foreach($cart as $id => $item)
                                    <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                                        <img src="{{ asset($item['image']) }}" 
                                             class="img-fluid rounded me-3" 
                                             style="width: 80px; height: 110px; object-fit: cover;"
                                             onerror="this.src='{{ asset('images/placeholder-book.jpg') }}'">
                                        <div class="flex-grow-1">
                                            <h3 class="fs-6 mb-1">{{ $item['title'] }}</h3>
                                            <div class="d-flex align-items-center">
                                                <label class="me-2">الكمية:</label>
                                                <input type="number" 
                                                       name="quantity[{{ $id }}]" 
                                                       class="form-control form-control-sm quantity-input" 
                                                       style="width: 70px;" 
                                                       value="{{ $item['quantity'] }}" 
                                                       min="1"
                                                       data-price="{{ $item['price'] }}">
                                            </div>
                                        </div>
                                        <div>
                                            <span class="fw-bold text-primary">{{ number_format($item['price'] * $item['quantity'], 2) }} ر.س</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @else
                            <div class="text-center py-4">
                                <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                <p class="text-muted">سلة التسوق فارغة</p>
                                <a href=" {{ route('index.page') }}" class="btn btn-primary">تصفح الكتب</a>
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
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">الاسم الأول</label>
                                    <input type="text" name="first_name" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">الاسم الأخير</label>
                                    <input type="text" name="last_name" class="form-control" required>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">البريد الإلكتروني</label>
                                    <input type="email" name="email" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">رقم الهاتف</label>
                                    <input type="tel" name="phone" class="form-control" pattern="[0-9]{10}" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">العنوان التفصيلي</label>
                                <textarea name="address" class="form-control" rows="3" required></textarea>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">المدينة</label>
                                    <select class="form-select" name="city" required>
                                        <option value="">اختر المدينة</option>
                                        <option value="الرياض">الرياض</option>
                                        <option value="جدة">جدة</option>
                                        <option value="مكة المكرمة">مكة المكرمة</option>
                                        <option value="المدينة المنورة">المدينة المنورة</option>
                                        <option value="الدمام">الدمام</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">الرمز البريدي</label>
                                    <input type="text" name="zip_code" class="form-control" pattern="[0-9]{5}" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Method Section -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h2 class="fs-5 m-0">طريقة الدفع</h2>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="payment-method-card">
                                        <input type="radio" name="payment_method" id="cashOnDelivery" value="cod" checked>
                                        <label for="cashOnDelivery" class="form-check-label">
                                            <i class="fas fa-money-bill-wave"></i>
                                            <span>الدفع عند الاستلام</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="payment-method-card">
                                        <input type="radio" name="payment_method" id="creditCard" value="credit_card">
                                        <label for="creditCard" class="form-check-label">
                                            <i class="fas fa-credit-card"></i>
                                            <span>بطاقة ائتمان</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- Credit Card Section -->
                            <div id="creditCardInfo" class="mt-4" style="display: none;">
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <label for="cardNumber" class="form-label">رقم البطاقة</label>
                                        <div class="input-group">
                                            <input type="text" id="cardNumber" name="card_number" 
                                                   class="form-control" 
                                                   placeholder="1234 5678 9012 3456"
                                                   data-inputmask="'mask': '9999 9999 9999 9999'">
                                            <span class="input-group-text"><i class="fas fa-credit-card"></i></span>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="expiryDate" class="form-label">تاريخ الانتهاء</label>
                                        <input type="text" id="expiryDate" name="expiry_date" 
                                               class="form-control" 
                                               placeholder="MM/YY"
                                               data-inputmask="'mask': '99/99'">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="cvv" class="form-label">CVV</label>
                                        <input type="text" id="cvv" name="cvv" 
                                               class="form-control" 
                                               placeholder="123"
                                               data-inputmask="'mask': '999'">
                                    </div>
                                </div>
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
                                    <input type="text" id="couponCode" class="form-control" placeholder="أدخل كود الخصم">
                                    <button type="button" class="btn btn-outline-primary" id="applyCoupon">تطبيق</button>
                                </div>
                                <div id="couponMessage" class="mt-2 small"></div>
                            </div>
                            
                                            <div class="order-summary">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>المجموع الفرعي:</span>
                                    <span id="subtotal">{{ number_format($subtotal, 2) }} ر.س</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>رسوم الشحن:</span>
                                    <span id="shipping">{{ number_format($shipping, 2) }} ر.س</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2 text-success">
                                    <span>الخصم:</span>
                                    <span id="discount">-{{ number_format($discount, 2) }} ر.س</span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between fw-bold">
                                    <span>الإجمالي:</span>
                                    <span id="total">{{ number_format($total, 2) }} ر.س</span>
                                </div>
                            </div>

                            
                            <button type="submit" class="btn btn-primary w-100 mt-4" id="completeOrder">
                                <span class="submit-text">إتمام عملية الشراء</span>
                                <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                            </button>
                            
                            <div class="form-check mt-3">
                                <input class="form-check-input" type="checkbox" id="termsCheck" required>
                                <label class="form-check-label small" for="termsCheck">
                                    أوافق على <a href=" route('terms') }}">الشروط والأحكام</a>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    @include('footer')

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/inputmask/5.0.6/jquery.inputmask.min.js"></script>
    <script src="{{ asset('js/checkout.js') }}"></script>
    <script src="{{ asset('js/header.js') }}"></script>
    
</body>
</html>