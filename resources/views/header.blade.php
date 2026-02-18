<div class="layout-header">

<!-- Top Bar -->
<div class="top-bar" id="topBar">
    <div class="container d-flex justify-content-between align-items-center">
        <div class="top-bar-right d-flex align-items-center gap-3">
            <span><i class="fas fa-phone-alt"></i> +213 000 000 000</span>
            <span><i class="fas fa-envelope"></i> info@maktaba-fukara.com</span>
        </div>
        <div class="top-bar-left d-flex align-items-center gap-3">
            @if(session('is_logged_in'))
                <span><i class="fas fa-user"></i> مرحباً {{ session('user_name') }}</span>
                <a href="{{ route('account.page') }}">حسابي</a>
                <a href="{{ route('logout') }}" class="top-bar-logout"><i class="fas fa-sign-out-alt"></i> خروج</a>
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
                            <a href="{{ route('logout') }}" class="dropdown-logout"><i class="fas fa-sign-out-alt"></i> تسجيل الخروج</a>
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
    <form action="{{ route('search.results') }}" method="GET" class="mobile-search">
        <input type="search" name="query" placeholder="ابحث عن كتاب..." required>
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

<script>
(function() {
    // Account dropdown toggle
    var accountToggle = document.getElementById('accountToggle');
    var accountMenu = document.getElementById('accountMenu');
    if (accountToggle && accountMenu) {
        accountToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            accountMenu.classList.toggle('show');
        });
        document.addEventListener('click', function(e) {
            if (!accountMenu.contains(e.target) && e.target !== accountToggle) {
                accountMenu.classList.remove('show');
            }
        });
    }

    // Categories mega menu
    var categoriesBtn = document.getElementById('categoriesBtn');
    var megaMenu = document.getElementById('categoriesMegaMenu');
    if (categoriesBtn && megaMenu) {
        categoriesBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            megaMenu.classList.toggle('show');
            categoriesBtn.classList.toggle('active');
        });
        document.addEventListener('click', function(e) {
            if (!megaMenu.contains(e.target) && e.target !== categoriesBtn) {
                megaMenu.classList.remove('show');
                categoriesBtn.classList.remove('active');
            }
        });
    }

    // Mobile menu
    var mobileBtn = document.getElementById('mobileMenuBtn');
    var mobileMenu = document.getElementById('mobileMenu');
    var mobileOverlay = document.getElementById('mobileOverlay');
    var mobileClose = document.getElementById('mobileMenuClose');

    function openMobile() {
        mobileMenu.classList.add('open');
        mobileOverlay.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
    function closeMobile() {
        mobileMenu.classList.remove('open');
        mobileOverlay.classList.remove('show');
        document.body.style.overflow = '';
    }
    if (mobileBtn) mobileBtn.addEventListener('click', openMobile);
    if (mobileClose) mobileClose.addEventListener('click', closeMobile);
    if (mobileOverlay) mobileOverlay.addEventListener('click', closeMobile);

    // Sticky navbar — hide top bar on scroll, navbar + nav links stick together
    var topBar = document.getElementById('topBar');
    var mainNavbar = document.getElementById('mainNavbar');
    var navLinksBar = document.getElementById('navLinksBar');

    window.addEventListener('scroll', function() {
        var scrollY = window.scrollY;
        if (scrollY > 50) {
            topBar.classList.add('hidden');
            mainNavbar.classList.add('sticky');
            navLinksBar.classList.add('sticky');
        } else {
            topBar.classList.remove('hidden');
            mainNavbar.classList.remove('sticky');
            navLinksBar.classList.remove('sticky');
        }
    });
})();
</script>
