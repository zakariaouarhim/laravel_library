<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark" id="mainNavbar" >
    <div class="container">
        <!-- Logo -->
        <a class="navbar-brand" href="{{ route('index.page') }}">
            <img src="{{ asset('images/logo.svg') }}" alt="شعار المكتبة" class="d-inline-block align-text-top">
        </a>

        <!-- Toggler for mobile view -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" 
            aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar Content -->
        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav me-auto">
                <!-- Arabic Books Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="arabicBooksDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        الكتب العربية
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="arabicBooksDropdown">
                        <li><a class="dropdown-item" href="#">روايات</a></li>
                        <li><a class="dropdown-item" href="#">الكتب العلمية</a></li>
                        <li><a class="dropdown-item" href="#">التاريخ</a></li>
                        <li><a class="dropdown-item" href="#">كتب الأطفال</a></li>
                    </ul>
                </li>

                <!-- English Books Dropdown -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="englishBooksDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        الكتب الإنجليزية
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="englishBooksDropdown">
                        <li><a class="dropdown-item" href="#">Fiction</a></li>
                        <li><a class="dropdown-item" href="#">Science</a></li>
                        <li><a class="dropdown-item" href="#">History</a></li>
                        <li><a class="dropdown-item" href="#">Children's Books</a></li>
                    </ul>
                </li>

                <!-- Static Links -->
                <li class="nav-item">
                    <a class="nav-link" href="#">الأكثر مبيعًا</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">الإصدارات الحديثة</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">المجموعات</a>
                </li>
            </ul>

            <div class="d-flex align-items-center">
                <!-- Cart Icon -->
                <a href="javascript:void(0);" class="nav-link position-relative" onclick="showCartModal()">
                    <i class="bi bi-cart-fill text-white fs-4"></i>
                    <span id="cartCount" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        {{ session('cart') ? count(session('cart')) : 0 }}
                    </span>
                </a>

                <!-- Empty Cart Modal -->
                <div class="modal fade" id="emptyCartModal" tabindex="-1" aria-labelledby="emptyCartLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content text-center p-4">
                            <h5 class="modal-title" id="emptyCartLabel">سلّة التسوق فارغة</h5>
                            <button type="button" class="btn btn-secondary mt-3" data-bs-dismiss="modal">إغلاق</button>
                        </div>
                    </div>
                </div>

                <!-- Cart Details Modal -->
                <div class="modal fade" id="cartDetailsModal" tabindex="-1" aria-labelledby="cartDetailsLabel" aria-hidden="false">
                    <div class="modal-dialog">
                        <div class="modal-content p-3">
                            <div class="modal-header">
                                <h5 class="modal-title" id="cartDetailsLabel">سلّة التسوق</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                            </div>
                            <div class="modal-body">
                                <form id="checkoutForm" action="{{ route('checkout.store') }}" method="POST">
                                    @csrf
                                    <div id="cartItemsContainer">
                                        <!-- Cart items will be inserted dynamically -->
                                    </div>
                                    <input type="hidden" name="cart_data" id="cartDataInput">
                                </form>
                            </div>
                            <div class="modal-footer d-flex justify-content-between">
                                <button class="btn btn-primary" id="checkoutButton" onclick="submitCheckoutForm()">إتمام الشراء ✔️</button>
                                <button class="btn btn-outline-warning">سلّة التسوق</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Account Dropdown -->
                <div class="nav-item dropdown ms-3">
                    @if(session('is_logged_in'))
                        <!-- User is logged in -->
                        <a href="#" class="nav-link dropdown-toggle d-flex align-items-center" id="accountDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle text-white fs-4 me-2"></i>
                            <span class="text-white">مرحباً {{ session('user_name') }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end p-3" aria-labelledby="accountDropdown" style="min-width: 250px;">
                            <li class="mb-3">
                                <div class="text-center">
                                    <strong>{{ session('user_name') }}</strong>
                                    <br>
                                    <small class="text-muted">{{ session('user_email') }}</small>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li class="mb-3">
                                <label for="trackOrderInput" class="form-label">تعقب طلباتي</label>
                                <div class="input-group">
                                    <input type="text" id="trackOrderInput" class="form-control" placeholder="رقم الطلب أو البريد الإلكتروني">
                                    <button class="btn btn-outline-secondary" type="button">بحث</button>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('account.page') }}">حسابي</a></li>
                            <li><a class="dropdown-item" href="#">الطلبات</a></li>
                            <li><a class="dropdown-item" href="#">طلبات الإسترجاع</a></li>
                            <li><a class="dropdown-item" href="#">قائمة الأمنيات</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="{{ route('logout') }}">
                                    <i class="bi bi-box-arrow-right me-2"></i>تسجيل الخروج
                                </a>
                            </li>
                        </ul>
                    @else
                        <!-- User is not logged in -->
                        <a href="#" class="nav-link dropdown-toggle" id="accountDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle text-white fs-4" title="حسابي"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end p-3" aria-labelledby="accountDropdown" style="min-width: 250px;">
                            <li class="mb-3">
                                <a href="{{ route('login2.page') }}" class="btn btn-primary w-100"> تسجيل الدخول </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li class="mb-3">
                                <label for="trackOrderInput" class="form-label">تعقب طلباتي</label>
                                <div class="input-group">
                                    <input type="text" id="trackOrderInput" class="form-control" placeholder="رقم الطلب أو البريد الإلكتروني">
                                    <button class="btn btn-outline-secondary" type="button">بحث</button>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- Bootstrap JS and Icons -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">