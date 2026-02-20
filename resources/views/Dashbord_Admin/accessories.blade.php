<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>إدارة الإكسسوارات</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/sidebardaschboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-accessories.css') }}">
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
                        <i class="fas fa-bookmark"></i>
                        إدارة الإكسسوارات
                    </h1>
                    <button class="btn-add" data-bs-toggle="modal" data-bs-target="#addAccessoryModal">
                        <i class="fas fa-plus me-2"></i>إضافة إكسسوار جديد
                    </button>
                </div>

                <!-- Stats Cards -->
                <div class="stats-row">
                    <div class="stat-card">
                        <div class="stat-label">إجمالي الإكسسوارات</div>
                        <div class="stat-value">{{ $totalAccessories ?? 0 }}</div>
                    </div>
                    <div class="stat-card" style="border-left-color: #27ae60;">
                        <div class="stat-label">المتاحة في المخزون</div>
                        <div class="stat-value">{{ $availableAccessories ?? 0 }}</div>
                    </div>
                    <div class="stat-card" style="border-left-color: #f39c12;">
                        <div class="stat-label">إجمالي الأصناف</div>
                        <div class="stat-value">{{ $totalCategories ?? 0 }}</div>
                    </div>
                </div>

                <!-- Search and Filter Section -->
                <div class="search-section">
                    <form action="{{ route('admin.Dashbord_Admin.accessories') }}" method="GET" class="search-controls" style="width: 100%; display: flex; gap: 15px;">
                        <div class="form-group" style="flex: 1;">
                            <label for="searchInput">بحث</label>
                            <div class="input-group">
                                <input type="text" name="search" id="searchInput" class="form-control"
                                    placeholder="ابحث عن إكسسوار..." value="{{ request('search') }}">
                                <button class="btn btn-outline-primary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group" style="width: 250px;">
                            <label for="categoryFilter">الصنف</label>
                            <select name="category" id="categoryFilter" class="form-select" onchange="this.form.submit()">
                                <option value="">جميع الأصناف</option>
                                @foreach ($categories as $cat)
                                    @if($cat->parent_id == null)
                                        <option value="{{ $cat->id }}" {{ request('category') == $cat->id ? 'selected' : '' }} style="font-weight: bold;">{{ $cat->name }}</option>
                                        @foreach($cat->children as $child)
                                            <option value="{{ $child->id }}" {{ request('category') == $child->id ? 'selected' : '' }} style="padding-left: 20px;">── {{ $child->name }}</option>
                                        @endforeach
                                    @endif
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group" style="display: flex; align-items: end;">
                            <a href="{{ route('admin.Dashbord_Admin.accessories') }}" class="btn-add" style="background: #95a5a6; text-decoration: none; padding: 8px 15px; display: inline-block;">
                                <i class="fas fa-redo me-2"></i>إعادة تعيين
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Alerts -->
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                @if (session('success'))
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        {{ session('success') }}
                    </div>
                @endif

                <!-- Table Section -->
                <div class="table-section">
                    <div class="table-responsive">
                        <table class="table table-hover" id="accessoriesTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>الصورة</th>
                                    <th>الإكسسوار</th>
                                    <th>الوصف</th>
                                    <th>السعر</th>
                                    <th>الخصم</th>
                                    <th>الكمية</th>
                                    <th>الصنف</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($accessories ?? [] as $accessory)
                                <tr data-accessory-id="{{ $accessory->id }}">
                                    <td>#{{ $accessory->id }}</td>
                                    <td>
                                        <div class="product-image-cell">
                                            @if($accessory->image)
                                                <img src="{{ asset('/' . $accessory->image) }}" alt="{{ $accessory->title }}" class="product-image">
                                            @else
                                                <div style="width: 60px; height: 60px; background: #e9ecef; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                                    <i class="fas fa-bookmark" style="color: #bdc3c7;"></i>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="product-name">{{ $accessory->title }}</div>
                                    </td>
                                    <td>
                                        <div class="product-description" title="{{ $accessory->description }}">
                                            {{ Str::limit($accessory->description, 60) }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="product-price">{{ number_format($accessory->price, 2) }} د.م</div>
                                    </td>
                                    <td>
                                        @if($accessory->discount)
                                            <span class="badge bg-danger">{{ $accessory->discount }}%</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="{{ $accessory->Quantity > 0 ? 'text-success' : 'text-danger' }} fw-bold">
                                            {{ $accessory->Quantity }}
                                        </span>
                                    </td>
                                    <td>{{ $accessory->category->name ?? '-' }}</td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-action btn-view" onclick="viewAccessory({{ $accessory->id }})" title="عرض">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn-action btn-edit" onclick="editAccessory({{ $accessory->id }})" title="تعديل">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-action btn-delete" onclick="deleteAccessory({{ $accessory->id }})" title="حذف">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9">
                                        <div class="empty-state">
                                            <i class="fas fa-inbox"></i>
                                            <p>لا توجد إكسسوارات</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                @if($accessories instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <nav>
                    {{ $accessories->links('pagination::bootstrap-4') }}
                </nav>
                @endif
            </main>
        </div>
    </div>

    <!-- Add Accessory Modal -->
    <div class="modal fade" id="addAccessoryModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i>إضافة إكسسوار جديد
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addAccessoryForm" action="{{ route('admin.accessories.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label">اسم الإكسسوار</label>
                                    <input type="text" class="form-control" name="title" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">الصنف</label>
                                    <select name="category_id" class="form-select" required>
                                        <option value="">اختر صنف</option>
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
                        </div>

                        <div class="mb-3">
                            <label class="form-label">الوصف</label>
                            <textarea class="form-control" name="description" rows="3" required></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">السعر</label>
                                    <input type="number" class="form-control" name="price" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">الخصم (%)</label>
                                    <input type="number" class="form-control" name="discount" step="0.01" min="0" max="100">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">الكمية</label>
                                    <input type="number" class="form-control" name="Quantity" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">صورة الإكسسوار</label>
                            <input type="file" class="form-control" name="image" accept="image/*">
                            <small class="text-muted">الصيغ المدعومة: JPG, PNG, GIF, WebP (الحد الأقصى 2MB)</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>حفظ الإكسسوار
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Accessory Modal -->
    <div class="modal fade" id="viewAccessoryModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-bookmark me-2"></i>تفاصيل الإكسسوار
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="viewAccessoryContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">جاري التحميل...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Accessory Modal -->
    <div class="modal fade" id="editAccessoryModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>تعديل الإكسسوار
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editAccessoryForm" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <input type="hidden" id="editAccessoryId">

                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label">اسم الإكسسوار</label>
                                    <input type="text" class="form-control" id="editTitle" name="title" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">الصنف</label>
                                    <select name="category_id" id="editCategoryId" class="form-select" required>
                                        <option value="">اختر صنف</option>
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
                        </div>

                        <div class="mb-3">
                            <label class="form-label">الوصف</label>
                            <textarea class="form-control" id="editDescription" name="description" rows="3" required></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">السعر</label>
                                    <input type="number" class="form-control" id="editPrice" name="price" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">الخصم (%)</label>
                                    <input type="number" class="form-control" id="editDiscount" name="discount" step="0.01" min="0" max="100">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">الكمية</label>
                                    <input type="number" class="form-control" id="editQuantity" name="Quantity" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">صورة الإكسسوار</label>
                            <div class="mb-2">
                                <img id="editImagePreview" src="" alt="" style="max-width: 200px; border-radius: 8px; display: none;">
                            </div>
                            <input type="file" class="form-control" name="image" accept="image/*">
                            <small class="text-muted">اترك الحقل فارغاً للاحتفاظ بالصورة الحالية</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>حفظ التعديلات
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/admin-accessories.js') }}"></script>
</body>
</html>
