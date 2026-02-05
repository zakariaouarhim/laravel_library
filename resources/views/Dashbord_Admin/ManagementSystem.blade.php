<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>نظام إدارة المكتبة</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/sidebardaschboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/ManagementSystem.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            @include('Dashbord_Admin.Sidebar')

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                
                <!-- Page Header -->
                <div class="page-header">
                    <h1>
                        <i class="fa-solid fa-gears"></i>
                        نظام إدارة المكتبة
                    </h1>
                    <div class="header-actions">
                        <button class="btn-action-header secondary" onclick="showPendingEnrichment()" title="الكتب غير المعالجة بـ API">
                            <i class="fas fa-sync"></i>
                            كتب غير معالجة
                        </button>
                        <button class="btn-action-header" data-bs-toggle="modal" data-bs-target="#addProductModal" title="إضافة منتج جديد">
                            <i class="fas fa-plus"></i>
                            إضافة منتج
                        </button>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="stats-row">
                    <div class="stat-card">
                        <div class="stat-label">إجمالي المنتجات</div>
                        <div class="stat-value" id="totalProductsStat">0</div>
                    </div>
                    <div class="stat-card" style="border-left-color: #9b59b6;">
                        <div class="stat-label">معالجة بـ API</div>
                        <div class="stat-value" id="enrichedProductsStat">0</div>
                    </div>
                    <div class="stat-card" style="border-left-color: #f39c12;">
                        <div class="stat-label">في انتظار المعالجة</div>
                        <div class="stat-value" id="pendingProductsStat">0</div>
                    </div>
                </div>

                <!-- Alert Container -->
                <div id="alertContainer"></div>

                <!-- Search and Filter Section -->
                <div class="search-section">
                    <div class="search-controls">
                        <div class="form-group">
                            <label for="searchInput">بحث</label>
                            <input type="text" id="searchInput" class="form-control" placeholder="ابحث عن المنتجات...">
                        </div>
                        <div class="form-group">
                            <label for="statusFilter">حالة API</label>
                            <select id="statusFilter" class="form-select">
                                <option value="">جميع الحالات</option>
                                <option value="enriched">معالج بـ API</option>
                                <option value="pending">في انتظار المعالجة</option>
                                <option value="failed">فشل في المعالجة</option>
                            </select>
                        </div>
                        <button class="btn-filter" onclick="bulkEnrichSelected()">
                            <i class="fas fa-magic"></i>معالجة المحدد
                        </button>
                    </div>
                </div>

                <!-- Table Section -->
                <div class="table-section">
                    <div class="table-responsive">
                        <table class="table table-hover" id="productsTable">
                            <thead>
                                <tr>
                                    <th width="40">
                                        <input type="checkbox" id="selectAll" class="form-check-input">
                                    </th>
                                    <th>#</th>
                                    <th>الصورة</th>
                                    <th>المنتج</th>
                                    <th>الوصف</th>
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
                </div>

                <!-- Pagination -->
                <nav aria-label="Products pagination" class="mt-4">
                    <ul class="pagination" id="paginationContainer">
                        <!-- Pagination will be inserted here -->
                    </ul>
                </nav>
            </main>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="addproductform" method="POST" action="{{ route('admin.product.add') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-plus-circle me-2"></i>إضافة منتج جديد
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">اسم المنتج <span style="color: #e74c3c;">*</span></label>
                                    <input type="text" class="form-control" id="productName" name="productName" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">المؤلف <span style="color: #e74c3c;">*</span></label>
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
                                    <label class="form-label">السعر <span style="color: #e74c3c;">*</span></label>
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
                                    <select name="productLanguage"id="productLanguage" class="form-select"  required>
                                        @foreach(App\Models\Book::LANGUAGES as $lang)
                                            <option value="{{ $lang }}">{{ ucfirst($lang) }}</option>
                                        @endforeach
                                    </select>
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
                                    <label class="form-label">الفئة <span style="color: #e74c3c;">*</span></label>
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
                                    <label class="form-label">الكمية <span style="color: #e74c3c;">*</span></label>
                                    <input type="number" class="form-control" id="productQuantity" name="productQuantity" min="0" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">صورة المنتج</label>
                            <input type="file" class="form-control" id="productImage" accept="image/*" name="productImage">
                            <div id="imagePreview"></div>
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
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>حفظ المنتج
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Product Modal -->
    <div class="modal fade" id="editProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>تعديل المنتج
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editProductForm">
                        <input type="hidden" id="editProductId" />
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">اسم المنتج <span style="color: #e74c3c;">*</span></label>
                                    <input type="text" class="form-control" id="editProductName" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">المؤلف <span style="color: #e74c3c;">*</span></label>
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
                                    <label class="form-label">السعر <span style="color: #e74c3c;">*</span></label>
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
                                    <select name="Langue"id="editProductLanguage" class="form-select"  required>
                                        @foreach(App\Models\Book::LANGUAGES as $lang)
                                            <option value="{{ $lang }}">{{ ucfirst($lang) }}</option>
                                        @endforeach
                                    </select>
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
                                    <label class="form-label">الفئة <span style="color: #e74c3c;">*</span></label>
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
                                    <label class="form-label">الكمية <span style="color: #e74c3c;">*</span></label>
                                    <input type="number" class="form-control" id="editProductQuantity" min="0" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">صورة المنتج الجديدة</label>
                            <input type="file" class="form-control" id="editProductImage" accept="image/*">
                            <div id="editImagePreview"></div>
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
                    <button type="button" class="btn btn-primary" onclick="updateProduct()">
                        <i class="fas fa-save me-2"></i>حفظ التغييرات
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Product Modal -->
    <div class="modal fade" id="productDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-info-circle me-2"></i>تفاصيل المنتج
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="productDetailsContent">
                    <!-- Content will be inserted here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Product Modal -->
    <div class="modal fade" id="deleteProductModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-trash me-2"></i>حذف المنتج
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>هل أنت متأكد من حذف المنتج: <strong id="deleteProductName"></strong>؟</p>
                    <p class="text-danger"><small><i class="fas fa-exclamation-triangle"></i> هذا الإجراء لا يمكن التراجع عنه</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                        <i class="fas fa-trash me-2"></i>حذف النتج
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Modal -->
    <div class="modal fade" id="loadingModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">جاري التحميل...</span>
                    </div>
                    <p>جاري المعالجة...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Enrichment Preview Modal -->
    <div class="modal fade" id="enrichPreviewModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-search me-2"></i>معاينة بيانات API
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="enrichPreviewLoading" class="text-center py-4">
                        <div class="spinner-border text-primary mb-3" role="status"></div>
                        <p>جاري البحث عن بيانات الكتاب...</p>
                    </div>

                    <div id="enrichPreviewContent" style="display: none;">
                        <div class="alert alert-warning mb-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>مهم:</strong> يرجى مراجعة البيانات بعناية قبل التأكيد. تأكد من أن الكتاب المعروض هو نفس الكتاب الذي تريد إثراءه.
                        </div>

                        <div class="row mb-3">
                            <div class="col-12">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <i class="fas fa-book me-2"></i>الكتاب الحالي: <span id="previewCurrentTitle" class="text-primary"></span>
                                        </h6>
                                        <p class="mb-0">
                                            <i class="fas fa-arrow-left me-2"></i>نتيجة API: <span id="previewApiTitle" class="text-success fw-bold"></span>
                                            <span id="previewSearchMethod" class="badge bg-secondary ms-2"></span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th width="20%">الحقل</th>
                                        <th width="35%">القيمة الحالية</th>
                                        <th width="35%">قيمة API</th>
                                        <th width="10%">سيتم التحديث</th>
                                    </tr>
                                </thead>
                                <tbody id="enrichPreviewTable">
                                    <!-- Preview rows will be inserted here -->
                                </tbody>
                            </table>
                        </div>

                        <div id="previewImageSection" class="row mt-3" style="display: none;">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-secondary text-white">الصورة الحالية</div>
                                    <div class="card-body text-center">
                                        <img id="previewCurrentImage" src="" alt="Current" style="max-height: 150px; object-fit: contain;">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-success text-white">صورة API</div>
                                    <div class="card-body text-center">
                                        <img id="previewApiImage" src="" alt="API" style="max-height: 150px; object-fit: contain;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="enrichPreviewError" class="alert alert-danger" style="display: none;">
                        <i class="fas fa-times-circle me-2"></i>
                        <span id="enrichPreviewErrorMessage"></span>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="hidden" id="enrichPreviewBookId">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>إلغاء
                    </button>
                    <button type="button" class="btn btn-danger" id="btnRejectEnrichment" onclick="rejectEnrichment()" style="display: none;">
                        <i class="fas fa-ban me-2"></i>رفض البيانات
                    </button>
                    <button type="button" class="btn btn-success" id="btnConfirmEnrichment" onclick="confirmEnrichment()" style="display: none;">
                        <i class="fas fa-check me-2"></i>تأكيد وتطبيق البيانات
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/ManagementSystem.js') }}"></script>
</body>
</html>