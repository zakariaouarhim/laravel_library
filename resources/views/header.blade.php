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
                <!-- Account Dropdown -->
                <div class="nav-item dropdown ">
                    <a href="#" class="nav-link ms-3  dropdown-toggle-no-caret" id="accountDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-circle text-white fs-4"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end p-3" aria-labelledby="accountDropdown" style="min-width: 250px;">
                        <!-- تسجيل and الدخول as Buttons -->
                        
                        <li class="mb-3">
                            <a href="{{ route('login2.page') }}" class="btn btn-primary w-100"> تسجيل الدخول </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <!-- تعقب طلباتي with Input Field -->
                        <li class="mb-3">
                            <label for="trackOrderInput" class="form-label">تعقب طلباتي</label>
                            <div class="input-group">
                                <input type="text" id="trackOrderInput" class="form-control" placeholder="رقم الطلب أو البريد الإلكتروني">
                                <button class="btn btn-outline-secondary" type="button">بحث</button>
                            </div>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <!-- Other Account Links -->
                        <li><a class="dropdown-item" href="$">حسابي</a></li>
                        <li><a class="dropdown-item" href="$}">الطلبات</a></li>
                        <li><a class="dropdown-item" href="$">طلبات الإسترجاع</a></li>
                        <li><a class="dropdown-item" href="$">قائمة الأمانيات</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- Bootstrap JS and Icons -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">