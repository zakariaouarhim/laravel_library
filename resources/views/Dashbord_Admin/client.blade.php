<!doctype html>
<html lang="ar" dir="rtl">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>الزبائن</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}"> 
    <link rel="stylesheet" href="{{ asset('css/sidebardaschboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/clients.css') }}">
  </head>
  <body>
    <!-- Navbar -->
    @include('Dashbord_Admin.dashbordHeader')

    <div class="container-fluid">
      <div class="row">
        <!-- Sidebar -->
        @include('Dashbord_Admin.Sidebar')

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
          <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">الزبائن</h1>
          </div>

          <!-- Search Bar -->
          <div class="row mb-3">
            <div class="col-md-6">
              <input type="text" id="searchClientInput" class="form-control" placeholder="بحث عن الزبائن...">
            </div>
          </div>

          <!-- Clients Table -->
          <div class="table-responsive">
            <table class="table table-striped table-hover align-middle" id="clientsTable">
              <thead class="table-dark">
                <tr>
                  <th scope="col">#</th>
                  <th scope="col">اسم الزبون</th>
                  <th scope="col">البريد الإلكتروني</th>
                  <th scope="col">رقم الهاتف</th>
                  <th scope="col">تاريخ التسجيل</th>
                  <th scope="col">الإجراءات</th>
                </tr>
              </thead>
              <tbody>
                <!-- Rows will be dynamically inserted here -->
              </tbody>
            </table>
          </div>

        </main>
      </div>
    </div>

    <!-- Add Client Modal -->
    <div class="modal fade" id="addClientModal" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">إضافة زبون جديد</h5>
            <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal"></button>
          </div>
          <form method="POST" action="#">
            @csrf
            <div class="modal-body">
              <div class="mb-3">
                <label class="form-label">اسم الزبون</label>
                <input type="text" class="form-control" name="clientName" required>
              </div>
              <div class="mb-3">
                <label class="form-label">البريد الإلكتروني</label>
                <input type="email" class="form-control" name="clientEmail" required>
              </div>
              <div class="mb-3">
                <label class="form-label">رقم الهاتف</label>
                <input type="text" class="form-control" name="clientPhone" required>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
              <button type="submit" class="btn btn-primary">حفظ</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    
    
    <script>
      // Example script to handle client search functionality
      const searchClientInput = document.getElementById('searchClientInput');
      searchClientInput.addEventListener('input', () => {
        const query = searchClientInput.value.toLowerCase();
        const rows = document.querySelectorAll('#clientsTable tbody tr');
        rows.forEach(row => {
          const name = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
          row.style.display = name.includes(query) ? '' : 'none';
        });
      });
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.28.0/feather.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    feather.replace();
    </script>
  </body>
</html>
