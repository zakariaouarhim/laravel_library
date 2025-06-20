<!doctype html>
<html lang="ar" dir="rtl">
  <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <meta name="description" content="">
      <meta name="author" content="Mark Otto, Jacob Thornton, and Bootstrap contributors">
      <meta name="generator" content="Hugo 0.84.0">
      <title>قالب لوحة القيادة </title>

    <!-- Bootstrap RTL CSS -->
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.rtl.min.css" integrity="sha384-gXt9imSW0VcJVHezoNQsP+TNrjYXoGcrqBZJpry9zJt8PCQjobwmhMGaDHTASo9N" crossorigin="anonymous">

      <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
      <link rel="stylesheet" href="{{ asset('css/sidebardaschboard.css') }}">
      
      <!-- Favicon -->
      <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
  </head>
  <body>
    
    @include('Dashbord_Admin.dashbordHeader')
    <br><br>
    <div class="container-fluid">
    <div class="row">
      @include('Dashbord_Admin.Sidebar')

      <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
          <h1 class="h2">لوحة القيادة</h1>
          <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
              <button type="button" class="btn btn-sm btn-outline-secondary">مشاركة</button>
              <button type="button" class="btn btn-sm btn-outline-secondary">تصدير</button>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle">
              <span data-feather="calendar"></span>
              هذا الأسبوع
            </button>
          </div>
        </div>

        <canvas class="my-4 w-100" id="myChart" width="900" height="380"></canvas>

        <h2>عنوان القسم</h2>
        <div class="table-responsive">
          <table class="table table-striped table-sm">
            <thead>
              <tr>
                <th scope="col">#</th>
                <th scope="col">عنوان</th>
                <th scope="col">عنوان</th>
                <th scope="col">عنوان</th>
                <th scope="col">عنوان</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>1,001</td>
                <td>بيانات</td>
                <td>عشوائية</td>
                <td>تثري</td>
                <td>الجدول</td>
              </tr>
              <tr>
                <td>1,002</td>
                <td>تثري</td>
                <td>مبهة</td>
                <td>تصميم</td>
                <td>تنسيق</td>
              </tr>
              <tr>
                <td>1,003</td>
                <td>عشوائية</td>
                <td>غنية</td>
                <td>قيمة</td>
                <td>مفيدة</td>
              </tr>
              <tr>
                <td>1,003</td>
                <td>معلومات</td>
                <td>تثري</td>
                <td>توضيحية</td>
                <td>عشوائية</td>
              </tr>
              <tr>
                <td>1,004</td>
                <td>الجدول</td>
                <td>بيانات</td>
                <td>تنسيق</td>
                <td>قيمة</td>
              </tr>
              <tr>
                <td>1,005</td>
                <td>قيمة</td>
                <td>مبهة</td>
                <td>الجدول</td>
                <td>تثري</td>
              </tr>
              <tr>
                <td>1,006</td>
                <td>قيمة</td>
                <td>توضيحية</td>
                <td>غنية</td>
                <td>عشوائية</td>
              </tr>
              <tr>
                <td>1,007</td>
                <td>تثري</td>
                <td>مفيدة</td>
                <td>معلومات</td>
                <td>مبهة</td>
              </tr>
              <tr>
                <td>1,008</td>
                <td>بيانات</td>
                <td>عشوائية</td>
                <td>تثري</td>
                <td>الجدول</td>
              </tr>
              <tr>
                <td>1,009</td>
                <td>تثري</td>
                <td>مبهة</td>
                <td>تصميم</td>
                <td>تنسيق</td>
              </tr>
              <tr>
                <td>1,010</td>
                <td>عشوائية</td>
                <td>غنية</td>
                <td>قيمة</td>
                <td>مفيدة</td>
              </tr>
              <tr>
                <td>1,011</td>
                <td>معلومات</td>
                <td>تثري</td>
                <td>توضيحية</td>
                <td>عشوائية</td>
              </tr>
              <tr>
                <td>1,012</td>
                <td>الجدول</td>
                <td>تثري</td>
                <td>تنسيق</td>
                <td>قيمة</td>
              </tr>
              <tr>
                <td>1,013</td>
                <td>قيمة</td>
                <td>مبهة</td>
                <td>الجدول</td>
                <td>تصميم</td>
              </tr>
              <tr>
                <td>1,014</td>
                <td>قيمة</td>
                <td>توضيحية</td>
                <td>غنية</td>
                <td>عشوائية</td>
              </tr>
              <tr>
                <td>1,015</td>
                <td>بيانات</td>
                <td>مفيدة</td>
                <td>معلومات</td>
                <td>الجدول</td>
              </tr>
            </tbody>
          </table>
        </div>
      </main>
    </div>
    </div>

      <!-- Scripts -->
      <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
      <script src="https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.24.1/feather.min.js"></script>
      <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.3/Chart.min.js"></script>
      <script src="{{ asset('js/dashboard.js') }}"></script>

        <script src="https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.24.1/feather.min.js" integrity="sha384-EbSscX4STvYAC/DxHse8z5gEDaNiKAIGW+EpfzYTfQrgIlHywXXrM9SUIZ0BlyfF" crossorigin="anonymous"></script><script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.3/Chart.min.js" integrity="sha384-i+dHPTzZw7YVZOx9lbH5l6lP74sLRtMtwN2XjVqjf3uAGAREAF4LMIUDTWEVs4LI" crossorigin="anonymous"></script><script src="dashboard.js"></script>
  </body>
</html>
