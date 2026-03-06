<div class="layout-header">

<!-- Top Bar -->
<div class="top-bar" id="topBar">
    <div class="container d-flex justify-content-between align-items-center">
        <div class="top-bar-right d-flex align-items-center gap-3">
            <a href="tel:+212691218840" dir="ltr"><i class="fas fa-phone-alt"></i> +212 69 121 8840</a>
            <a href="https://wa.me/212691218840" target="_blank" rel="noopener" dir="ltr" style="color:#25D366;"><i class="fab fa-whatsapp"></i> واتساب</a>
            <span dir="ltr"><i class="fas fa-envelope"></i> info@maktaba-fukara.com</span>
        </div>
        <div class="top-bar-left d-flex align-items-center gap-3">
            @if(session('is_logged_in'))
                <span><i class="fas fa-user"></i> مرحباً {{ session('user_name') }}</span>
                <a href="{{ route('account.page') }}">حسابي</a>
                <form method="POST" action="{{ route('logout') }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="top-bar-logout"><i class="fas fa-sign-out-alt"></i> خروج</button>
                </form>
            @else
                <a href="{{ route('login2.page') }}"><i class="fas fa-sign-in-alt"></i> تسجيل الدخول</a>
            @endif
        </div>
    </div>
</div>

<!-- Main Navbar -->
<nav class="main-navbar" id="mainNavbar">
    <div class="container">
        <div class="navbar-inner">
            <!-- Logo -->
            <a class="navbar-logo" href="{{ route('index.page') }}">
                <img src="{{ asset('images/Logo2Black.svg') }}" alt="شعار المكتبة">
                
            </a>

            <!-- Search Bar -->
            <form action="{{ route('search.results') }}" method="GET" class="navbar-search position-relative">
                <div class="search-input-wrapper">
                    <input
                        type="search"
                        name="query"
                        id="searchInputHeader"
                        class="search-input"
                        placeholder="ابحث عن كتاب، مؤلف، ناشر..."
                        oninput="searchBooksAutocomplete(this.value, 'searchResultsHeader')"
                        onfocus="if(this.value.length < 2) showRecentSearches('searchResultsHeader')"
                        autocomplete="off"
                        required>
                    <button type="submit" class="search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                <div id="searchResultsHeader" class="search-results-header"></div>
            </form>

            <!-- Navbar Actions -->
            <div class="navbar-actions">
                <!-- Wishlist -->
                <a href="{{ route('wishlist.index') }}" class="nav-action-btn" title="قائمة الأمنيات">
                    <i class="fas fa-heart"></i>
                    @php
                        $wishlistCount = 0;
                        if(auth()->check()) {
                            $wishlistCount = auth()->user()->wishlist()->count();
                        } elseif(session()->has('wishlist')) {
                            $wishlistCount = count(session('wishlist'));
                        }
                    @endphp
                    @if($wishlistCount > 0)
                    <span class="action-badge wishlist-badge">{{ $wishlistCount }}</span>
                    @endif
                </a>

                <!-- Cart -->
                <a href="javascript:void(0);" class="nav-action-btn" onclick="showCartModal()" title="سلة التسوق">
                    <i class="fas fa-shopping-bag"></i>
                    <span id="cartCount" class="action-badge cart-badge">
                        {{ session('cart') ? count(session('cart')) : 0 }}
                    </span>
                </a>

                <!-- Notifications Bell (logged-in users only) -->
                @if(session('is_logged_in'))
                <div class="nav-notif-dropdown" id="notifWrapper">
                    <button class="nav-action-btn" id="notifToggle" type="button" title="الإشعارات">
                        <i class="fas fa-bell"></i>
                        <span class="action-badge notif-badge" id="notifBadge" style="display:none;"></span>
                    </button>
                    <div class="notif-dropdown-menu" id="notifMenu">
                        <div class="notif-header">
                            <span>الإشعارات</span>
                            <button class="notif-mark-all" id="notifMarkAll" type="button">تعليم الكل كمقروء</button>
                        </div>
                        <div class="notif-list" id="notifList">
                            <div class="notif-empty">لا توجد إشعارات</div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Account (mobile-visible dropdown) -->
                <div class="nav-account-dropdown">
                    <button class="nav-action-btn" id="accountToggle" type="button">
                        <i class="fas fa-user-circle"></i>
                    </button>
                    <div class="account-dropdown-menu" id="accountMenu">
                        @if(session('is_logged_in'))
                            <div class="dropdown-user-info">
                                <strong>{{ session('user_name') }}</strong>
                                <small>{{ session('user_email') }}</small>
                            </div>
                            <div class="dropdown-divider"></div>
                            <div class="dropdown-track-order">
                                <label>تعقب طلباتي</label>
                                <form action="{{ route('trackmyorder') }}" method="POST">
                                    @csrf
                                    <div class="track-input-group">
                                        <input type="text" name="trackOrderInput" placeholder="رقم التتبع أو البريد" required>
                                        <button type="submit"><i class="fas fa-search"></i></button>
                                    </div>
                                </form>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a href="{{ route('account.page') }}"><i class="fas fa-user"></i> حسابي</a>
                            <a href="{{ route('my-orders.index') }}"><i class="fas fa-box"></i> الطلبات</a>
                            <a href="{{ route('return-requests.index') }}"><i class="fas fa-undo"></i> طلبات الإسترجاع</a>
                            <a href="{{ route('wishlist.index') }}"><i class="fas fa-heart"></i> قائمة الأمنيات</a>
                            <div class="dropdown-divider"></div>
                            <form method="POST" action="{{ route('logout') }}" style="display:inline;width:100%;">
                                @csrf
                                <button type="submit" class="dropdown-logout"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</button>
                            </form>
                        @else
                            <a href="{{ route('login2.page') }}" class="dropdown-login-btn">
                                <i class="fas fa-sign-in-alt"></i> تسجيل الدخول
                            </a>
                            <div class="dropdown-divider"></div>
                            <div class="dropdown-track-order">
                                <label>تعقب طلباتي</label>
                                <form action="{{ route('trackmyorder') }}" method="POST">
                                    @csrf
                                    <div class="track-input-group">
                                        <input type="text" name="trackOrderInput" placeholder="رقم التتبع أو البريد" required>
                                        <button type="submit"><i class="fas fa-search"></i></button>
                                    </div>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Mobile Menu Toggle -->
                <button class="mobile-menu-btn" id="mobileMenuBtn" type="button" aria-label="القائمة">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </div>
    </div>
