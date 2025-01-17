 <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
        <div class="position-sticky pt-3">
          <ul class="nav flex-column">
            <li class="nav-item">
              <a class="nav-link active" aria-current="page" href="{{ route('Dashbord_Admin.dashboard') }}">
                <span data-feather="home"></span>
                لوحة القيادة
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="{{ route('orders.index') }}">
                <span data-feather="file"></span>
                الطلبات
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="{{ route('Dashbord_Admin.product') }}">
                <span data-feather="shopping-cart"></span>
                المنتجات
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="{{ route('client.index') }}">
                <span data-feather="users"></span>
                الزبائن
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="#">
                <span data-feather="bar-chart-2"></span>
                التقارير
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="#">
                <span data-feather="layers"></span>
                التكاملات
              </a>
            </li>
          </ul>

          <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>التقارير المحفوظة</span>
            <a class="link-secondary" href="#" aria-label="إضافة تقرير جديد">
              <span data-feather="plus-circle"></span>
            </a>
          </h6>
          <ul class="nav flex-column mb-2">
            <li class="nav-item">
              <a class="nav-link" href="#">
                <span data-feather="file-text"></span>
                الشهر الحالي
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="#">
                <span data-feather="file-text"></span>
                الربع الأخير
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="#">
                <span data-feather="file-text"></span>
                التفاعل الإجتماعي
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="#">
                <span data-feather="file-text"></span>
                مبيعات نهاية العام
              </a>
            </li>
          </ul>
        </div>
    </nav>