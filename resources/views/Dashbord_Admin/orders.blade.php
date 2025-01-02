<!doctype html>
<html lang="ar" dir="rtl">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>إدارة الطلبات</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.rtl.min.css">
    
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/order.css') }}">
    <link rel="stylesheet" href="{{ asset('css/bootstrap.rtl.min.css') }}">
    
  </head>
  <body>
    @include('Dashbord_Admin.dashbordHeader')
    
    <div class="container-fluid">
      <div class="row">
        <!-- Sidebar -->
        @include('Dashbord_Admin.Sidebar')

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
          <h2 class="text-center mb-4">إدارة الطلبات</h2>
          
          <!-- Filters Section -->
          <div class="row filter-section">
            <div class="col-md-6">
              <input type="text" id="searchInput" class="form-control" placeholder="بحث عن الطلبات...">
            </div>
            <div class="col-md-3">
              <select id="statusFilter" class="form-select">
                <option value="">كل الحالات</option>
                <option value="قيد التنفيذ">قيد التنفيذ</option>
                <option value="مكتمل">مكتمل</option>
                <option value="ملغي">ملغي</option>
              </select>
            </div>
            <div class="col-md-3">
              <button class="btn btn-primary w-100" onclick="applyFilters()">تطبيق الفلاتر</button>
            </div>
          </div>

          <!-- Orders Table -->
          <div class="table-responsive">
            <table class="table table-bordered table-hover text-center align-middle">
              <!-- Your existing table content -->
            </table>
          </div>

          <!-- Pagination -->
          <nav>
            <ul class="pagination">
              <li class="page-item">
                <a class="page-link" href="#" onclick="prevPage()">السابق</a>
              </li>
              <li class="page-item">
                <a class="page-link" href="#" onclick="nextPage()">التالي</a>
              </li>
            </ul>
          </nav>
        </main>
      </div>
    </div>

    <!-- Scripts -->
      <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
      <script src="https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.24.1/feather.min.js"></script>
      <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.3/Chart.min.js"></script>
      <script src="{{ asset('js/dashboard.js') }}"></script>
      <script src="{{ asset('js/order.js') }}"></script> 
    
    
    
  </body>
</html>
