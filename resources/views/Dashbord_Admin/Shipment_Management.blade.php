<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>إدارة الشحنات</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/sidebardaschboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/dashboardShipment_Management.css') }}">
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
                        <i class="fas fa-truck"></i>
                        إدارة الشحنات
                    </h1>
                    <button class="btn-add" data-bs-toggle="modal" data-bs-target="#addShipmentModal">
                        <i class="fas fa-plus me-2"></i>إضافة شحنة جديدة
                    </button>
                </div>

                <!-- Stats Cards -->
                <div class="stats-row">
                    <div class="stat-card">
                        <div class="stat-label">إجمالي الشحنات</div>
                        <div class="stat-value">{{ $shipments->count() ?? 0 }}</div>
                    </div>
                    <div class="stat-card" style="border-left-color: #f39c12;">
                        <div class="stat-label">قيد المعالجة</div>
                        <div class="stat-value">{{ $shipments->where('status', 'processing')->count() ?? 0 }}</div>
                    </div>
                    <div class="stat-card" style="border-left-color: #27ae60;">
                        <div class="stat-label">مكتملة</div>
                        <div class="stat-value">{{ $shipments->where('status', 'completed')->count() ?? 0 }}</div>
                    </div>
                </div>

                <!-- Alerts -->
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                <!-- search Section -->
                <div class="search-section">
                <form action="{{ route('admin.shipments.search') }}" method="GET" class="search-controls" style="width: 100%; display: flex; gap: 15px;">
                        
                        <div class="form-group" style="flex: 1;">
                            <label for="searchInput">بحث</label>
                            <div class="input-group">
                                <input 
                                    type="text" 
                                    name="search" 
                                    id="searchInput" 
                                    class="form-control" 
                                    placeholder="ابحث عن اسم أو رقم الشحنة..."
                                    value="{{ request('search') }}" 
                                >
                                <button class="btn btn-outline-primary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="form-group" style="display: flex; align-items: end;">
                            <a href="{{ route('admin.Dashbord_Admin.Shipment_Management') }}" class="btn-add" style="background: #95a5a6; text-decoration: none; padding: 8px 15px; display: inline-block;">
                                <i class="fas fa-redo me-2"></i>إعادة تعيين
                            </a>
                        </div>
                </form>
                </div>         
                <!-- Table Section -->
                <div class="table-section">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>رقم الشحنة</th>
                                    <th>المورد</th>
                                    <th>تاريخ الوصول</th>
                                    <th>إجمالي الكتب</th>
                                    <th>المعالج</th>
                                    <th>الحالة</th>
                                    <th>التقدم</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($shipments ?? [] as $shipment)
                                <tr>
                                    <td>
                                        <span class="shipment-reference">{{ $shipment->shipment_reference }}</span>
                                    </td>
                                    <td>
                                        <span class="supplier-name">{{ $shipment->supplier_name ?? 'غير محدد' }}</span>
                                    </td>
                                    <td>{{ \Carbon\Carbon::parse($shipment->arrival_date)->format('d-m-Y') }}</td>
                                    <td><strong>{{ $shipment->total_books }}</strong></td>
                                    <td>{{ $shipment->processed_books ?? 0 }}</td>
                                    <td>
                                        @php
                                            $statusClass = $shipment->status == 'completed' ? 'status-completed' : ($shipment->status == 'processing' ? 'status-processing' : 'status-pending');
                                            $statusText = $shipment->status == 'completed' ? 'مكتملة' : ($shipment->status == 'processing' ? 'قيد المعالجة' : 'في الانتظار');
                                        @endphp
                                        <span class="status-badge {{ $statusClass }}">{{ $statusText }}</span>
                                    </td>
                                    <td>
                                        <div class="progress-container">
                                            @php
                                                $progress = $shipment->total_books > 0 ? round(($shipment->processed_books / $shipment->total_books) * 100) : 0;
                                            @endphp
                                            <div class="progress">
                                                <div class="progress-bar" style="width: {{ $progress }}%">
                                                    {{ $progress }}%
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="{{ route('admin.shipments.show', $shipment->id) }}" class="btn-action btn-view">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button 
                                                class="btn-action btn-edit" 
                                                onclick="editProduct({{ $shipment->id }})"
                                                title="تعديل"
                                            >
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button 
                                                class="btn-action btn-delete" 
                                                onclick="deleteProduct({{ $shipment->id }})"
                                                title="حذف"
                                            >
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            @if($shipment->status == 'pending')
                                                <form method="POST" action="{{ route('admin.shipments.process', $shipment->id) }}" style="display: inline;">
                                                    @csrf
                                                    <button type="submit" class="btn-action btn-process" onclick="return confirm('هل أنت متأكد من معالجة هذه الشحنة؟')">
                                                        <i class="fas fa-cog"></i>معالجة
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8">
                                        <div class="empty-state">
                                            <i class="fas fa-inbox"></i>
                                            <p>لا توجد شحنات</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- Pagination -->
                @if($shipments instanceof \Illuminate\Pagination\Paginator || $shipments instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <nav>
                    {{ $shipments->links('pagination::bootstrap-4') }}
                </nav>
                @endif
            </main>
        </div>
    </div>

    <!-- Add Shipment Modal -->
    <div class="modal fade" id="addShipmentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('admin.shipments.store') }}" id="shipmentForm">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-plus-circle me-2"></i>إضافة شحنة جديدة
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <!-- PHASE 1: Shipment Header Section -->
                        <div class="mb-4">
                            <h6 style="color: #2c3e50; font-weight: 600; margin-bottom: 1rem;">معلومات الشحنة</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">رقم الشحنة <span style="color: #e74c3c;">*</span></label>
                                        <input type="text" class="form-control" name="shipment_reference" id="shipmentReference" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">المورد</label>
                                        <input type="text" class="form-control" name="supplier_name" id="supplierName">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">تاريخ الوصول <span style="color: #e74c3c;">*</span></label>
                                        <input type="date" class="form-control" name="arrival_date" id="arrivalDate" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">ملاحظات</label>
                                        <textarea class="form-control" name="notes" id="notes" rows="2"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- PHASE 2: Book Search & Add Section -->
                        <div class="mb-4">
                            <h6 style="color: #2c3e50; font-weight: 600; margin-bottom: 1rem;">إضافة الكتب</h6>
                            
                            <!-- Search Phase -->
                            <div class="search-phase mb-4" id="searchPhase">
                                <div class="mb-3">
                                    <label class="form-label">البحث عن الكتاب <span style="color: #e74c3c;">*</span></label>
                                    <div class="input-group">
                                        <input 
                                            type="text" 
                                            class="form-control" 
                                            id="bookSearchInput" 
                                            placeholder="ابحث عن ISBN أو اسم الكتاب..."
                                            autocomplete="off"
                                        >
                                        <button class="btn btn-outline-primary" type="button" id="searchBtn">
                                            <i class="fas fa-search"></i>بحث
                                        </button>
                                    </div>
                                    <small class="form-text text-muted">ابحث بـ ISBN (أسرع) أو اسم الكتاب أو المؤلف</small>
                                </div>

                                <!-- Search Results -->
                                <div id="searchResults" style="display: none; max-height: 300px; overflow-y: auto;">
                                    <div class="list-group">
                                        <!-- Results will appear here -->
                                    </div>
                                </div>

                                <!-- Existing Book Info -->
                                <div id="existingBookInfo" class="alert alert-info" style="display: none;">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <p><strong>الكتاب:</strong> <span id="existingBookTitle"></span></p>
                                            <p><strong>ISBN:</strong> <span id="existingBookISBN"></span></p>
                                            <p><strong>المؤلف:</strong> <span id="existingBookAuthor"></span></p>
                                            <p><strong>الكمية الحالية:</strong> <span id="existingBookQuantity" class="badge bg-success"></span></p>
                                            <p><strong>السعر الحالي:</strong> <span id="existingBookPrice" class="badge bg-primary"></span> DH</p>
                                        </div>
                                        <div class="col-md-4 d-flex align-items-center justify-content-end gap-2">
                                            <button type="button" class="btn btn-success btn-sm" id="selectExistingBtn">
                                                <i class="fas fa-check"></i>اختيار
                                            </button>
                                            <button type="button" class="btn btn-secondary btn-sm" id="cancelExistingBtn">
                                                <i class="fas fa-times"></i>إلغاء
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- New Book Form -->
                                <div id="newBookForm" style="display: none;">
                                    <div class="alert alert-warning">
                                        <p><i class="fas fa-info-circle"></i> كتاب جديد - أكمل البيانات المطلوبة</p>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">ISBN <span style="color: #e74c3c;">*</span></label>
                                                <input type="text" class="form-control" id="newBookISBN" placeholder="ISBN">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">عنوان الكتاب <span style="color: #e74c3c;">*</span></label>
                                                <input type="text" class="form-control" id="newBookTitle" placeholder="العنوان">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">المؤلف</label>
                                                <div class="input-group">
                                                    <input 
                                                        type="text" 
                                                        class="form-control" 
                                                        id="newBookAuthorSearch" 
                                                        placeholder="ابحث عن المؤلف..."
                                                        autocomplete="off"
                                                    >
                                                    <button class="btn btn-outline-secondary" type="button" id="searchAuthorBtn">
                                                        <i class="fas fa-search"></i>
                                                    </button>
                                                </div>
                                                <div id="authorSearchResults" class="list-group mt-2" style="display: none; max-height: 150px; overflow-y: auto;">
                                                    <!-- Author results here -->
                                                </div>
                                                <input type="hidden" id="newBookAuthorId">
                                                <small id="selectedAuthorName" class="text-muted"></small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">دار النشر</label>
                                                <div class="input-group">
                                                    <input 
                                                        type="text" 
                                                        class="form-control" 
                                                        id="newBookPublisherSearch" 
                                                        placeholder="ابحث عن دار النشر..."
                                                        autocomplete="off"
                                                    >
                                                    <button class="btn btn-outline-secondary" type="button" id="searchPublisherBtn">
                                                        <i class="fas fa-search"></i>
                                                    </button>
                                                </div>
                                                <div id="publisherSearchResults" class="list-group mt-2" style="display: none; max-height: 150px; overflow-y: auto;">
                                                    <!-- Publisher results here -->
                                                </div>
                                                <input type="hidden" id="newBookPublisherId">
                                                <small id="selectedPublisherName" class="text-muted"></small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <button type="button" class="btn btn-success" id="proceedNewBookBtn">
                                                <i class="fas fa-check"></i>متابعة
                                            </button>
                                            <button type="button" class="btn btn-warning btn-sm" id="cancelNewBookBtn">
                                                <i class="fas fa-arrow-left"></i>رجوع
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Item Details Phase -->
                            <div class="item-details-phase" id="itemDetailsPhase" style="display: none;">
                                <div class="alert alert-primary">
                                    <p><strong id="itemBookTitle"></strong></p>
                                    <p id="itemBookDetails" style="font-size: 0.9rem; margin-bottom: 0;"></p>
                                </div>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">الكمية <span style="color: #e74c3c;">*</span></label>
                                            <input type="number" class="form-control" id="itemQuantity" min="1" required value="1">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">سعر التكلفة</label>
                                            <input type="number" class="form-control" id="itemCostPrice" step="0.01" min="0">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="mb-3">
                                            <label class="form-label">سعر البيع <span style="color: #e74c3c;">*</span></label>
                                            <input type="number" class="form-control" id="itemSellingPrice" step="0.01" min="0" >
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-success" id="addItemBtn">
                                        <i class="fas fa-plus"></i>إضافة للشحنة
                                    </button>
                                    <button type="button" class="btn btn-secondary" id="backToSearchBtn">
                                        <i class="fas fa-arrow-left"></i>رجوع
                                    </button>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Shipment Items List -->
                        <div class="mb-4">
                            <h6 style="color: #2c3e50; font-weight: 600; margin-bottom: 1rem;">الكتب المضافة</h6>
                            <div id="shipmentItemsList">
                                <p class="text-muted text-center" id="emptyItemsMessage">لم تضف أي كتب حتى الآن</p>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer with Hidden Form Fields -->
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary" id="saveShipmentBtn" disabled>
                            <i class="fas fa-save me-2"></i>حفظ الشحنة
                        </button>
                    </div>

                    <!-- Hidden container for item data -->
                    <div id="itemsDataContainer"></div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/dashboardShipment.js') }}"></script> 
</body>
</html>
             