<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>إتمام الشراء</title>
    <!-- Stylesheets -->
    <link rel="stylesheet" href="{{ asset('css/headerstyle.css') }}">
    <link rel="stylesheet" href="{{ asset('css/by-category.css') }}">
    <link rel="stylesheet" href="{{ asset('css/checkout.css') }}">
    <link rel="stylesheet" href="{{ asset('css/footer.css') }}">
    <!-- Favicon -->
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
    <!-- Bootstrap RTL CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts - Tajawal -->
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@300;400;500;700&display=swap" rel="stylesheet">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth
        <meta name="auth-user" content="true">
    @endauth
</head>
<body>
    @include('header')

    @include('partials.page-hero', [
        'title'       => 'إتمام الشراء',
        'icon'        => 'fas fa-credit-card',
        'centered'    => true,
        'breadcrumbs' => [
            ['label' => 'الرئيسية',   'url' => url('/')],
            ['label' => 'سلّة التسوق', 'url' => route('cart.page')],
            ['label' => 'إتمام الشراء'],
        ],
    ])

    <div class="layout-checkout">
        <!-- Toast -->
        <div class="toast-container position-relative w-100 d-flex justify-content-start px-3">
            <div id="cartToast" class="toast align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body"></div>
                    <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="إغلاق"></button>
                </div>
            </div>
        </div>

        <div class="container checkout-section py-4">
            <!-- Alerts -->
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
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <form id="checkoutForm" action="{{ route('checkout.submit') }}" method="POST">
                @csrf
                <input type="hidden" name="checkout_token" value="{{ $checkoutToken }}">
                <div class="row g-4">
                    <div class="col-lg-8">
                        <!-- Shopping Cart Section -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                <h2 class="fs-5 m-0"><i class="fas fa-shopping-bag me-2"></i> سلة التسوق</h2>
                                <span class="text-muted" id="countcart">{{ count($cart) }} منتج</span>
                            </div>
                            <div class="card-body" id="cartContent">
                                @if(count($cart) > 0)
                                <div class="cart-items">
                                    @foreach($cart as $id => $item)
                                        <div class="d-flex align-items-center mb-3 pb-3 border-bottom" id="element{{ $id }}" data-item-id="{{ $id }}">
                                            <img src="{{ asset($item['image']) }}"
                                                class="img-fluid rounded me-3"
                                                style="width: 80px; height: 110px; object-fit: cover;"
                                                onerror="this.src='{{ asset('images/book-placeholder.png') }}'">
                                            <div class="flex-grow-1">
                                                <h3 class="fs-6 mb-1">{{ $item['title'] }}</h3>
                                                <div class="d-flex align-items-center">
                                                    <span class="fw-bold text-primary me-3">{{ number_format($item['price'] * $item['quantity'], 2) }} د.م</span>
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
                                                    <input type="hidden" name="cart_quantities[{{ $id }}]" value="{{ $item['quantity'] }}" class="hidden-quantity">
                                                    <button type="button" class="quantity-btn quantity-increase">+</button>
                                                </div>
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
                                <h2 class="fs-5 m-0"><i class="fas fa-truck me-2"></i> معلومات الشحن</h2>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">الاسم الكامل <span class="text-danger">*</span></label>
                                        <input type="text" name="full_name" class="form-control @error('full_name') is-invalid @enderror"
                                            value="{{ old('full_name', Auth::user()->name ?? '') }}" placeholder="الاسم الأول والأخير" required>
                                        @error('full_name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">رقم الهاتف <span class="text-danger">*</span></label>
                                        <input type="tel" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                            pattern="[0-9]{10}" value="{{ old('phone', $lastPhone ?? '') }}" placeholder="0600000000" required>
                                        @error('phone')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">المدينة <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('city') is-invalid @enderror" name="city" id="city"
                                            value="{{ old('city') }}" placeholder="اكتب مدينتك" required>
                                        @error('city')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">البريد الإلكتروني</label>
                                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                            value="{{ old('email', Auth::user()->email ?? '') }}" placeholder="example@email.com">
                                        @error('email')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">العنوان التفصيلي <span class="text-danger">*</span></label>
                                    <textarea name="address" class="form-control @error('address') is-invalid @enderror"
                                            rows="2" placeholder="الحي، الشارع، رقم المنزل..." required>{{ old('address') }}</textarea>
                                    @error('address')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">ملاحظات التوصيل</label>
                                    <textarea name="notes" class="form-control" rows="2"
                                        placeholder="مثال: الاتصال قبل التوصيل، الطابق الثالث...">{{ old('notes') }}</textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Method Section -->
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-white">
                                <h2 class="fs-5 m-0"><i class="fas fa-wallet me-2"></i> طريقة الدفع</h2>
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
                                            <input type="radio" name="payment_method" id="bankTransfer" value="bank_transfer"
                                                {{ old('payment_method') == 'bank_transfer' ? 'checked' : '' }}>
                                            <label for="bankTransfer" class="form-check-label">
                                                <i class="fas fa-university"></i>
                                                <span>تحويل بنكي</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                @error('payment_method')
                                    <div class="text-danger mt-2">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Order Summary Section (Sticky) -->
                    <div class="col-lg-4">
                        <div class="card shadow-sm checkout-summary-sticky">
                            <div class="card-header bg-white">
                                <h2 class="fs-5 m-0"><i class="fas fa-receipt me-2"></i> ملخص الطلب</h2>
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
                                        <span id="subtotal">{{ number_format($subtotal, 2) }} د.م</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>رسوم الشحن:</span>
                                        <span id="shipping">
                                            @if($shipping == 0 && $freeThreshold > 0)
                                                <span style="color: #28a745; font-weight: 600;">مجاني</span>
                                            @else
                                                {{ number_format($shipping, 2) }} د.م
                                            @endif
                                        </span>
                                    </div>
                                    @if($shipping == 0 && $freeThreshold > 0)
                                        <div style="background: linear-gradient(135deg, #d4edda, #c3e6cb); color: #155724; padding: 8px 12px; border-radius: 8px; text-align: center; font-size: 0.85rem; font-weight: 600; margin-bottom: 8px;">
                                            <i class="fas fa-truck me-1"></i> شحن مجاني!
                                        </div>
                                    @elseif($freeThreshold > 0 && $subtotal < $freeThreshold)
                                        <div style="background: #fff3cd; color: #856404; padding: 8px 12px; border-radius: 8px; text-align: center; font-size: 0.82rem; margin-bottom: 8px;">
                                            <i class="fas fa-info-circle me-1"></i> أضف {{ number_format($freeThreshold - $subtotal, 2) }} د.م للحصول على شحن مجاني
                                        </div>
                                    @endif
                                    <div class="d-flex justify-content-between mb-2 text-success">
                                        <span>الخصم:</span>
                                        <span id="discount">-{{ number_format($discount, 2) }} د.م</span>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between fw-bold">
                                        <span>الإجمالي:</span>
                                        <span id="total">{{ number_format($total, 2) }} د.م</span>
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
                                        أوافق على <a href="{{ route('terms.page') }}" target="_blank">الشروط والأحكام</a> (<a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">عرض سريع</a>)
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

    <!-- Terms & Conditions Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="termsModalLabel">
                        <i class="fas fa-file-contract me-2 text-primary"></i>الشروط والأحكام
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <div class="modal-body" style="font-family:'Tajawal',sans-serif;line-height:1.9;color:#333;">
                    @include('partials.terms-content')
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal"
                            onclick="document.getElementById('termsCheck').checked = true;">
                        <i class="fas fa-check me-1"></i> أوافق على الشروط والأحكام
                    </button>
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إغلاق</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        window.routes = {
            updateCartQuantity: "{{ route('cart.update-quantity') }}"
        };
        window.shippingConfig = {
            cost: {{ $shipping }},
            freeThreshold: {{ $freeThreshold }}
        };
    </script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="{{ asset('js/checkout.js') }}"></script>
    <script src="{{ asset('js/header.js') }}"></script>
</body>
</html>
