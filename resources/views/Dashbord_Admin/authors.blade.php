<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>إدارة المؤلفين</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/sidebardaschboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/authors.css') }}">
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
                        <i class="fa-solid fa-feather-pointed"></i>
                        إدارة المؤلفين
                    </h1>
                    <div class="header-actions">
                        <button class="btn-action-header secondary" onclick="importFromBooks()" title="استيراد المؤلفين من الكتب">
                            <i class="fas fa-file-import"></i>
                            استيراد من الكتب
                        </button>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="stats-row">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-users"></i></div>
                        <div class="stat-info">
                            <div class="stat-label">إجمالي المؤلفين</div>
                            <div class="stat-value" id="totalAuthorsStat">0</div>
                        </div>
                    </div>
                    <div class="stat-card" style="border-left-color: #27ae60;">
                        <div class="stat-icon" style="color: #27ae60;"><i class="fas fa-check-circle"></i></div>
                        <div class="stat-info">
                            <div class="stat-label">نشط</div>
                            <div class="stat-value" id="activeAuthorsStat">0</div>
                        </div>
                    </div>
                    <div class="stat-card" style="border-left-color: #9b59b6;">
                        <div class="stat-icon" style="color: #9b59b6;"><i class="fas fa-magic"></i></div>
                        <div class="stat-info">
                            <div class="stat-label">معالج بـ API</div>
                            <div class="stat-value" id="enrichedAuthorsStat">0</div>
                        </div>
                    </div>
                    <div class="stat-card" style="border-left-color: #f39c12;">
                        <div class="stat-icon" style="color: #f39c12;"><i class="fas fa-clock"></i></div>
                        <div class="stat-info">
                            <div class="stat-label">في انتظار المعالجة</div>
                            <div class="stat-value" id="pendingAuthorsStat">0</div>
                        </div>
                    </div>
                </div>

                <!-- Alert Container -->
                <div id="alertContainer"></div>

                <!-- Search and Filter Section -->
                <div class="search-section">
                    <div class="search-controls">
                        <div class="form-group">
                            <label for="searchInput">بحث</label>
                            <input type="text" id="searchInput" class="form-control" placeholder="ابحث بالاسم أو الجنسية...">
                        </div>
                        <div class="form-group">
                            <label for="statusFilter">الحالة</label>
                            <select id="statusFilter" class="form-select">
                                <option value="">جميع الحالات</option>
                                <option value="active">نشط</option>
                                <option value="inactive">غير نشط</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="apiStatusFilter">حالة API</label>
                            <select id="apiStatusFilter" class="form-select">
                                <option value="">الكل</option>
                                <option value="enriched">معالج</option>
                                <option value="pending">غير معالج</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Table Section -->
                <div class="table-section">
                    <div class="table-responsive">
                        <table class="table table-hover" id="authorsTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>الصورة</th>
                                    <th>الاسم</th>
                                    <th>الجنسية</th>
                                    <th>تاريخ الميلاد</th>
                                    <th>عدد الكتب</th>
                                    <th>حالة API</th>
                                    <th>الحالة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody id="authorsTableBody">
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        <div class="spinner-border text-primary" role="status"></div>
                                        <p class="mt-2">جاري التحميل...</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <nav aria-label="Authors pagination" class="mt-4">
                    <ul class="pagination" id="paginationContainer"></ul>
                </nav>
            </main>
        </div>
    </div>

    <!-- View Author Modal -->
    <div class="modal fade" id="viewAuthorModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user me-2"></i>تفاصيل المؤلف</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="viewAuthorContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Author Modal -->
    <div class="modal fade" id="editAuthorModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>تعديل المؤلف</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editAuthorForm">
                        <input type="hidden" id="editAuthorId">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">الاسم <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="editAuthorName" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">الجنسية</label>
                                    <input type="text" class="form-control" id="editAuthorNationality">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">السيرة الذاتية</label>
                            <textarea class="form-control" id="editAuthorBiography" rows="4"></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">تاريخ الميلاد</label>
                                    <input type="date" class="form-control" id="editAuthorBirthDate">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">تاريخ الوفاة</label>
                                    <input type="date" class="form-control" id="editAuthorDeathDate">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">الحالة <span class="text-danger">*</span></label>
                                    <select class="form-select" id="editAuthorStatus" required>
                                        <option value="active">نشط</option>
                                        <option value="inactive">غير نشط</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">الموقع الإلكتروني</label>
                            <input type="url" class="form-control" id="editAuthorWebsite" placeholder="https://">
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" class="btn btn-primary" onclick="saveAuthor()">
                        <i class="fas fa-save me-2"></i>حفظ التغييرات
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Author Modal -->
    <div class="modal fade" id="deleteAuthorModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-trash me-2"></i>حذف المؤلف</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>هل أنت متأكد من حذف المؤلف: <strong id="deleteAuthorName"></strong>؟</p>
                    <p class="text-danger"><small><i class="fas fa-exclamation-triangle"></i> هذا الإجراء لا يمكن التراجع عنه</small></p>
                </div>
                <div class="modal-footer">
                    <input type="hidden" id="deleteAuthorId">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                        <i class="fas fa-trash me-2"></i>حذف
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Enrichment Preview Modal -->
    <div class="modal fade" id="enrichPreviewModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="fas fa-magic me-2"></i>معاينة بيانات Open Library</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="enrichPreviewLoading" class="text-center py-4">
                        <div class="spinner-border text-primary mb-3"></div>
                        <p>جاري البحث عن بيانات المؤلف...</p>
                    </div>

                    <div id="enrichPreviewContent" style="display: none;">
                        <div class="alert alert-warning mb-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>مهم:</strong> يرجى التأكد من أن البيانات المعروضة تخص المؤلف الصحيح قبل التطبيق.
                        </div>

                        <div class="card bg-light mb-3">
                            <div class="card-body">
                                <h6><i class="fas fa-user me-2"></i>المؤلف الحالي: <span id="previewCurrentName" class="text-primary"></span></h6>
                                <p class="mb-0"><i class="fas fa-arrow-left me-2"></i>نتيجة API: <span id="previewApiName" class="text-success fw-bold"></span></p>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th width="5%"><input type="checkbox" id="selectAllFields" class="form-check-input" checked></th>
                                        <th width="20%">الحقل</th>
                                        <th width="35%">القيمة الحالية</th>
                                        <th width="35%">قيمة API</th>
                                    </tr>
                                </thead>
                                <tbody id="enrichPreviewTable"></tbody>
                            </table>
                        </div>

                        <div id="previewImageSection" class="row mt-3" style="display: none;">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-secondary text-white">الصورة الحالية</div>
                                    <div class="card-body text-center">
                                        <img id="previewCurrentImage" src="" alt="" style="max-height: 150px; object-fit: contain;">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-success text-white">صورة API</div>
                                    <div class="card-body text-center">
                                        <img id="previewApiImage" src="" alt="" style="max-height: 150px; object-fit: contain;">
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
                    <input type="hidden" id="enrichPreviewAuthorId">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="button" class="btn btn-success" id="btnConfirmEnrichment" onclick="confirmEnrichment()" style="display: none;">
                        <i class="fas fa-check me-2"></i>تأكيد وتطبيق البيانات
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Import Results Modal -->
    <div class="modal fade" id="importResultsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-file-import me-2"></i>نتائج الاستيراد</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="importResultsContent">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary"></div>
                        <p class="mt-2">جاري الاستيراد...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Modal -->
    <div class="modal fade" id="loadingModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-body text-center">
                    <div class="spinner-border text-primary mb-3"></div>
                    <p>جاري المعالجة...</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/authors.js') }}"></script>
</body>
</html>
