<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>المنتجات - نظام إدارة المكتبة</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sidebardaschboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/product.css') }}">
    <link rel="stylesheet" href="{{ asset('css/ManagementSystem.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    
    <!-- Navbar -->
    

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            @include('Dashbord_Admin.Sidebar')

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">إدارة المنتجات</h1>
                    <div>
                        
                        <button class="btn btn-info me-2" onclick="showPendingEnrichment()">
                            <i class="fas fa-sync"></i>
                            الكتب غير المعالجة بـ API
                        </button>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                            <i class="fas fa-plus"></i>
                            إضافة منتج جديد
                        </button>
                    </div>
                </div>

                <!-- Alert for messages -->
                <div id="alertContainer"></div>

                <!-- Search and Filter -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <input type="text" id="searchInput" class="form-control" placeholder="بحث عن المنتجات...">
                    </div>
                    <div class="col-md-3">
                        <select id="statusFilter" class="form-select">
                            <option value="">جميع الحالات</option>
                            <option value="enriched">معالج بـ API</option>
                            <option value="pending">في انتظار المعالجة</option>
                            <option value="failed">فشل في المعالجة</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-warning" onclick="bulkEnrichSelected()">
                            <i class="fas fa-magic"></i>
                            معالجة المحدد بـ API
                        </button>
                    </div>
                </div>

                <!-- Products Table -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle" id="productsTable">
                        <thead class="table-dark">
                            <tr>
                                <th>
                                    <input type="checkbox" id="selectAll">
                                </th>
                                <th>#</th>
                                <th>الصورة</th>
                                <th>اسم المنتج</th>
                                <th style="max-width: 300px;">الوصف</th>
                                <th>السعر</th>
                                <th>المؤلف</th>
                                <th>الكمية</th>
                                <th>ISBN</th>
                                <th>حالة API</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody id="productsTableBody">
                            <!-- Rows will be dynamically inserted here -->
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <nav aria-label="Products pagination" class="mt-4">
                    <ul class="pagination justify-content-center" id="paginationContainer">
                        <!-- Pagination will be inserted here -->
                    </ul>
                </nav>
            </main>
        </div>
    </div>

    <!-- Add Product Modal (Enhanced) -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="addproductform" method="POST" action="{{ route('product.add') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">إضافة منتج جديد</h5>
                        <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">اسم المنتج *</label>
                                    <input type="text" class="form-control" id="productName" name="productName" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">المؤلف *</label>
                                    <input type="text" class="form-control" id="productauthor" name="productauthor" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">الوصف</label>
                            <textarea class="form-control" id="productDescription" rows="3" name="productDescription" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">السعر *</label>
                                    <input type="number" class="form-control" id="productPrice" step="0.01" name="productPrice" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">عدد الصفحات</label>
                                    <input type="number" class="form-control" id="productNumPages" name="productNumPages">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">اللغة</label>
                                    <input type="text" class="form-control" id="productLanguage" name="productLanguage">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">دار النشر</label>
                                    <input type="text" class="form-control" id="ProductPublishingHouse" name="ProductPublishingHouse">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">ISBN</label>
                                    <input type="text" class="form-control" id="productIsbn" name="productIsbn">
                                    <div class="form-text">سيتم استخدام ISBN لجلب بيانات إضافية من API</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">الفئة *</label>
                                    <select name="Productcategorie" id="Productcategorie" class="form-select" required>
                                        @foreach ($categories as $cat)
                                            @if($cat->parent_id == null)
                                                <option value="{{ $cat->id }}" style="font-weight: bold;">{{ $cat->name }}</option>
                                                @foreach($cat->children as $child)
                                                    <option value="{{ $child->id }}" style="padding-left: 20px;">── {{ $child->name }}</option>
                                                @endforeach
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">الكمية *</label>
                                    <input type="number" class="form-control" id="productQuantity" name="productQuantity" min="0" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">صورة المنتج</label>
                            <input type="file" class="form-control" id="productImage" accept="image/*" name="productImage">
                            <div class="form-text">اختياري - سيتم جلب الصورة من API إذا لم يتم رفعها</div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="autoEnrich" name="auto_enrich" checked>
                                <label class="form-check-label" for="autoEnrich">
                                    إثراء تلقائي من API بعد الإضافة
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">حفظ المنتج</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal (Enhanced) -->
    <div class="modal fade" id="editProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تعديل المنتج</h5>
                    <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editProductForm">
                        <input type="hidden" id="editProductId" />
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">اسم المنتج *</label>
                                    <input type="text" class="form-control" id="editProductName" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">المؤلف *</label>
                                    <input type="text" class="form-control" id="editProductAuthor" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">الوصف</label>
                            <textarea class="form-control" id="editProductDescription" rows="3" required></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">السعر *</label>
                                    <input type="number" class="form-control" id="editProductPrice" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">عدد الصفحات</label>
                                    <input type="number" class="form-control" id="editProductNumPages">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">اللغة</label>
                                    <input type="text" class="form-control" id="editProductLanguage">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">دار النشر</label>
                                    <input type="text" class="form-control" id="editProductPublishingHouse">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">ISBN</label>
                                    <input type="text" class="form-control" id="editProductIsbn">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">الفئة *</label>
                                    <select id="editProductCategorie" class="form-select" required>
                                        <option value="">اختر الفئة</option>
                                        @foreach ($categories as $cat)
                                            @if($cat->parent_id == null)
                                                <option value="{{ $cat->id }}" style="font-weight: bold;">{{ $cat->name }}</option>
                                                @foreach($cat->children as $child)
                                                    <option value="{{ $child->id }}" style="padding-left: 20px;">── {{ $child->name }}</option>
                                                @endforeach
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">الكمية *</label>
                                    <input type="number" class="form-control" id="editProductQuantity" min="0" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">صورة المنتج الجديدة</label>
                            <input type="file" class="form-control" id="editProductImage" accept="image/*">
                            <div class="form-text">اختياري - اتركه فارغاً للاحتفاظ بالصورة الحالية</div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="editAutoEnrich">
                                <label class="form-check-label" for="editAutoEnrich">
                                    إثراء تلقائي من API بعد التحديث
                                </label>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" class="btn btn-primary" onclick="updateProduct()">حفظ التغييرات</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteProductModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تأكيد الحذف</h5>
                    <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>هل أنت متأكد من حذف هذا المنتج؟ لا يمكن التراجع عن هذا الإجراء.</p>
                    <p class="text-muted">المنتج: <span id="deleteProductName"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" class="btn btn-danger" onclick="confirmDelete()">حذف</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Product Details Modal -->
    <div class="modal fade" id="productDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تفاصيل المنتج</h5>
                    <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="productDetailsContent">
                    <!-- Product details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Modal -->
    <div class="modal fade" id="loadingModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-sm modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">جاري التحميل...</span>
                    </div>
                    <p class="mt-2 mb-0">جاري المعالجة...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.0.2/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="{{ asset('js/ManagementSystem.js') }}"></script> 
    

    
</body>
</html>