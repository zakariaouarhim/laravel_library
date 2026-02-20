<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سلّة التسوق</title>
    <!-- Stylesheets -->
    <link rel="stylesheet" href="{{ asset('css/headerstyle.css') }}">
    <link rel="stylesheet" href="{{ asset('css/by-category.css') }}">
    <link rel="stylesheet" href="{{ asset('css/cart.css') }}">
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

    <!-- Hero Banner -->
    <div class="category-hero">
        <div class="container">
            <div class="hero-content text-center">
                <h1 class="hero-title"><i class="fas fa-shopping-cart me-2"></i> سلّة التسوق</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb justify-content-center">
                        <li class="breadcrumb-item"><a href="{{ url('/') }}"><i class="fas fa-home home-icon"></i> الرئيسية</a></li>
                        <li class="breadcrumb-item active" aria-current="page">سلّة التسوق</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <div class="layout-cart">
        <div class="container py-4">
            @if(count($cart) > 0)
                <div class="row g-4">
                    <!-- Cart Items Section -->
                    <div class="col-lg-8">
                        <div class="cart-card">
                            <div class="cart-card-header">
                                <h2><i class="fas fa-shopping-bag me-2"></i> المنتجات</h2>
                                <span class="items-count" id="countcart">{{ count($cart) }} منتج</span>
                            </div>
                            <div class="cart-card-body">
                                <div class="cart-items" id="cartItemsList">
                                    @foreach($cart as $id => $item)
                                        <div class="cart-item" id="cartItem{{ $id }}" data-item-id="{{ $id }}" data-price="{{ $item['price'] }}">
                                            <div class="cart-item-image">
                                                <a href="{{ route('moredetail.page', ['id' => $id]) }}">
                                                    <img src="{{ asset($item['image']) }}" alt="{{ $item['title'] }}"
                                                        onerror="this.src='{{ asset('images/book-placeholder.png') }}'">
                                                </a>
                                            </div>
                                            <div class="cart-item-details">
                                                <h3 class="cart-item-title">
                                                    <a href="{{ route('moredetail.page', ['id' => $id]) }}">{{ $item['title'] }}</a>
                                                </h3>
                                                <div class="cart-item-price">
                                                    <span class="unit-price">{{ number_format($item['price'], 2) }} د.م</span>
                                                </div>
                                            </div>
                                            <div class="cart-item-actions">
                                                <div class="quantity-control">
                                                    <button type="button" class="qty-btn qty-decrease" data-id="{{ $id }}">
                                                        <i class="fas fa-minus"></i>
                                                    </button>
                                                    <input type="number" class="qty-input" id="qty_{{ $id }}"
                                                        value="{{ $item['quantity'] }}" min="1" readonly
                                                        data-id="{{ $id }}">
                                                    <button type="button" class="qty-btn qty-increase" data-id="{{ $id }}">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                </div>
                                                <div class="item-total">
                                                    <span id="itemTotal_{{ $id }}">{{ number_format($item['price'] * $item['quantity'], 2) }}</span> د.م
                                                </div>
                                                <button type="button" class="remove-btn" data-id="{{ $id }}" title="حذف المنتج">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                            <div class="cart-card-footer">
                                <a href="{{ route('index.page') }}" class="continue-shopping">
                                    <i class="fas fa-arrow-right me-2"></i> متابعة التسوق
                                </a>
                                <button type="button" class="clear-cart-btn" id="clearCartBtn">
                                    <i class="fas fa-trash me-2"></i> تفريغ السلة
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Order Summary Section -->
                    <div class="col-lg-4">
                        <div class="summary-card">
                            <div class="summary-card-header">
                                <h2><i class="fas fa-receipt me-2"></i> ملخص الطلب</h2>
                            </div>
                            <div class="summary-card-body">
                                <div class="summary-row">
                                    <span>المجموع الفرعي</span>
                                    <span id="subtotal">{{ number_format($subtotal, 2) }} د.م</span>
                                </div>
                                <div class="summary-row">
                                    <span>رسوم الشحن</span>
                                    <span id="shipping">{{ number_format($shipping, 2) }} د.م</span>
                                </div>
                                <div class="summary-row discount-row">
                                    <span>الخصم</span>
                                    <span id="discount">-{{ number_format($discount, 2) }} د.م</span>
                                </div>
                                <div class="summary-divider"></div>
                                <div class="summary-row total-row">
                                    <span>الإجمالي</span>
                                    <span id="total">{{ number_format($total, 2) }} د.م</span>
                                </div>
                            </div>
                            <div class="summary-card-footer">
                                <a href="{{ route('checkout.page') }}" class="checkout-btn" id="checkoutBtn">
                                    <i class="fas fa-credit-card me-2"></i> إتمام الشراء
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <!-- Empty Cart State -->
                <div class="empty-cart">
                    <div class="empty-cart-icon">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <h3>سلّة التسوق فارغة</h3>
                    <p>لم تقم بإضافة أي منتج إلى سلة التسوق بعد.</p>
                    <a href="{{ route('index.page') }}" class="btn btn-primary mt-3">
                        <i class="fas fa-book me-2"></i> تصفح الكتب
                    </a>
                </div>
            @endif
        </div>
    </div>

    @include('footer')

    <!-- Scripts -->
    <script>
        window.routes = {
            updateCartQuantity: "{{ route('cart.update-quantity') }}",
            removeFromCart: "/remove-from-cart",
            cartPage: "{{ route('cart.page') }}"
        };
    </script>
    <script src="{{ asset('js/header.js') }}"></script>
    <script src="{{ asset('js/scripts.js') }}"></script>
    <script src="{{ asset('js/cart.js') }}"></script>
</body>
</html>
