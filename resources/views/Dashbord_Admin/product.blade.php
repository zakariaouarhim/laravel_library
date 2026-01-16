<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>إدارة المنتجات</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/sidebardaschboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/product.css') }}">
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
                        <i class="fas fa-box"></i>
                        إدارة المنتجات
                    </h1>
                    <button class="btn-add" data-bs-toggle="modal" data-bs-target="#addProductModal">
                        <i class="fas fa-plus me-2"></i>إضافة منتج جديد
                    </button>
                </div>

                <!-- Stats Cards -->
                <div class="stats-row">
                    <div class="stat-card">
                        <div class="stat-label">إجمالي المنتجات</div>
                        <div class="stat-value">{{ $totalProducts ?? 0 }}</div>
                    </div>
                    <div class="stat-card" style="border-left-color: #27ae60;">
                        <div class="stat-label">المنتجات المتاحة</div>
                        <div class="stat-value">{{ $availableProducts ?? 0 }}</div>
                    </div>
                    <div class="stat-card" style="border-left-color: #f39c12;">
                        <div class="stat-label">إجمالي الفئات</div>
                        <div class="stat-value">{{ $totalCategories ?? 0 }}</div>
                    </div>
                </div>

                <!-- Search and Filter Section -->
                <div class="search-section">
                    <form action="{{ route('admin.products.index') }}" method="GET" class="search-controls" style="width: 100%; display: flex; gap: 15px;">
                        
                        <div class="form-group" style="flex: 1;">
                            <label for="searchInput">بحث</label>
                            <div class="input-group">
                                <input 
                                    type="text" 
                                    name="search" 
                                    id="searchInput" 
                                    class="form-control" 
                                    placeholder="ابحث عن اسم أو مؤلف..."
                                    value="{{ request('search') }}" 
                                >
                                <button class="btn btn-outline-primary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group" style="width: 250px;">
                            <label for="categoryFilter">الفئة</label>
                            <select name="category" id="categoryFilter" class="form-select" onchange="this.form.submit()">
                                <option value="">جميع الفئات</option>
                                @foreach ($categories as $c)
                                    <option value="{{ $c->id }}" {{ request('category') == $c->id ? 'selected' : '' }}>
                                        {{ $c->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group" style="display: flex; align-items: end;">
                            <a href="{{ route('admin.products.index') }}" class="btn-add" style="background: #95a5a6; text-decoration: none; padding: 8px 15px; display: inline-block;">
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
                        <table class="table table-hover" id="productsTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>الصورة</th>
                                    <th>المنتج</th>
                                    <th>الوصف</th>
                                    <th>المؤلف</th>
                                    <th>السعر</th>
                                    <th>الصفحات</th>
                                    <th>اللغة</th>
                                    <th>ISBN</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($products ?? [] as $product)
                                <tr data-product-id="{{ $product->id }}">
                                    <td>#{{ $product->id }}</td>
                                    <td>
                                        <div class="product-image-cell">
                                            @if($product->image)
                                                <img src="{{ asset('/' . $product->image) }}" alt="{{ $product->title }}" class="product-image">
                                            @else
                                                <div style="width: 60px; height: 80px; background: #e9ecef; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                                    <i class="fas fa-book" style="color: #bdc3c7;"></i>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="product-name">{{ $product->title }}</div>
                                    </td>
                                    <td>
                                        <div class="product-description" title="{{ $product->description }}">
                                            {{ $product->description }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="product-author">{{ $product->author }}</div>
                                    </td>
                                    <td>
                                        <div class="product-price">{{ number_format($product->price, 2) }} ر.س</div>
                                    </td>
                                    <td>{{ $product->Page_Num ?? '-' }}</td>
                                    <td>{{ $product->Langue ?? '-' }}</td>
                                    <td>{{ $product->ISBN ?? '-' }}</td>
                                    <td>
                                        <div class="action-buttons">
                                            <button 
                                                class="btn-action btn-view" 
                                                onclick="viewProduct({{ $product->id }})"
                                                title="عرض التفاصيل"
                                            >
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button 
                                                class="btn-action btn-edit" 
                                                onclick="editProduct({{ $product->id }})"
                                                title="تعديل"
                                            >
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button 
                                                class="btn-action btn-delete" 
                                                onclick="deleteProduct({{ $product->id }})"
                                                title="حذف"
                                            >
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="10">
                                        <div class="empty-state">
                                            <i class="fas fa-inbox"></i>
                                            <p>لا توجد منتجات</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                @if($products instanceof \Illuminate\Pagination\Paginator || $products instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <nav>
                    {{ $products->links('pagination::bootstrap-4') }}
                </nav>
                @endif
            </main>
        </div>
    </div>

    <!-- Add Product Modal -->
    <div class="modal fade" id="addProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i>إضافة منتج جديد
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addProductForm" action="{{ route('product.add') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">اسم المنتج</label>
                                    <input type="text" class="form-control" name="productName" id="productName" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">المؤلف</label>
                                    <input type="text" class="form-control" name="productauthor" id="productauthor" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">الوصف</label>
                            <textarea class="form-control" name="productDescription" id="productDescription" rows="3" required></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">السعر</label>
                                    <input type="number" class="form-control" name="productPrice" id="productPrice" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">عدد الصفحات</label>
                                    <input type="number" class="form-control" name="productNumPages" id="productNumPages" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">اللغة</label>
                                    <input type="text" class="form-control" name="productLanguage" id="productLanguage" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">ISBN</label>
                                    <input type="text" class="form-control" name="productIsbn" id="productIsbn" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">دار النشر</label>
                                    <input type="text" class="form-control" name="ProductPublishingHouse" id="ProductPublishingHouse" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">الفئة</label>
                                    <select name="Productcategorie" id="Productcategorie" class="form-select" required>
                                        <option value="">اختر فئة</option>
                                        @foreach ($categories as $c)
                                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">صورة المنتج</label>
                            <input type="file" class="form-control" name="productImage" id="productImage" accept="image/*" required>
                            <small class="text-muted">الصيغ المدعومة: JPG, PNG, GIF (الحد الأقصى 5MB)</small>
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

    <!-- View Product Modal -->
    <div class="modal fade" id="viewProductModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-book me-2"></i>تفاصيل المنتج
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="viewProductContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">جاري التحميل...</span>
                        </div>
                    </div>
                </div>
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
                <form id="editProductForm" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <input type="hidden" id="productId">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">اسم المنتج</label>
                                    <input type="text" class="form-control" id="editProductName" name="title" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">المؤلف</label>
                                    <input type="text" class="form-control" id="editAuthor" name="author" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">الوصف</label>
                            <textarea class="form-control" id="editProductDescription" name="description" rows="3" required></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">السعر</label>
                                    <input type="number" class="form-control" id="editProductPrice" name="price" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">عدد الصفحات</label>
                                    <input type="number" class="form-control" id="editProductPageNum" name="Page_Num" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">اللغة</label>
                                    <input type="text" class="form-control" id="editProductLanguage" name="Langue" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">ISBN</label>
                                    <input type="text" class="form-control" id="editProductISBN" name="ISBN" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">دار النشر</label>
                                    <input type="text" class="form-control" id="editProductPublishingHouse" name="Publishing_House" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">الكمية </label>
                                    <input type="text" class="form-control" id="editProductQuantity" name="Quantity" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">الفئة</label>
                                    <select name="Productcategorie" id="Productcategorie" class="form-select" required>
                                        <option value="">اختر فئة</option>
                                        @foreach ($categories as $c)
                                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">صورة المنتج</label>
                            <div class="mb-2">
                                <img id="editProductImagePreview" src="" alt="Product Image" style="max-width: 200px; border-radius: 8px; display: none;">
                            </div>
                            <input type="file" class="form-control" id="editProductImage" name="image" accept="image/*">
                            <small class="text-muted">اترك الحقل فارغاً للاحتفاظ بالصورة الحالية</small>
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
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/DashboardProduct.js') }}"></script> 
</body>
</html>
                        

   