<!doctype html>
<html lang="ar" dir="rtl">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>المنتجات</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
    
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sidebardaschboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/product.css') }}">
    
    
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
            <h1 class="h2">المنتجات</h1>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
              <span data-feather="plus"></span>
              إضافة منتج جديد
            </button>
          </div>

          <!-- Search and Filter -->
          <div class="row mb-3">
            <div class="col-md-6">
              <input type="text" id="searchInput" class="form-control" placeholder="بحث عن المنتجات...">
            </div>
          </div>

          <!-- Products Table -->
          <div class="table-responsive">
            <table class="table table-striped table-hover align-middle" id="productsTable">
              <thead class="table-dark">
                <tr>
                  <th scope="col">#</th>
                  <th scope="col">الصورة</th>
                  <th scope="col">اسم المنتج</th>
                  <th scope="col" style="max-width: 500px;">الوصف</th>
                  <th scope="col">السعر</th>
                  <th scope="col">المؤلف</th>
                  <th scope="col">عدد الصفحات</th>
                  <th scope="col">اللغة</th>
                  <th scope="col">ISBN</th>
                  <th scope="col">الإجراءات</th>
                </tr>
              </thead>
              <tbody id="productsTableBody">
                <!-- Rows will be dynamically inserted here -->
              </tbody>
            </table>
          </div>
        </main>
      </div>
    </div>

    <!-- Add Product Modal -->
    
      
      <form id="addproductform" method="POST" action="{{ route('product.add') }}" enctype="multipart/form-data">
    @csrf
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">إضافة منتج جديد</h5>
                    <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">اسم المنتج</label>
                        <input type="text" class="form-control" id="productName" name="productName" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الكاتب </label>
                        <input type="text" class="form-control" id="productauthor" name="productauthor" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الوصف</label>
                        <textarea class="form-control" id="productDescription" rows="3" name="productDescription" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">السعر</label>
                        <input type="number" class="form-control" id="productPrice" step="1" name="productPrice" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">عدد الصفحات</label>
                        <input type="number" class="form-control" id="productNumPages" step="1" name="productNumPages" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">اللغة</label>
                        <input type="text" class="form-control" id="productLanguage" name="productLanguage" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">دار النشر</label>
                        <input type="text" class="form-control" id="ProductPublishingHouse" name="ProductPublishingHouse" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ISBN</label>
                        <input type="text" class="form-control" id="productIsbn" name="productIsbn" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الفئة</label>
                        <select name="Productcategorie" id="Productcategorie" class="form-select" required>
                            <option value="1">روايات</option>
                            <option value="2">كتب دينية</option>
                            <option value="3">التنمية البشرية وتنمية وتطوير الذات</option>
                            <option value="4">قصص الأطفال</option>
                            <option value="5">فلسفة</option>
                            <option value="6">كتب الفكر</option>
                            <option value="7">علم النفس</option>
                            <option value="8">علم الاجتماع</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">صورة المنتج</label>
                        <input type="file" class="form-control" id="productImage" accept="image/*" name="productImage" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ</button>
                </div>
            </div>
        </div>
    </div>
</form>

    
    <!-- Edit Product Modal -->
<div class="modal fade" id="editProductModal" tabindex="-1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">تعديل المنتج</h5>
        <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="editProductForm">
          <input type="hidden" id="productId" />
          <div class="mb-3">
            <label class="form-label">اسم المنتج</label>
            <input type="text" class="form-control" id="editProductName" required>
          </div>
          <div class="mb-3">
            <label class="form-label">المؤلف</label>
            <input type="text" class="form-control" id="editAuthor" required>
          </div>
          <div class="mb-3">
            <label class="form-label">الوصف</label>
            <textarea class="form-control" id="editProductDescription" rows="3" required></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label">السعر</label>
            <input type="number" class="form-control" id="editProductPrice" step="1" required>
          </div>
          <div class="mb-3">
            <label class="form-label">عدد الصفحات</label>
            <input type="number" class="form-control" id="editProductPageNum" step="1" required>
          </div>
          <div class="mb-3">
            <label class="form-label">اللغة</label>
            <input type="text" class="form-control" id="editProductLangue" required>
          </div>
          <div class="mb-3">
            <label class="form-label">دار النشر</label>
            <input type="text" class="form-control" id="editProductPublishingHouse" required>
          </div>
          <div class="mb-3">
            <label class="form-label">ISBN</label>
            <input type="text" class="form-control" id="editProductISBN" required>
          </div>
          <div class="mb-3">
            <label class="form-label">صورة المنتج</label>
            <input type="file" class="form-control" id="editProductImage" accept="image/*">
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
        <button type="button" class="btn btn-primary" onclick="saveProduct()">حفظ التعديلات</button>
      </div>
    </div>
  </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/feather-icons/4.28.0/feather.min.js"></script>
    <script src="{{ asset('js/product.js') }}"></script> 
  </body>
</html>