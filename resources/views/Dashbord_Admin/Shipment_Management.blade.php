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
                                            <a href="{{ route('shipments.show', $shipment->id) }}" class="btn-action btn-view">
                                                <i class="fas fa-eye"></i>عرض
                                            </a>
                                            @if($shipment->status == 'pending')
                                                <form method="POST" action="{{ route('shipments.process', $shipment->id) }}" style="display: inline;">
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
            </main>
        </div>
    </div>

    <!-- Add Shipment Modal -->
    <div class="modal fade" id="addShipmentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="{{ route('shipments.store') }}" id="shipmentForm">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-plus-circle me-2"></i>إضافة شحنة جديدة
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Header Section -->
                        <div class="mb-4">
                            <h6 style="color: #2c3e50; font-weight: 600; margin-bottom: 1rem;">معلومات الشحنة</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">رقم الشحنة <span style="color: #e74c3c;">*</span></label>
                                        <input type="text" class="form-control" name="shipment_reference" required value="{{ old('shipment_reference') }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">المورد</label>
                                        <input type="text" class="form-control" name="supplier_name" value="{{ old('supplier_name') }}">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">تاريخ الوصول <span style="color: #e74c3c;">*</span></label>
                                        <input type="date" class="form-control" name="arrival_date" required value="{{ old('arrival_date') }}">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">ملاحظات</label>
                                        <textarea class="form-control" name="notes" rows="2">{{ old('notes') }}</textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <!-- Items Section -->
                        <div class="mb-4">
                            <h6 style="color: #2c3e50; font-weight: 600; margin-bottom: 1rem;">عناصر الشحنة</h6>
                            <div id="shipmentItems">
                                <div class="shipment-item">
                                    <div class="item-header">
                                        <span class="item-title">
                                            <i class="fas fa-book"></i>الكتاب #1
                                        </span>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">ISBN <span style="color: #e74c3c;">*</span></label>
                                                <input type="text" class="form-control" name="items[0][isbn]" required value="{{ old('items.0.isbn') }}">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">عنوان الكتاب <span style="color: #e74c3c;">*</span></label>
                                                <input type="text" class="form-control" name="items[0][title]" required value="{{ old('items.0.title') }}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">المؤلف</label>
                                                <input type="text" class="form-control" name="items[0][author]" value="{{ old('items.0.author') }}">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">الكمية <span style="color: #e74c3c;">*</span></label>
                                                <input type="number" class="form-control" name="items[0][quantity_received]" min="1" required value="{{ old('items.0.quantity_received') }}">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <label class="form-label">سعر التكلفة</label>
                                                <input type="number" class="form-control" name="items[0][cost_price]" step="0.01" min="0" value="{{ old('items.0.cost_price') }}">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="mb-3">
                                                <label class="form-label">سعر البيع <span style="color: #e74c3c;">*</span></label>
                                                <input type="number" class="form-control" name="items[0][selling_price]" step="0.01" min="0" required value="{{ old('items.0.selling_price') }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="add-item-btn" onclick="addShipmentItem()">
                                <i class="fas fa-plus me-2"></i>إضافة كتاب آخر
                            </button>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary" id="saveShipmentBtn">
                            <i class="fas fa-save me-2"></i>حفظ الشحنة
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let itemIndex = 1;

        function addShipmentItem() {
            const container = document.getElementById('shipmentItems');
            const newItem = document.createElement('div');
            newItem.className = 'shipment-item';
            newItem.innerHTML = `
                <div class="item-header">
                    <span class="item-title">
                        <i class="fas fa-book"></i>الكتاب #${itemIndex + 1}
                    </span>
                    <button type="button" class="remove-item-btn" onclick="removeShipmentItem(this)">
                        <i class="fas fa-trash me-1"></i>حذف
                    </button>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">ISBN <span style="color: #e74c3c;">*</span></label>
                            <input type="text" class="form-control" name="items[${itemIndex}][isbn]" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">عنوان الكتاب <span style="color: #e74c3c;">*</span></label>
                            <input type="text" class="form-control" name="items[${itemIndex}][title]" required>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">المؤلف</label>
                            <input type="text" class="form-control" name="items[${itemIndex}][author]">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">الكمية <span style="color: #e74c3c;">*</span></label>
                            <input type="number" class="form-control" name="items[${itemIndex}][quantity_received]" min="1" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">سعر التكلفة</label>
                            <input type="number" class="form-control" name="items[${itemIndex}][cost_price]" step="0.01" min="0">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label class="form-label">سعر البيع <span style="color: #e74c3c;">*</span></label>
                            <input type="number" class="form-control" name="items[${itemIndex}][selling_price]" step="0.01" min="0" required>
                        </div>
                    </div>
                </div>
            `;
            container.appendChild(newItem);
            itemIndex++;
        }

        function removeShipmentItem(button) {
            const items = document.querySelectorAll('.shipment-item');
            if (items.length > 1) {
                button.closest('.shipment-item').remove();
            } else {
                alert('يجب أن تحتوي الشحنة على كتاب واحد على الأقل');
            }
        }

        document.getElementById('shipmentForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('saveShipmentBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>جاري الحفظ...';
        });

        document.getElementById('addShipmentModal').addEventListener