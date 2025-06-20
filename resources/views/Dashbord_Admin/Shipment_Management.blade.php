<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>إدارة الشحنات</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sidebardaschboard.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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
                    <h1 class="h2">إدارة الشحنات</h1>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addShipmentModal">
                        <i class="fas fa-plus"></i>
                        إضافة شحنة جديدة
                    </button>
                </div>

                <!-- Display Success/Error Messages -->
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <!-- Shipments Table -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-dark">
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
                                    <td>{{ $shipment->shipment_reference }}</td>
                                    <td>{{ $shipment->supplier_name ?? 'غير محدد' }}</td>
                                    <td>{{ \Carbon\Carbon::parse($shipment->arrival_date)->format('Y-m-d') }}</td>
                                    <td>{{ $shipment->total_books }}</td>
                                    <td>{{ $shipment->processed_books }}</td>
                                    <td>
                                        <span class="badge bg-{{ $shipment->status == 'completed' ? 'success' : ($shipment->status == 'processing' ? 'warning' : 'secondary') }}">
                                            {{ $shipment->status == 'completed' ? 'مكتملة' : ($shipment->status == 'processing' ? 'قيد المعالجة' : 'في الانتظار') }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="progress" style="width: 100px;">
                                            @php
                                                $progress = $shipment->total_books > 0 ? round(($shipment->processed_books / $shipment->total_books) * 100) : 0;
                                            @endphp
                                            <div class="progress-bar" role="progressbar" style="width: {{ $progress }}%">
                                                {{ $progress }}%
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="{{ route('shipments.show', $shipment->id) }}" class="btn btn-sm btn-outline-primary">عرض</a>
                                        @if($shipment->status == 'pending')
                                            <form method="POST" action="{{ route('shipments.process', $shipment->id) }}" style="display: inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('هل أنت متأكد من معالجة هذه الشحنة؟')">معالجة</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center">لا توجد شحنات</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <!-- Add Shipment Modal -->
    <div class="modal fade" id="addShipmentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <!-- Fixed the form action route -->
                <form method="POST" action="{{ route('shipments.store') }}" id="shipmentForm">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">إضافة شحنة جديدة</h5>
                        <button type="button" class="btn-close ms-0 me-auto" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">رقم الشحنة *</label>
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
                                    <label class="form-label">تاريخ الوصول *</label>
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

                        <hr>
                        <h6>عناصر الشحنة</h6>
                        <div id="shipmentItems">
                            <div class="shipment-item border p-3 mb-3 rounded">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label class="form-label">ISBN *</label>
                                        <input type="text" class="form-control" name="items[0][isbn]" required value="{{ old('items.0.isbn') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">عنوان الكتاب *</label>
                                        <input type="text" class="form-control" name="items[0][title]" required value="{{ old('items.0.title') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">المؤلف</label>
                                        <input type="text" class="form-control" name="items[0][author]" value="{{ old('items.0.author') }}">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">الكمية *</label>
                                        <input type="number" class="form-control" name="items[0][quantity_received]" min="1" required value="{{ old('items.0.quantity_received') }}">
                                    </div>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-4">
                                        <label class="form-label">سعر التكلفة</label>
                                        <input type="number" class="form-control" name="items[0][cost_price]" step="0.01" min="0" value="{{ old('items.0.cost_price') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">سعر البيع *</label>
                                        <input type="number" class="form-control" name="items[0][selling_price]" step="0.01" min="0" required value="{{ old('items.0.selling_price') }}">
                                    </div>
                                    <div class="col-md-4 d-flex align-items-end">
                                        <button type="button" class="btn btn-danger btn-sm remove-item" onclick="removeShipmentItem(this)">حذف</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-secondary" onclick="addShipmentItem()">إضافة كتاب آخر</button>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary" id="saveShipmentBtn">حفظ الشحنة</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let itemIndex = 1;

        function addShipmentItem() {
            const container = document.getElementById('shipmentItems');
            const newItem = document.createElement('div');
            newItem.className = 'shipment-item border p-3 mb-3 rounded';
            newItem.innerHTML = `
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">ISBN *</label>
                        <input type="text" class="form-control" name="items[${itemIndex}][isbn]" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">عنوان الكتاب *</label>
                        <input type="text" class="form-control" name="items[${itemIndex}][title]" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">المؤلف</label>
                        <input type="text" class="form-control" name="items[${itemIndex}][author]">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">الكمية *</label>
                        <input type="number" class="form-control" name="items[${itemIndex}][quantity_received]" min="1" required>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-4">
                        <label class="form-label">سعر التكلفة</label>
                        <input type="number" class="form-control" name="items[${itemIndex}][cost_price]" step="0.01" min="0">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">سعر البيع *</label>
                        <input type="number" class="form-control" name="items[${itemIndex}][selling_price]" step="0.01" min="0" required>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="button" class="btn btn-danger btn-sm remove-item" onclick="removeShipmentItem(this)">حذف</button>
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

        // Add form submission handling to prevent multiple clicks
        document.getElementById('shipmentForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('saveShipmentBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'جاري الحفظ...';
        });

        // Reset form when modal is closed
        document.getElementById('addShipmentModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('shipmentForm').reset();
            const submitBtn = document.getElementById('saveShipmentBtn');
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'حفظ الشحنة';
            
            // Reset to only one item
            const container = document.getElementById('shipmentItems');
            const items = container.querySelectorAll('.shipment-item');
            for (let i = 1; i < items.length; i++) {
                items[i].remove();
            }
            itemIndex = 1;
        });
    </script>
</body>
</html>