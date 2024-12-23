<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <!-- Logo -->
        <a class="navbar-brand" href="{{ route('index.page') }}">
            <img src="{{ asset('images/logo.svg') }}" alt="شعار المكتبة" class="d-inline-block align-text-top">
            <span class="me-2"></span>
        </a>

        <!-- Toggler for mobile view -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar Content -->
        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav me-auto">
                <!-- Dropdown for Arabic Books -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="arabicBooksDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        الكتب العربية
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="arabicBooksDropdown">
                        <li><a class="dropdown-item" href="#fiction-arabic">روايات</a></li>
                        <li><a class="dropdown-item" href="#science-arabic">الكتب العلمية</a></li>
                        <li><a class="dropdown-item" href="#history-arabic">التاريخ</a></li>
                        <li><a class="dropdown-item" href="#children-arabic">كتب الأطفال</a></li>
                    </ul>
                </li>

                <!-- Dropdown for English Books -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="englishBooksDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        الكتب الإنجليزية
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="englishBooksDropdown">
                        <li><a class="dropdown-item" href="#fiction-english">Fiction</a></li>
                        <li><a class="dropdown-item" href="#science-english">Science</a></li>
                        <li><a class="dropdown-item" href="#history-english">History</a></li>
                        <li><a class="dropdown-item" href="#children-english">Children's Books</a></li>
                    </ul>
                </li>

                <!-- Static Links -->
                <li class="nav-item">
                    <a class="nav-link" href="#bookCarousel2">الأكثر مبيعًا</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#bookCarousel">الإصدارات الحديثة</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#collections">المجموعات</a>
                </li>
            </ul>
            
             <div class="d-flex">
                <!-- Cart Icon -->
                <a href="{{ route('checkout.page') }}" class="nav-link position-relative">
                    <i class="bi bi-cart-fill text-white fs-4"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        0 <!-- Example cart count -->
                        <span class="visually-hidden">عدد العناصر في السلة</span>
                    </span>
                </a>
                <!-- Account Icon -->
                <a href="#" class="nav-link ms-3" data-bs-toggle="modal" data-bs-target="#accountModal">
                    <i class="bi bi-person-circle text-white fs-4"></i>
                </a>

            </div>
        </div>
    </div>
</nav>
<!-- Account Modal -->
<div class="modal fade" id="accountModal" tabindex="-1" aria-labelledby="accountModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="accountModalLabel">تسجيل الدخول أو إنشاء حساب</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Tabs for Login and Register -->
                <ul class="nav nav-tabs" id="accountTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="login-tab" data-bs-toggle="tab" data-bs-target="#login" type="button" role="tab" aria-controls="login" aria-selected="true">تسجيل الدخول</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="register-tab" data-bs-toggle="tab" data-bs-target="#register" type="button" role="tab" aria-controls="register" aria-selected="false">إنشاء حساب</button>
                    </li>
                </ul>
                <div class="tab-content mt-3">
                    <!-- Login Form -->
                    <div class="tab-pane fade show active" id="login" role="tabpanel" aria-labelledby="login-tab">
                        <form method="POST" action="{{ route('login') }}">
                            @csrf
                            <div class="mb-3">
                                <label for="emailLogin" class="form-label">البريد الإلكتروني</label>
                                <input type="email" class="form-control" id="emailLogin" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="passwordLogin" class="form-label">كلمة المرور</label>
                                <input type="password" class="form-control" id="passwordLogin" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">تسجيل الدخول</button>
                        </form>
                    </div>
                    <!-- Register Form -->
                    <div class="tab-pane fade" id="register" role="tabpanel" aria-labelledby="register-tab">
                        <form method="POST" action="{{ route('register') }}">
                            @csrf
                            <div class="mb-3">
                                <label for="nameRegister" class="form-label">الاسم الكامل</label>
                                <input type="text" class="form-control" id="nameRegister" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label for="emailRegister" class="form-label">البريد الإلكتروني</label>
                                <input type="email" class="form-control" id="emailRegister" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="passwordRegister" class="form-label">كلمة المرور</label>
                                <input type="password" class="form-control" id="passwordRegister" name="password" required>
                            </div>
                            <div class="mb-3">
                                <label for="passwordRegister" class="form-label">تأكيد كلمة المرور </label>
                                <input type="password" class="form-control" id="passwordRegister" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-success w-100">إنشاء حساب</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- Include Bootstrap Icons -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">