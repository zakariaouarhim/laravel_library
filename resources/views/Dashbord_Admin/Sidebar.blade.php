<!-- Mobile sidebar toggle (hamburger) -->
<button class="sidebar-mobile-toggle" id="sidebarMobileToggle" aria-label="فتح القائمة">
    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="3" y1="6" x2="21" y2="6"></line>
        <line x1="3" y1="12" x2="21" y2="12"></line>
        <line x1="3" y1="18" x2="21" y2="18"></line>
    </svg>
</button>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<nav id="sidebarMenu" class="sidebar">
    <!-- Sidebar Header -->
    <div class="sidebar-header">
        <div class="sidebar-header-content">
            <div class="logo-container">
                <a href="{{ route('index.page') }}" class="sidebar-logo-link">
                    <img src="{{ asset('images/logo.svg') }}" alt="Logo" class="sidebar-logo" width="40" height="40">
                </a>
                <span class="logo-text">مكتبة الفقراء </span>
            </div>
            <button class="sidebar-collapse-btn" id="sidebarCollapseBtn" aria-label="طي القائمة الجانبية">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="15 18 9 12 15 6"></polyline>
                </svg>
            </button>
        </div>
    </div>

    <!-- Sidebar Content -->
    <div class="sidebar-content">
        <!-- Main Section -->
        <div class="sidebar-group">
            <div class="sidebar-group-label">الرئيسية</div>
            <nav class="sidebar-nav">
                <a href="{{ route('admin.Dashbord_Admin.dashboard') }}" 
                   class="sidebar-nav-item {{ request()->routeIs('Dashbord_Admin.dashboard') ? 'active' : '' }}"
                   title="لوحة القيادة">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 3v1m6.894 1.106l-.707.707M21 12h-1m-1.106 6.894l-.707-.707M12 21v-1m-6.894-1.106l.707-.707M3 12h1m1.106-6.894l.707.707"/><circle cx="12" cy="12" r="4"/>
                    </svg>
                    <span class="nav-text">لوحة القيادة</span>
                    <span class="sidebar-badge">جديد</span>
                </a>
            </nav>
        </div>

        <!-- Management Section -->
        <div class="sidebar-group">
            <div class="sidebar-group-label">الإدارة</div>
            <nav class="sidebar-nav">
                <a href="{{ route('admin.orders.index') }}" 
                   class="sidebar-nav-item {{ request()->routeIs('orders.index') ? 'active' : '' }}"
                   title="الطلبات">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                    </svg>
                    <span class="nav-text">الطلبات</span>
                    <span class="sidebar-badge badge-destructive">{{ $pendingOrders ?? 0 }}</span>
                </a>
                <a href="{{ route('admin.return-requests.index') }}"
                   class="sidebar-nav-item {{ request()->routeIs('admin.return-requests.*') ? 'active' : '' }}"
                   title="طلبات الإسترجاع">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="1 4 1 10 7 10"></polyline><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>
                    </svg>
                    <span class="nav-text">طلبات الإسترجاع</span>
                    <span class="sidebar-badge badge-destructive">{{ $pendingReturns ?? 0 }}</span>
                </a>
                <a href="{{ route('admin.Dashbord_Admin.product') }}"
                   class="sidebar-nav-item {{ request()->routeIs('*product*') ? 'active' : '' }}"
                   title="المنتجات">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
                    </svg>
                    <span class="nav-text">المنتجات</span>
                    <span class="sidebar-badge">{{ $totalProducts ?? 0 }}</span>
                </a>
                <a href="{{ route('admin.books.ingest.create') }}"
                   class="sidebar-nav-item {{ request()->routeIs('admin.books.ingest.*') ? 'active' : '' }}"
                   title="إضافة كتاب من API">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    <span class="nav-text">إضافة كتاب من API</span>
                </a>
                <a href="{{ route('admin.books.pending.index') }}"
                   class="sidebar-nav-item {{ request()->routeIs('admin.books.pending.*') ? 'active' : '' }}"
                   title="كتب قيد المراجعة">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                    <span class="nav-text">كتب قيد المراجعة</span>
                </a>
                <a href="{{ route('admin.Dashbord_Admin.accessories') }}"
                   class="sidebar-nav-item {{ request()->routeIs('admin.*accessories*') ? 'active' : '' }}"
                   title="الإكسسوارات">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
                    </svg>
                    <span class="nav-text">الإكسسوارات</span>
                </a>
                @if(Auth::user()->role === 'super_admin')
                <a href="{{ route('admin.users.index') }}"
                   class="sidebar-nav-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"
                   title="إدارة المستخدمين">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                    <span class="nav-text">إدارة المستخدمين</span>
                    <span class="sidebar-badge" style="background:#c0392b;">مشرف عام</span>
                </a>
                @endif
                <a href="{{ route('admin.categories.index') }}"
                   class="sidebar-nav-item {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}"
                   title="الفئات">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                    </svg>
                    <span class="nav-text">الفئات</span>
                </a>
                <a href="{{ route('admin.series.index') }}"
                   class="sidebar-nav-item {{ request()->routeIs('admin.series.*') ? 'active' : '' }}"
                   title="السلاسل">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/><line x1="8" y1="7" x2="16" y2="7"/><line x1="8" y1="11" x2="16" y2="11"/><line x1="8" y1="15" x2="12" y2="15"/>
                    </svg>
                    <span class="nav-text">السلاسل</span>
                </a>
                <a href="{{ route('admin.bundles.index') }}"
                   class="sidebar-nav-item {{ request()->routeIs('admin.bundles.*') ? 'active' : '' }}"
                   title="الباقات">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                        <polyline points="3.27 6.96 12 12.01 20.73 6.96"/>
                        <line x1="12" y1="22.08" x2="12" y2="12"/>
                    </svg>
                    <span class="nav-text">الباقات</span>
                </a>
                <a href="{{ route('admin.coupons.index') }}"
                   class="sidebar-nav-item {{ request()->routeIs('admin.coupons.*') ? 'active' : '' }}"
                   title="الكوبونات">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 12V22H4V12"/><path d="M22 7H2v5h20V7z"/><path d="M12 22V7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/>
                    </svg>
                    <span class="nav-text">الكوبونات</span>
                </a>
                <a href="{{ route('admin.offers.index') }}"
                   class="sidebar-nav-item {{ request()->routeIs('admin.offers.*') ? 'active' : '' }}"
                   title="العروض">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/>
                    </svg>
                    <span class="nav-text">العروض</span>
                </a>
                <a href="{{ route('admin.home-carousels.index') }}"
                   class="sidebar-nav-item {{ request()->routeIs('admin.home-carousels.*') ? 'active' : '' }}"
                   title="كاروسيلات الصفحة الرئيسية">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="6" width="14" height="12" rx="2"/><path d="M18 8v8"/><path d="M22 10v4"/>
                    </svg>
                    <span class="nav-text">كاروسيلات الرئيسية</span>
                </a>
                <a href="{{ route('admin.client.index') }}"
                   class="sidebar-nav-item {{ request()->routeIs('client.index') ? 'active' : '' }}"
                   title="الزبائن">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    </svg>
                    <span class="nav-text">الزبائن</span>
                    <span class="sidebar-badge">{{ $totalClients ?? 0 }}</span>
                </a>
                <a href="{{ route('admin.Dashbord_Admin.Shipment_Management') }}" 
                   class="sidebar-nav-item {{ request()->routeIs('admin.*shipment*') ? 'active' : '' }}"
                   title="إدارة الشحنات">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2zM7 9h.01M7 13h4"/><path d="M16 5h2a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-2"/>
                    </svg>
                    <span class="nav-text">إدارة الشحنات</span>
                </a>
                <a href="{{ route('admin.Dashbord_Admin.authors') }}"
                   class="sidebar-nav-item {{ request()->routeIs('admin.*authors*') ? 'active' : '' }}"
                   title="المؤلفون">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                    </svg>
                    <span class="nav-text">المؤلفون</span>
                </a>
                <a href="{{ route('admin.publishing_houses.index') }}"
                   class="sidebar-nav-item {{ request()->routeIs('admin.publishing_houses.*') ? 'active' : '' }}"
                   title="دور النشر">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 21h18"/><path d="M5 21V7l8-4v18"/><path d="M19 21V11l-6-4"/><path d="M9 9v.01"/><path d="M9 12v.01"/><path d="M9 15v.01"/><path d="M9 18v.01"/>
                    </svg>
                    <span class="nav-text">دور النشر</span>
                </a>
                <a href="{{ route('admin.reviews.index') }}"
                   class="sidebar-nav-item {{ request()->routeIs('admin.reviews.*') ? 'active' : '' }}"
                   title="التقييمات">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                    </svg>
                    <span class="nav-text">التقييمات</span>
                    @php $pendingReviews = \App\Models\Book_Review::where('status', 'pending')->count(); @endphp
                    @if($pendingReviews > 0)
                    <span class="sidebar-badge badge-destructive">{{ $pendingReviews }}</span>
                    @endif
                </a>
                <a href="{{ route('admin.quotes.index') }}"
                   class="sidebar-nav-item {{ request()->routeIs('admin.quotes.*') ? 'active' : '' }}"
                   title="الاقتباسات">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 21c3 0 7-1 7-8V5c0-1.25-.756-2.017-2-2H4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2 1 0 1 0 1 1v1c0 1-1 2-2 2s-1 .008-1 1.031V20c0 1 0 1 1 1z"/>
                        <path d="M15 21c3 0 7-1 7-8V5c0-1.25-.757-2.017-2-2h-4c-1.25 0-2 .75-2 1.972V11c0 1.25.75 2 2 2h.75c0 2.25.25 4-2.75 4v3c0 1 0 1 1 1z"/>
                    </svg>
                    <span class="nav-text">الاقتباسات</span>
                </a>
                <a href="{{ route('admin.contact-messages.index') }}"
                   class="sidebar-nav-item {{ request()->routeIs('admin.contact-messages.*') ? 'active' : '' }}"
                   title="الرسائل">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/>
                    </svg>
                    <span class="nav-text">الرسائل</span>
                    @php $unreadMessages = \App\Models\ContactMessage::where('is_read', false)->count(); @endphp
                    @if($unreadMessages > 0)
                    <span class="sidebar-badge badge-destructive">{{ $unreadMessages }}</span>
                    @endif
                </a>
            </nav>
        </div>

        <!-- System Section -->
        <div class="sidebar-group">
            <div class="sidebar-group-label">النظام</div>
            <nav class="sidebar-nav">
                <a href="{{ route('admin.Dashbord_Admin.ManagementSystem') }}" 
                   class="sidebar-nav-item {{ request()->routeIs('admin.*ManagementSystem*') ? 'active' : '' }}"
                   title="إدارة المكتبة">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="1"/><path d="M12 1v6m0 6v6M4.22 4.22l4.24 4.24m2.12 2.12l4.24 4.24M1 12h6m6 0h6M4.22 19.78l4.24-4.24m2.12-2.12l4.24-4.24"/>
                    </svg>
                    <span class="nav-text">إدارة المكتبة</span>
                </a>
                <a href="{{ route('admin.reports.index') }}"
                   class="sidebar-nav-item {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}"
                   title="التقارير">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="2" x2="12" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                    <span class="nav-text">التقارير</span>
                </a>
                <a href="{{ route('admin.search-insights.index') }}"
                   class="sidebar-nav-item {{ request()->routeIs('admin.search-insights.*') ? 'active' : '' }}"
                   title="إحصائيات البحث">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <span class="nav-text">إحصائيات البحث</span>
                </a>
                <a href="{{ route('admin.faqs.index') }}"
                   class="sidebar-nav-item {{ request()->routeIs('admin.faqs.*') ? 'active' : '' }}"
                   title="الأسئلة الشائعة">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                    <span class="nav-text">الأسئلة الشائعة</span>
                </a>
                <a href="{{ route('admin.settings.index') }}"
                   class="sidebar-nav-item {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}"
                   title="الإعدادات">
                    <i class="fas fa-cog"></i>
                    <span class="nav-text">الإعدادات</span>
                </a>
            </nav>
        </div>

    </div>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar">
                <span>{{ substr(Auth::user()->name ?? 'م', 0, 1) }}</span>
            </div>
            <div class="user-details">
                <p class="user-name">{{ Auth::user()->name ?? 'مسؤول' }}</p>
                <p class="user-role">
                    @if(Auth::user()->role === 'super_admin')
                        <span style="color:#c0392b;font-weight:700;">مشرف عام</span>
                    @else
                        مسؤول النظام
                    @endif
                </p>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}" style="display:inline;">
            @csrf
            <button type="submit" class="logout-btn" title="تسجيل الخروج" style="background:none;border:none;padding:0;cursor:pointer;">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>
                </svg>
            </button>
        </form>
    </div>
