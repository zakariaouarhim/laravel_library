<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إتمام الشراء</title>
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="{{ asset('css/headerstyle.css') }}">
    <link rel="stylesheet" href="{{ asset('css/checkout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    
    <!-- Favicon -->
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">

    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    @include('header')
    <!-- Cart Toast Component -->
    <div class="layout-checkout">
    <div class="toast-container position-relative w-100 d-flex justify-content-start px-3">
        <div id="cartToast" class="toast align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
            <!-- Toast message will be inserted here -->
            </div>
            <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="إغلاق"></button>
        </div>
        </div>
    </div>
        <div class="container checkout-section py-5">
            <!-- Main Checkout Form -->
            <!-- Add this at the top of your form to display errors -->
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            <form id="checkoutForm" action="{{ route('checkout.submit') }}" method="POST">
                @csrf
                <div class="row g-4">
                    <div class="col-lg-8">
                        <!-- Shopping Cart Section -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h2 class="fs-5 m-0">سلة التسوق</h2>
                                <span class="text-muted" id="countcart">{{ count($cart) }} منتجات</span>
                            </div>
                            <div class="card-body" id="cartContent">
                                @if(count($cart) > 0)
                                <h3 class="mb-4">مراجعة الطلب</h3>
                                <div class="cart-items">
                                    @foreach($cart as $id => $item)
                                        <div class="d-flex align-items-center mb-3 pb-3 border-bottom" id="element{{ $id }}" data-item-id="{{ $id }}">
                                            <img src="{{ asset($item['image']) }}" 
                                                class="img-fluid rounded me-3" 
                                                style="width: 80px; height: 110px; object-fit: cover;"
                                                onerror="this.src='{{ asset('images/placeholder-book.jpg') }}'">
                                            <div class="flex-grow-1">
                                                <h3 class="fs-6 mb-1">{{ $item['title'] }}</h3>
                                                <div class="d-flex align-items-center">
                                                    <span class="fw-bold text-primary me-3">{{ number_format($item['price'] * $item['quantity'], 2) }} ر.س</span>
                                                </div>
                                            </div>
                                            
                                            <div class="d-flex align-items-center">
                                                <label class="me-2">الكمية:</label>
                                                <div class="quantity-control-group">
                                                    <button type="button" class="quantity-btn quantity-decrease">-</button>
                                                    <input type="number" 
                                                        id="quantity_{{ $id }}"
                                                        name="display_quantity[{{ $id }}]" 
                                                        class="quantity-input" 
                                                        value="{{ $item['quantity'] }}" 
                                                        min="1"
                                                        data-price="{{ $item['price'] }}"
                                                        readonly>
                                                    <!-- Hidden input for form submission -->
                                                    <input type="hidden" name="cart_quantities[{{ $id }}]" value="{{ $item['quantity'] }}" class="hidden-quantity">
                                                    <button type="button" class="quantity-btn quantity-increase">+</button>
                                                </div>
                                                <!-- Edit and Save Buttons -->
                                                <button type="button" class="btn btn-sm btn-outline-secondary ms-2 edit-btn">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                    
                                                <button type="button" class="delete-item-btn" title="حذف المنتج" onclick="removeFromCart2({{ $id }})">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                @else
                                <div class="text-center py-4">
                                    <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">سلة التسوق فارغة</p>
                                    <a href="{{ route('index.page') }}" class="btn btn-primary">تصفح الكتب</a>
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
                                        <label class="form-label">الاسم الأول <span class="text-danger">*</span></label>
                                        <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror" 
                                            value="{{ old('first_name') }}" required>
                                        @error('first_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">الاسم الأخير <span class="text-danger">*</span></label>
                                        <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror" 
                                            value="{{ old('last_name') }}" required>
                                        @error('last_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">البريد الإلكتروني <span class="text-danger">*</span></label>
                                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" 
                                            value="{{ old('email') }}" required>
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">رقم الهاتف <span class="text-danger">*</span></label>
                                        <input type="tel" name="phone" class="form-control @error('phone') is-invalid @enderror" 
                                            pattern="[0-9]{10}" value="{{ old('phone') }}" required>
                                        @error('phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">العنوان التفصيلي <span class="text-danger">*</span></label>
                                    <textarea name="address" class="form-control @error('address') is-invalid @enderror" 
                                            rows="3" required>{{ old('address') }}</textarea>
                                    @error('address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">المدينة <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="city" id="city" placeholder="اكتب مدينتك" required>
                                        @error('city')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">الرمز البريدي <span class="text-danger">*</span></label>
                                        <input type="text" name="zip_code" class="form-control @error('zip_code') is-invalid @enderror" 
                                            pattern="[0-9]{5}" value="{{ old('zip_code') }}" required>
                                        @error('zip_code')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
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
                                            <input type="radio" name="payment_method" id="cashOnDelivery" value="cod" 
                                                {{ old('payment_method', 'cod') == 'cod' ? 'checked' : '' }}>
                                            <label for="cashOnDelivery" class="form-check-label">
                                                <i class="fas fa-money-bill-wave"></i>
                                                <span>الدفع عند الاستلام</span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="payment-method-card">
                                            <input type="radio" name="payment_method" id="creditCard" value="credit_card"
                                                {{ old('payment_method') == 'credit_card' ? 'checked' : '' }}>
                                            <label for="creditCard" class="form-check-label">
                                                <i class="fas fa-credit-card"></i>
                                                <span>بطاقة ائتمان</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                @error('payment_method')
                                    <div class="text-danger mt-2">{{ $message }}</div>
                                @enderror

                                <!-- Credit Card Section -->
                                <div id="creditCardInfo" class="mt-4" style="{{ old('payment_method') == 'credit_card' ? 'display: block;' : 'display: none;' }}">
                                    <div class="row">
                                        <div class="col-12 mb-3">
                                            <label for="cardNumber" class="form-label">رقم البطاقة</label>
                                            <div class="input-group">
                                                <input type="text" id="cardNumber" name="card_number" 
                                                    class="form-control @error('card_number') is-invalid @enderror" 
                                                    placeholder="1234 5678 9012 3456"
                                                    value="{{ old('card_number') }}"
                                                    data-inputmask="'mask': '9999 9999 9999 9999'">
                                                <span class="input-group-text"><i class="fas fa-credit-card"></i></span>
                                                @error('card_number')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="expiryDate" class="form-label">تاريخ الانتهاء</label>
                                            <input type="text" id="expiryDate" name="expiry_date" 
                                                class="form-control @error('expiry_date') is-invalid @enderror" 
                                                placeholder="MM/YY"
                                                value="{{ old('expiry_date') }}"
                                                data-inputmask="'mask': '99/99'">
                                            @error('expiry_date')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label for="cvv" class="form-label">CVV</label>
                                            <input type="text" id="cvv" name="cvv" 
                                                class="form-control @error('cvv') is-invalid @enderror" 
                                                placeholder="123"
                                                value="{{ old('cvv') }}"
                                                data-inputmask="'mask': '999'">
                                            @error('cvv')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
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
                                    <input class="form-check-input @error('terms') is-invalid @enderror" type="checkbox" 
                                        id="termsCheck" name="terms" required {{ old('terms') ? 'checked' : '' }}>
                                    <label class="form-check-label small" for="termsCheck">
                                        أوافق على <a href="#">الشروط والأحكام</a>
                                    </label>
                                    @error('terms')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>


        </div>
    </div>
    @include('footer')

    <!-- Scripts -->
    <script>
        // Define global routes for JavaScript
        window.routes = {
            updateCartQuantity: "{{ route('cart.update-quantity') }}"
        };
    </script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/inputmask/5.0.6/jquery.inputmask.min.js"></script>
    <script src="{{ asset('js/checkout.js') }}"></script>
    <script src="{{ asset('js/header.js') }}"></script>
    
</body>
</html>