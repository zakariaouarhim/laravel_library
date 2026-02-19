<nav id="sidebarMenu" class="sidebar">
    <!-- Sidebar Header -->
    <div class="sidebar-header">
        <div class="sidebar-header-content">
            <div class="logo-container">
                <a href="{{ route('index.page') }}" class="sidebar-logo-link">
                    <img src="{{ asset('images/logo.svg') }}" alt="Logo" class="sidebar-logo">
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
                <a href="{{ route('admin.coupons.index') }}"
                   class="sidebar-nav-item {{ request()->routeIs('admin.coupons.*') ? 'active' : '' }}"
                   title="الكوبونات">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 12V22H4V12"/><path d="M22 7H2v5h20V7z"/><path d="M12 22V7"/><path d="M12 7H7.5a2.5 2.5 0 0 1 0-5C11 2 12 7 12 7z"/><path d="M12 7h4.5a2.5 2.5 0 0 0 0-5C13 2 12 7 12 7z"/>
                    </svg>
                    <span class="nav-text">الكوبونات</span>
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
                <a href="#" class="sidebar-nav-item" title="التقارير">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="2" x2="12" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                    <span class="nav-text">التقارير</span>
                </a>
                <a href="#" class="sidebar-nav-item" title="التكاملات">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    </svg>
                    <span class="nav-text">التكاملات</span>
                </a>
                <a href="#" class="sidebar-nav-item" title="الإعدادات">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="1"/><path d="M12 1v6m0 6v6M4.22 4.22l4.24 4.24m2.12 2.12l4.24 4.24M1 12h6m6 0h6M4.22 19.78l4.24-4.24m2.12-2.12l4.24-4.24"/>
                    </svg>
                    <span class="nav-text">الإعدادات</span>
                </a>
            </nav>
        </div>

        <!-- Saved Reports Section -->
        <div class="sidebar-group sidebar-group-saved">
            <div class="sidebar-group-header">
                <div class="sidebar-group-label">التقارير المحفوظة</div>
                <button class="add-report-btn" title="إضافة تقرير جديد">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                </button>
            </div>
            <nav class="sidebar-nav">
                <a href="#" class="sidebar-nav-item" title="الشهر الحالي">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M8 2v4m8-4v4M3 10.5h18M5 21h14c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2z"/>
                    </svg>
                    <span class="nav-text">الشهر الحالي</span>
                </a>
                <a href="#" class="sidebar-nav-item" title="الربع الأخير">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M8 2v4m8-4v4M3 10.5h18M5 21h14c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2z"/>
                    </svg>
                    <span class="nav-text">الربع الأخير</span>
                </a>
                <a href="#" class="sidebar-nav-item" title="التفاعل الاجتماعي">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="18" cy="5" r="3"/><path d="M21 17v2a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2v-2"/><path d="M3 7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4v4a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4"/>
                    </svg>
                    <span class="nav-text">التفاعل الاجتماعي</span>
                </a>
                <a href="#" class="sidebar-nav-item" title="مبيعات نهاية العام">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 3v18a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-5l-2-3h-7a2 2 0 0 0-2 2z"/><polyline points="15 13 21 13 18 21 15 13"/>
                    </svg>
                    <span class="nav-text">مبيعات نهاية العام</span>
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
     * Close sidebar when clicking on a nav link (mobile)
     */
    document.querySelectorAll('.sidebar-nav-item').forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                sidebar.classList.remove('open');
            }
        });
    });

    /**
     * Add Report Button
     */
    document.querySelector('.add-report-btn')?.addEventListener('click', function(e) {
        e.preventDefault();
        alert('سيتم إضافة ميزة إنشاء تقارير مخصصة قريباً');
    });
</script>