</nav>
<script>
    /**
     * Sidebar Collapse/Expand
     */
    const sidebarCollapseBtn = document.getElementById('sidebarCollapseBtn');
    const sidebar = document.getElementById('sidebarMenu');
    const mainContent = document.querySelector('.main-content');

    // Load sidebar state from localStorage
    const sidebarState = localStorage.getItem('sidebarCollapsed');
    if (sidebarState === 'true') {
        sidebar.classList.add('collapsed');
        if (mainContent) {
            mainContent.classList.add('sidebar-collapsed');
        }
    }

    // Toggle sidebar collapse
    sidebarCollapseBtn?.addEventListener('click', function(e) {
        e.preventDefault();
        sidebar.classList.toggle('collapsed');
        if (mainContent) {
            mainContent.classList.toggle('sidebar-collapsed');
        }
        // Save state
        localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
    });

    /**
     * Mobile sidebar toggle (hamburger + backdrop)
     */
    const mobileToggle = document.getElementById('sidebarMobileToggle');
    const backdrop = document.getElementById('sidebarBackdrop');

    function openMobileSidebar() {
        sidebar.classList.add('open');
        backdrop?.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
    function closeMobileSidebar() {
        sidebar.classList.remove('open');
        backdrop?.classList.remove('show');
        document.body.style.overflow = '';
    }

    mobileToggle?.addEventListener('click', function(e) {
        e.preventDefault();
        if (sidebar.classList.contains('open')) {
            closeMobileSidebar();
        } else {
            openMobileSidebar();
        }
    });

    backdrop?.addEventListener('click', closeMobileSidebar);

    /**
     * Close sidebar when clicking on a nav link (mobile)
     */
    document.querySelectorAll('.sidebar-nav-item').forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                closeMobileSidebar();
            }
        });
    });

    // Reset state when resizing above mobile breakpoint
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            closeMobileSidebar();
        }
    });

</script>