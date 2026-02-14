<div class="layout-header">
<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark" id="mainNavbar">
    <div class="container">
        <!-- Logo -->
        <a class="navbar-brand" href="{{ route('index.page') }}">
            <img src="{{ asset('images/logo.svg') }}" alt="شعار المكتبة" class="d-inline-block align-text-top">
        </a>

        <!-- Search Bar -->
        <form action="{{ route('search.results') }}" method="GET" class="header-search-form position-relative">
            <div class="input-group">
                <input
                    type="search"
                    name="query"
                    id="searchInputHeader"
                    class="form-control"
                    placeholder="ابحث عن كتاب، مؤلف، ناشر..."
                    oninput="searchBooksAutocomplete(this.value, 'searchResultsHeader')"
                    autocomplete="off"
                    required>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-search"></i>
                </button>
            </div>
            <div id="searchResultsHeader" class="search-results-header"></div>
        </form>

        <!-- Toggler for mobile view -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent"
            aria-controls="navbarContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar Content -->
        <div class="collapse navbar-collapse" id="navbarContent">
            <!-- Empty spacer to push everything to the left -->
            <div class="me-auto"></div>

            <div class="d-flex align-items-center">
                <!-- Static Links -->
                <ul class="navbar-nav flex-row me-3">
                    <li class="nav-item">
                        <a class="nav-link" href="#popular-books">الأكثر مبيعًا</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#all-books">الإصدارات الحديثة</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('accessories.index') }}">الإكسسوارات</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('authors.index') }}">المؤلفون</a>
                    </li>
                </ul>

                <!-- Cart Icon -->
                <a href="javascript:void(0);" class="nav-link position-relative" onclick="showCartModal()">
                    <i class="bi bi-cart-fill text-white fs-4"></i>
                    <span id="cartCount" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        {{ session('cart') ? count(session('cart')) : 0 }}
                    </span>
                </a>

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
                                <form action="{{ route('trackmyorder') }}" method="POST">
                                    @csrf
                                    <div class="input-group">
                                        <input 
                                            type="text" 
                                            name="trackOrderInput" 
                                            class="form-control" 
                                            placeholder="رقم التتبع أو البريد الإلكتروني"
                                            required
                                        >
                                        <button class="btn btn-outline-secondary" type="submit">
                                            بحث
                                        </button>
                                    </div>
                                </form>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="{{ route('account.page') }}">حسابي</a></li>
                            <li><a class="dropdown-item" href="{{ route('my-orders.index') }}">الطلبات</a></li>
                            <li><a class="dropdown-item" href="{{ route('return-requests.index') }}">طلبات الإسترجاع</a></li>
                            <li><a class="dropdown-item" href="{{ route('wishlist.index') }}">قائمة الأمنيات</a></li>
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
                                <form action="{{ route('trackmyorder') }}" method="POST">
                                    @csrf
                                    <div class="input-group">
                                        <input 
                                            type="text" 
                                            name="trackOrderInput" 
                                            class="form-control" 
                                            placeholder="رقم التتبع أو البريد الإلكتروني"
                                            required
                                        >
                                        <button class="btn btn-outline-secondary" type="submit">
                                            بحث
                                        </button>
                                    </div>
                                </form>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
</nav>

@include('cartmodals')
</div>

<!-- JavaScript for Search Toggle -->


<!-- Bootstrap JS and Icons -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">