<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>إدارة دور النشر</title>
    <link rel="stylesheet" href="{{ asset('css/variables.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components/modal.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
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
                        <i class="fas fa-building"></i>
                        إدارة دور النشر
                    </h1>
                    <button class="btn-add" data-bs-toggle="modal" data-bs-target="#addPublisherModal">
                        <i class="fas fa-plus me-2"></i>إضافة دار نشر جديدة
                    </button>
                </div>

                <!-- Stats Cards -->
                <div class="stats-row">
                    <div class="stat-card">
                        <div class="stat-label">إجمالي دور النشر</div>
                        <div class="stat-value">{{ $totalPublishers ?? 0 }}</div>
                    </div>
                    <div class="stat-card" style="border-left-color: #27ae60;">
                        <div class="stat-label">النشطة</div>
                        <div class="stat-value">{{ $activePublishers ?? 0 }}</div>
                    </div>
                    <div class="stat-card" style="border-left-color: #95a5a6;">
                        <div class="stat-label">غير النشطة</div>
                        <div class="stat-value">{{ $inactivePublishers ?? 0 }}</div>
                    </div>
                    <div class="stat-card" style="border-left-color: #f39c12;">
                        <div class="stat-label">الدول</div>
                        <div class="stat-value">{{ $totalCountries ?? 0 }}</div>
                    </div>
                </div>

                <!-- Search and Filter Section -->
                <div class="search-section">
                    <form action="{{ route('admin.publishing_houses.index') }}" method="GET" class="search-controls" style="width: 100%; display: flex; gap: 15px;">

                        <div class="form-group" style="flex: 1;">
                            <label for="searchInput">بحث</label>
                            <div class="input-group">
                                <input
                                    type="text"
                                    name="search"
                                    id="searchInput"
                                    class="form-control"
                                    placeholder="ابحث عن اسم أو دولة أو بريد إلكتروني..."
                                    value="{{ request('search') }}"
                                >
                                <button class="btn btn-outline-primary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group" style="width: 250px;">
                            <label for="statusFilter">الحالة</label>
                            <select name="status" id="statusFilter" class="form-select" onchange="this.form.submit()">
                                <option value="">جميع الحالات</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>نشطة</option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>غير نشطة</option>
                            </select>
                        </div>

                        <div class="form-group" style="display: flex; align-items: end;">
                            <a href="{{ route('admin.publishing_houses.index') }}" class="btn-add" style="background: #95a5a6; text-decoration: none; padding: 8px 15px; display: inline-block;">
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
                        <table class="table table-hover" id="publishersTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>الشعار</th>
                                    <th>الاسم</th>
                                    <th>الدولة</th>
                                    <th>الهاتف</th>
                                    <th>البريد الإلكتروني</th>
                                    <th>الموقع</th>
                                    <th>عدد الكتب</th>
                                    <th>الحالة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($publishers ?? [] as $publisher)
                                <tr data-publisher-id="{{ $publisher->id }}">
                                    <td>#{{ $publisher->id }}</td>
                                    <td>
                                        <div class="product-image-cell">
                                            @if($publisher->logo)
                                                <img src="{{ asset('storage/' . $publisher->logo) }}" alt="{{ $publisher->name }}" class="product-image" width="60" height="60" loading="lazy" style="object-fit: contain;">
                                            @else
                                                <div style="width: 60px; height: 60px; background: #e9ecef; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                                    <i class="fas fa-building" style="color: #bdc3c7;"></i>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="product-name">{{ $publisher->name }}</div>
                                    </td>
                                    <td>{{ $publisher->country ?? '-' }}</td>
                                    <td>{{ $publisher->phone ?? '-' }}</td>
                                    <td>{{ $publisher->email ?? '-' }}</td>
                                    <td>
                                        @if($publisher->website)
                                            <a href="{{ $publisher->website }}" target="_blank" rel="noopener">{{ \Illuminate\Support\Str::limit($publisher->website, 30) }}</a>
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>{{ $publisher->books_count }}</td>
                                    <td>
                                        @if($publisher->status === 'active')
                                            <span class="badge bg-success">نشطة</span>
                                        @else
                                            <span class="badge bg-secondary">غير نشطة</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button
                                                class="btn-action btn-view"
                                                onclick="viewPublisher({{ $publisher->id }})"
                                                title="عرض التفاصيل"
                                            >
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button
                                                class="btn-action btn-edit"
                                                onclick="editPublisher({{ $publisher->id }})"
                                                title="تعديل"
                                            >
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button
                                                class="btn-action btn-delete"
                                                onclick="deletePublisher({{ $publisher->id }})"
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
                                            <p>لا توجد دور نشر</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                @if($publishers instanceof \Illuminate\Pagination\Paginator || $publishers instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <nav>
                    {{ $publishers->links('pagination::bootstrap-4') }}
                </nav>
                @endif
            </main>
        </div>
    </div>

    <!-- Add Publisher Modal -->
    <div class="modal fade" id="addPublisherModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus-circle me-2"></i>إضافة دار نشر جديدة
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="addPublisherForm" action="{{ route('admin.publishing_houses.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">اسم دار النشر <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="name" required maxlength="191">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">الدولة</label>
                                    <input type="text" class="form-control" name="country" maxlength="100">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">الهاتف</label>
                                    <input type="text" class="form-control" name="phone" maxlength="20">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">البريد الإلكتروني</label>
                                    <input type="email" class="form-control" name="email" maxlength="191">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label">الموقع الإلكتروني</label>
                                    <input type="url" class="form-control" name="website" placeholder="https://example.com" maxlength="255">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">سنة التأسيس</label>
                                    <input type="number" class="form-control" name="founded_year" min="1400" max="{{ date('Y') + 1 }}" placeholder="مثال: 1985">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">العنوان</label>
                            <textarea class="form-control" name="address" rows="2" maxlength="2000"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">الوصف</label>
                            <textarea class="form-control" name="description" rows="3" maxlength="5000"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label">الشعار</label>
                                    <input type="file" class="form-control" name="logo" accept="image/*">
                                    <small class="text-muted">الصيغ المدعومة: JPG, PNG, GIF, WEBP (الحد الأقصى 2MB)</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">الحالة <span class="text-danger">*</span></label>
                                    <select name="status" class="form-select" required>
                                        <option value="active" selected>نشطة</option>
                                        <option value="inactive">غير نشطة</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- SEO override fields. Empty = use auto-generated MetaBuilder fallback. -->
                        <div class="accordion mb-3" id="addPublisherSeoAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#addPublisherSeoCollapse">
                                        <i class="fas fa-search me-2"></i>إعدادات SEO <span class="text-muted ms-2 small">(اختياري — اتركه فارغاً للتوليد التلقائي)</span>
                                    </button>
                                </h2>
                                <div id="addPublisherSeoCollapse" class="accordion-collapse collapse" data-bs-parent="#addPublisherSeoAccordion">
                                    <div class="accordion-body">
                                        <div class="mb-3">
                                            <label class="form-label">عنوان SEO <small class="text-muted">(الحد الأقصى 70 حرف)</small></label>
                                            <input type="text" class="form-control" name="meta_title" maxlength="70">
                                            <small class="text-muted">يظهر كعنوان نتيجة البحث في Google</small>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">وصف SEO <small class="text-muted">(الحد الأقصى 160 حرف)</small></label>
                                            <textarea class="form-control" name="meta_description" rows="2" maxlength="160"></textarea>
                                            <small class="text-muted">يظهر تحت العنوان في نتائج البحث</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>حفظ
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Publisher Modal -->
    <div class="modal fade" id="viewPublisherModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-building me-2"></i>تفاصيل دار النشر
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="viewPublisherContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">جاري التحميل...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Publisher Modal -->
    <div class="modal fade" id="editPublisherModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>تعديل دار النشر
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editPublisherForm" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <input type="hidden" id="editPublisherId">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">اسم دار النشر <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="editName" name="name" required maxlength="191">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">الدولة</label>
                                    <input type="text" class="form-control" id="editCountry" name="country" maxlength="100">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">الهاتف</label>
                                    <input type="text" class="form-control" id="editPhone" name="phone" maxlength="20">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">البريد الإلكتروني</label>
                                    <input type="email" class="form-control" id="editEmail" name="email" maxlength="191">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label">الموقع الإلكتروني</label>
                                    <input type="url" class="form-control" id="editWebsite" name="website" maxlength="255">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">سنة التأسيس</label>
                                    <input type="number" class="form-control" id="editFoundedYear" name="founded_year" min="1400" max="{{ date('Y') + 1 }}">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">العنوان</label>
                            <textarea class="form-control" id="editAddress" name="address" rows="2" maxlength="2000"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">الوصف</label>
                            <textarea class="form-control" id="editDescription" name="description" rows="3" maxlength="5000"></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">الشعار</label>
                            <div class="mb-2">
                                <img id="editLogoPreview" src="" alt="Logo" style="max-width: 150px; border-radius: 8px; display: none;">
                            </div>
                            <input type="file" class="form-control" id="editLogo" name="logo" accept="image/*">
                            <small class="text-muted">اترك الحقل فارغاً للاحتفاظ بالشعار الحالي</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">الحالة <span class="text-danger">*</span></label>
                            <select id="editStatus" name="status" class="form-select" required>
                                <option value="active">نشطة</option>
                                <option value="inactive">غير نشطة</option>
                            </select>
                        </div>

                        <!-- SEO override fields. Populated by editPublisher() from the API response. -->
                        <div class="accordion mb-3" id="editPublisherSeoAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#editPublisherSeoCollapse">
                                        <i class="fas fa-search me-2"></i>إعدادات SEO <span class="text-muted ms-2 small">(اختياري — اتركه فارغاً للتوليد التلقائي)</span>
                                    </button>
                                </h2>
                                <div id="editPublisherSeoCollapse" class="accordion-collapse collapse" data-bs-parent="#editPublisherSeoAccordion">
                                    <div class="accordion-body">
                                        <div class="mb-3">
                                            <label class="form-label">عنوان SEO <small class="text-muted">(الحد الأقصى 70 حرف)</small></label>
                                            <input type="text" class="form-control" id="editPublisherMetaTitle" name="meta_title" maxlength="70">
                                            <small class="text-muted">يظهر كعنوان نتيجة البحث في Google</small>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">وصف SEO <small class="text-muted">(الحد الأقصى 160 حرف)</small></label>
                                            <textarea class="form-control" id="editPublisherMetaDescription" name="meta_description" rows="2" maxlength="160"></textarea>
                                            <small class="text-muted">يظهر تحت العنوان في نتائج البحث</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js" defer></script>
    <script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    window.viewPublisher = function(id) {
        const content = document.getElementById('viewPublisherContent');
        content.innerHTML = `<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>`;
        new bootstrap.Modal(document.getElementById('viewPublisherModal')).show();

        fetch(`/admin/publishing-houses/api/${id}`, {
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(p => {
            const statusBadge = p.status === 'active'
                ? '<span class="badge bg-success">نشطة</span>'
                : '<span class="badge bg-secondary">غير نشطة</span>';
            const logoHtml = p.logo
                ? `<img src="/storage/${p.logo}" alt="${p.name}" style="max-width: 150px; border-radius: 8px;">`
                : '<div style="width:120px;height:120px;background:#e9ecef;border-radius:8px;display:flex;align-items:center;justify-content:center;"><i class="fas fa-building" style="font-size:40px;color:#bdc3c7;"></i></div>';

            content.innerHTML = `
                <div class="row">
                    <div class="col-md-4 text-center">${logoHtml}</div>
                    <div class="col-md-8">
                        <h4>${p.name}</h4>
                        <p class="mb-2">${statusBadge}</p>
                        <p><strong>الدولة:</strong> ${p.country || '-'}</p>
                        <p><strong>الهاتف:</strong> ${p.phone || '-'}</p>
                        <p><strong>البريد الإلكتروني:</strong> ${p.email || '-'}</p>
                        <p><strong>الموقع:</strong> ${p.website ? `<a href="${p.website}" target="_blank" rel="noopener">${p.website}</a>` : '-'}</p>
                        <p><strong>سنة التأسيس:</strong> ${p.founded_year || '-'}</p>
                        <p><strong>عدد الكتب:</strong> ${p.books_count ?? 0}</p>
                    </div>
                </div>
                <hr>
                <p><strong>العنوان:</strong><br>${p.address ? p.address.replace(/\n/g, '<br>') : '-'}</p>
                <p><strong>الوصف:</strong><br>${p.description ? p.description.replace(/\n/g, '<br>') : '-'}</p>
            `;
        })
        .catch(err => {
            console.error(err);
            content.innerHTML = '<div class="alert alert-danger">فشل في تحميل البيانات</div>';
        });
    };

    window.editPublisher = function(id) {
        fetch(`/admin/publishing-houses/api/${id}`, {
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(p => {
            document.getElementById('editPublisherId').value = p.id;
            document.getElementById('editName').value = p.name || '';
            document.getElementById('editCountry').value = p.country || '';
            document.getElementById('editPhone').value = p.phone || '';
            document.getElementById('editEmail').value = p.email || '';
            document.getElementById('editWebsite').value = p.website || '';
            document.getElementById('editFoundedYear').value = p.founded_year || '';
            document.getElementById('editAddress').value = p.address || '';
            document.getElementById('editDescription').value = p.description || '';
            document.getElementById('editStatus').value = p.status || 'active';
            document.getElementById('editPublisherMetaTitle').value = p.meta_title || '';
            document.getElementById('editPublisherMetaDescription').value = p.meta_description || '';

            const preview = document.getElementById('editLogoPreview');
            if (p.logo) {
                preview.src = `/storage/${p.logo}`;
                preview.style.display = 'block';
            } else {
                preview.style.display = 'none';
            }

            document.getElementById('editPublisherForm').action = `/admin/publishing-houses/api/${p.id}`;
            new bootstrap.Modal(document.getElementById('editPublisherModal')).show();
        })
        .catch(err => {
            console.error(err);
            alert('فشل في تحميل بيانات دار النشر');
        });
    };

    window.deletePublisher = function(id) {
        if (!confirm('هل أنت متأكد من حذف دار النشر؟ هذا الإجراء لا يمكن التراجع عنه.')) return;

        fetch(`/admin/publishing-houses/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            }
        })
        .then(r => r.json().then(data => ({ ok: r.ok, data })))
        .then(({ ok, data }) => {
            if (ok && data.success) {
                const row = document.querySelector(`tr[data-publisher-id="${id}"]`);
                if (row) row.remove();
                alert(data.message || 'تم الحذف بنجاح');
            } else {
                alert(data.message || 'فشل الحذف');
            }
        })
        .catch(err => {
            console.error(err);
            alert('حدث خطأ أثناء الحذف');
        });
    };
    </script>
</body>
</html>
