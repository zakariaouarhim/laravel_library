<!-- Refactored Sidebar Navigation -->
<nav id="sidebarMenu" class="sidebar">
    <!-- Logo Section -->
    <div class="sidebar-header">
        <div class="logo-container">
            <img src="{{ asset('images/logo.svg') }}" alt="Logo" class="sidebar-logo">
            <span class="logo-text">أسير الكتب</span>
        </div>
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <!-- Main Navigation -->
    <div class="sidebar-content">
        <div class="nav-section">
            <h5 class="section-title">الرئيسية</h5>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('Dashbord_Admin.dashboard') ? 'active' : '' }}" 
                       href="{{ route('Dashbord_Admin.dashboard') }}">
                        <i class="fas fa-chart-pie"></i>
                        <span class="nav-text">لوحة القيادة</span>
                        <span class="nav-badge">جديد</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Management Section -->
        <div class="nav-section">
            <h5 class="section-title">الإدارة</h5>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('orders.index') ? 'active' : '' }}" 
                       href="{{ route('admin.orders.index') }}">
                        <i class="fas fa-shopping-bag"></i>
                        <span class="nav-text">الطلبات</span>
                        <span class="nav-badge badge-danger">{{ $pendingOrders ?? 0 }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('*product*') ? 'active' : '' }}" 
                       href="{{ route('Dashbord_Admin.product') }}">
                        <i class="fas fa-book"></i>
                        <span class="nav-text">المنتجات</span>
                        <span class="nav-badge">{{ $totalProducts ?? 0 }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('client.index') ? 'active' : '' }}" 
                       href="{{ route('client.index') }}">
                        <i class="fas fa-users"></i>
                        <span class="nav-text">الزبائن</span>
                        <span class="nav-badge">{{ $totalClients ?? 0 }}</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('*shipment*') ? 'active' : '' }}" 
                       href="{{ route('Dashbord_Admin.Shipment_Management') }}">
                        <i class="fas fa-truck"></i>
                        <span class="nav-text">إدارة الشحنات</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- System Section -->
        <div class="nav-section">
            <h5 class="section-title">النظام</h5>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a class="nav-link {{ request()->routeIs('*ManagementSystem*') ? 'active' : '' }}" 
                       href="{{ route('Dashbord_Admin.ManagementSystem') }}">
                        <i class="fas fa-cogs"></i>
                        <span class="nav-text">إدارة المكتبة</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="fas fa-chart-bar"></i>
                        <span class="nav-text">التقارير</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="fas fa-plug"></i>
                        <span class="nav-text">التكاملات</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="fas fa-cog"></i>
                        <span class="nav-text">الإعدادات</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Saved Reports Section -->
        <div class="nav-section saved-reports">
            <div class="section-header">
                <h5 class="section-title">التقارير المحفوظة</h5>
                <button class="add-report-btn" title="إضافة تقرير جديد">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="fas fa-calendar-alt"></i>
                        <span class="nav-text">الشهر الحالي</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="fas fa-calendar-alt"></i>
                        <span class="nav-text">الربع الأخير</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="fas fa-share-alt"></i>
                        <span class="nav-text">التفاعل الاجتماعي</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="fas fa-line-chart"></i>
                        <span class="nav-text">مبيعات نهاية العام</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <!-- User Section at Bottom -->
    <div class="sidebar-footer">
        <div class="user-info">
            <div class="user-avatar">
                <i class="fas fa-user"></i>
            </div>
            <div class="user-details">
                <h6>{{ Auth::user()->name ?? 'مسؤول' }}</h6>
                <span>مسؤول النظام</span>
            </div>
        </div>
        <a href="{{ route('logout') }}" class="logout-btn" title="تسجيل الخروج">
            <i class="fas fa-sign-out-alt"></i>
        </a>
    </div>
</nav>

<!-- Overlay for mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<script>
    /**
     * Sidebar Toggle for Mobile
     */
    document.getElementById('sidebarToggle')?.addEventListener('click', function() {
        const content = document.querySelector('.sidebar-content');
        content.classList.toggle('show');
    });

    /**
     * Close sidebar when clicking on a nav link (mobile)
     */
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                const content = document.querySelector('.sidebar-content');
                content.classList.remove('show');
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