</nav>

<!-- Navigation Links Bar -->
<div class="nav-links-bar" id="navLinksBar">
    <div class="container">
        <div class="nav-links-inner">
            <!-- Categories Dropdown -->
            <div class="nav-categories-dropdown">
                <button class="nav-categories-btn" id="categoriesBtn" type="button">
                    <i class="fas fa-th-large"></i>
                    <span>التصنيفات</span>
                    <i class="fas fa-chevron-down chevron-icon"></i>
                </button>
                <div class="categories-mega-menu" id="categoriesMegaMenu">
                    <div class="mega-menu-grid">
                        @if(isset($navCategories))
                        @foreach($navCategories->take(7) as $category)
                        <div class="mega-menu-column">
                            <a href="{{ route('categories.index') }}?category={{ $category->id }}" class="mega-menu-title">
                                {{ $category->name }}
                            </a>
                            @if($category->children && $category->children->count() > 0)
                            <ul class="mega-menu-list">
                                @foreach($category->children->take(5) as $child)
                                <li><a href="{{ route('categories.index') }}?category={{ $child->id }}">{{ $child->name }}</a></li>
                                @endforeach
                            </ul>
                            @endif
                        </div>
                        @endforeach
                        @endif
                        <div class="mega-menu-column mega-menu-all">
                            <a href="{{ route('categories.index') }}" class="view-all-link">
                                <i class="fas fa-arrow-left"></i> عرض كل التصنيفات
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Nav Links -->
            <ul class="nav-links-list">
                <li><a href="{{ route('index.page') }}#popular-books">الأكثر مبيعًا</a></li>
                <li><a href="{{ route('index.page') }}#all-books">الإصدارات الحديثة</a></li>
                <li><a href="{{ route('accessories.index') }}">الإكسسوارات</a></li>
                <li><a href="{{ route('authors.index') }}">المؤلفون</a></li>
                <li><a href="{{ route('publishers.index') }}">دور النشر</a></li>
                <li><a href="{{ route('about.page') }}">من نحن</a></li>
                <li><a href="{{ route('contact.page') }}">اتصل بنا</a></li>
            </ul>
        </div>
    </div>
</div>

<!-- Mobile Menu Overlay -->
<div class="mobile-menu-overlay" id="mobileOverlay"></div>

<!-- Mobile Slide Menu -->
<div class="mobile-slide-menu" id="mobileMenu">
    <div class="mobile-menu-header">
        <img src="{{ asset('images/logo.svg') }}" alt="شعار المكتبة">
        <button class="mobile-menu-close" id="mobileMenuClose"><i class="fas fa-times"></i></button>
    </div>

    <!-- Mobile Search -->
    <form action="{{ route('search.results') }}" method="GET" class="mobile-search position-relative">
        <input type="search" name="query" placeholder="ابحث عن كتاب، مؤلف، ناشر..." autocomplete="off" required>
        <button type="submit"><i class="fas fa-search"></i></button>
    </form>

    <nav class="mobile-nav">
        <a href="{{ route('index.page') }}"><i class="fas fa-home"></i> الرئيسية</a>
        <a href="{{ route('categories.index') }}"><i class="fas fa-th-large"></i> التصنيفات</a>
        <a href="{{ route('index.page') }}#popular-books"><i class="fas fa-fire"></i> الأكثر مبيعًا</a>
        <a href="{{ route('accessories.index') }}"><i class="fas fa-bookmark"></i> الإكسسوارات</a>
        <a href="{{ route('authors.index') }}"><i class="fas fa-pen-fancy"></i> المؤلفون</a>
        <a href="{{ route('publishers.index') }}"><i class="fas fa-building"></i> دور النشر</a>
        <a href="{{ route('about.page') }}"><i class="fas fa-info-circle"></i> من نحن</a>
        <a href="{{ route('contact.page') }}"><i class="fas fa-envelope"></i> اتصل بنا</a>
    </nav>
</div>

@include('cartmodals')
</div>

<!-- Bootstrap JS and Icons -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<script>
window.isLoggedIn = {{ session('is_logged_in') ? 'true' : 'false' }};
window.loginUrl   = "{{ route('login2.page') }}";
</script>

@if(session('is_logged_in'))
<script src="{{ asset('js/notifications.js') }}"></script>
@endif
<script src="{{ asset('js/navigation.js') }}"></script>
