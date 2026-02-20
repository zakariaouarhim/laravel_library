<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

    <!-- Hero Banner -->
    <div class="category-hero">
        <div class="container">
            <div class="hero-content text-center">
                <h1 class="hero-title"><i class="fas fa-credit-card me-2"></i> إتمام الشراء</h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb justify-content-center">
                        <li class="breadcrumb-item"><a href="{{ url('/') }}"><i class="fas fa-home home-icon"></i> الرئيسية</a></li>
                        <li class="breadcrumb-item"><a href="{{ route('cart.page') }}">سلّة التسوق</a></li>
                        <li class="breadcrumb-item active" aria-current="page">إتمام الشراء</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

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
                                        <span id="shipping">{{ number_format($shipping, 2) }} د.م</span>
                                    </div>
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
                                        أوافق على <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">الشروط والأحكام</a>
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

                    <p class="text-muted small">آخر تحديث: {{ date('d/m/Y') }}</p>

                    <p>مرحباً بك في متجر <strong>أسير الكتب</strong>. باستخدامك لهذا الموقع وإتمامك لعملية الشراء، فإنك توافق على الشروط والأحكام التالية. يُرجى قراءتها بعناية قبل تقديم طلبك.</p>

                    <hr>

                    <h6 class="fw-bold mt-4">١. الطلبات والدفع</h6>
                    <ul>
                        <li>جميع الأسعار المعروضة بالدرهم المغربي (د.م) وتشمل الضرائب المقررة.</li>
                        <li>يُعدّ طلبك مؤكداً فور استلامك بريد التأكيد الإلكتروني.</li>
                        <li>نحتفظ بالحق في إلغاء أي طلب في حال عدم توفر المنتج أو وجود خطأ في السعر، مع إعادة المبلغ كاملاً.</li>
                        <li>طرق الدفع المتاحة: الدفع عند الاستلام والتحويل البنكي.</li>
                        <li>في حال اخترت التحويل البنكي، يجب إرسال إثبات التحويل خلال 48 ساعة من تقديم الطلب، وإلا سيُلغى الطلب تلقائياً.</li>
                    </ul>

                    <h6 class="fw-bold mt-4">٢. الشحن والتوصيل</h6>
                    <ul>
                        <li>يُشحن الطلب خلال 1 إلى 3 أيام عمل من تأكيد الدفع.</li>
                        <li>تتراوح مدة التوصيل بين 3 و7 أيام عمل حسب المنطقة الجغرافية.</li>
                        <li>رسوم الشحن ثابتة وتُحسب تلقائياً عند إتمام الطلب.</li>
                        <li>لا نتحمل المسؤولية عن أي تأخير ناجم عن ظروف خارجة عن إرادتنا (كوارث طبيعية، إضرابات، إلخ).</li>
                    </ul>

                    <h6 class="fw-bold mt-4">٣. الإرجاع والاستبدال</h6>
                    <ul>
                        <li>يحق لك طلب الإرجاع خلال <strong>7 أيام</strong> من تاريخ الاستلام، شريطة أن يكون المنتج بحالته الأصلية غير مستخدم.</li>
                        <li>لا تُقبل إعادة الكتب التي فُتحت أغلفتها المحكمة أو الكتب الرقمية.</li>
                        <li>في حال وجود عيب مصنعي أو خطأ في الطلب، نتحمل تكاليف الشحن العكسي كاملاً.</li>
                        <li>يُعاد المبلغ إلى نفس وسيلة الدفع خلال 5 إلى 10 أيام عمل.</li>
                    </ul>

                    <h6 class="fw-bold mt-4">٤. الخصوصية وحماية البيانات</h6>
                    <ul>
                        <li>نلتزم بحماية بياناتك الشخصية ولا نبيعها أو نشاركها مع أطراف ثالثة لأغراض تجارية.</li>
                        <li>تُستخدم بياناتك حصراً لمعالجة الطلبات وتحسين تجربتك في المتجر.</li>
                        <li>يحق لك طلب حذف بياناتك في أي وقت عبر التواصل معنا.</li>
                    </ul>

                    <h6 class="fw-bold mt-4">٥. الملكية الفكرية</h6>
                    <ul>
                        <li>جميع محتويات الموقع (نصوص، صور، شعارات) هي ملك حصري لمتجر <strong>أسير الكتب</strong> ومحمية بموجب قوانين حقوق الملكية الفكرية.</li>
                        <li>يُحظر نسخ أي محتوى أو إعادة استخدامه دون إذن كتابي مسبق.</li>
                    </ul>

                    <h6 class="fw-bold mt-4">٦. تعديل الشروط</h6>
                    <p>نحتفظ بالحق في تعديل هذه الشروط في أي وقت. سيتم إشعارك بأي تغييرات جوهرية عبر البريد الإلكتروني المسجل لديك. استمرارك في استخدام الموقع بعد نشر التعديلات يُعدّ قبولاً صريحاً لها.</p>

                    <h6 class="fw-bold mt-4">٧. القانون المطبق</h6>
                    <p>تخضع هذه الشروط لأحكام القانون المغربي المتعلق بالتجارة الإلكترونية، وتختص المحاكم المغربية بالفصل في أي نزاع ينشأ عنها.</p>

                    <hr>
                    <p class="text-muted small mb-0">للتواصل معنا بشأن أي استفسار حول هذه الشروط، يُرجى مراسلتنا عبر صفحة <a href="{{ url('/contact') }}">التواصل معنا</a>.</p>

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
    </script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="{{ asset('js/checkout.js') }}"></script>
    <script src="{{ asset('js/header.js') }}"></script>
</body>
</html>
