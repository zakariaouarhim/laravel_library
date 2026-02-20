<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>إدارة الفئات</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.rtl.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/dashboardCategories.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sidebardaschboard.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>

<div class="container-fluid">
    <div class="row">
        @include('Dashbord_Admin.Sidebar')

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">

            <!-- Flash messages -->
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger mt-3">
                    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            <!-- Page Header -->
            <div class="page-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h2>
                        <i class="fas fa-folder-open header-icon"></i>
                        إدارة الفئات
                    </h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                        <i class="fas fa-plus me-1"></i> فئة جديدة
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-card-icon"><i class="fas fa-folder text-primary"></i></div>
                    <div class="stat-card-title">إجمالي الفئات</div>
                    <div class="stat-card-value">{{ $stats['total'] }}</div>
                </div>
                <div class="stat-card green">
                    <div class="stat-card-icon"><i class="fas fa-sitemap text-success"></i></div>
                    <div class="stat-card-title">فئات رئيسية</div>
                    <div class="stat-card-value">{{ $stats['parents'] }}</div>
                </div>
                <div class="stat-card orange">
                    <div class="stat-card-icon"><i class="fas fa-list-ul text-warning"></i></div>
                    <div class="stat-card-title">فئات فرعية</div>
                    <div class="stat-card-value">{{ $stats['children'] }}</div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <div class="row g-3">
                    <div class="col-md-9">
                        <input type="text" id="searchInput" class="form-control"
                               placeholder="البحث باسم الفئة...">
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-apply-filters w-100" onclick="clearSearch()">
                            <i class="fas fa-times me-2"></i>مسح البحث
                        </button>
                    </div>
                </div>
            </div>

            <!-- Categories Table -->
            <div class="table-section">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="categoriesTable">
                        <thead>
                            <tr>
                                <th>الاسم</th>
                                <th class="text-center">الأيقونة</th>
                                
                                <th class="text-center">النوع</th>
                                <th class="text-center">الكتب</th>
                                <th class="text-center">الفئات الفرعية</th>
                                <th class="text-center">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categories as $parent)
                            {{-- Parent row --}}
                            <tr class="row-parent" data-name="{{ strtolower($parent->name) }}">
                                <td>
                                    @if($parent->children->count())
                                    <button class="expand-btn" data-target="children-{{ $parent->id }}" title="توسيع/طي">
                                        <i class="fas fa-chevron-down"></i>
                                    </button>
                                    @else
                                    <span style="display:inline-block;width:30px;"></span>
                                    @endif
                                    {{ $parent->name }}
                                </td>
                                <td class="text-center">
                                    @if($parent->categorie_icon)
                                        <span class="icon-preview"><i class="{{ $parent->categorie_icon }}"></i></span>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                
                                <td class="text-center"><span class="badge bg-primary">رئيسية</span></td>
                                <td class="text-center">{{ $parent->books_count }}</td>
                                <td class="text-center">{{ $parent->children->count() }}</td>
                                <td class="text-center">
                                    <div class="action-buttons">
                                        <button class="btn-icon text-primary" title="تعديل"
                                                onclick="openEditModal({{ $parent->id }}, '{{ addslashes($parent->name) }}', null, '{{ addslashes($parent->categorie_icon ?? '') }}', '{{ $parent->categorie_image ? asset('storage/'.$parent->categorie_image) : '' }}')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn-icon text-danger" title="حذف"
                                                onclick="confirmDelete({{ $parent->id }}, '{{ addslashes($parent->name) }}')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            {{-- Children rows --}}
                            @if($parent->children->count())
                            <tbody class="children-group" id="children-{{ $parent->id }}">
                                @foreach($parent->children as $child)
                                <tr class="row-child" data-name="{{ strtolower($child->name) }}">
                                    <td class="child-indent">
                                        <i class="fas fa-level-up-alt fa-rotate-90 text-muted me-2" style="font-size:.75rem;"></i>
                                        {{ $child->name }}
                                    </td>
                                    <td class="text-center">
                                        @if($child->categorie_icon)
                                            <span class="icon-preview-sm"><i class="{{ $child->categorie_icon }}"></i></span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($child->categorie_image)
                                            <img src="{{ asset('storage/' . $child->categorie_image) }}"
                                                 alt="{{ $child->name }}" class="cat-img">
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td class="text-center"><span class="badge bg-secondary">فرعية</span></td>
                                    <td class="text-center">{{ $child->books_count }}</td>
                                    <td class="text-center text-muted">—</td>
                                    <td class="text-center">
                                        <div class="action-buttons">
                                            <button class="btn-icon text-primary" title="تعديل"
                                                    onclick="openEditModal({{ $child->id }}, '{{ addslashes($child->name) }}', {{ $child->parent_id }}, '{{ addslashes($child->categorie_icon ?? '') }}', '{{ $child->categorie_image ? asset('storage/'.$child->categorie_image) : '' }}')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-icon text-danger" title="حذف"
                                                    onclick="confirmDelete({{ $child->id }}, '{{ addslashes($child->name) }}')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            @endif

                            @empty
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <i class="fas fa-folder-open"></i>
                                        <p>لا توجد فئات بعد</p>
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

<!-- Toast container -->
<div id="toast-container"></div>

<!-- ============================================================ -->
<!-- Create Modal                                                   -->
<!-- ============================================================ -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>إنشاء فئة جديدة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('admin.categories.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label">الاسم <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}" required placeholder="مثال: روايات">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">الفئة الأم</label>
                        <select name="parent_id" class="form-select @error('parent_id') is-invalid @enderror">
                            <option value="">— فئة رئيسية —</option>
                            @foreach($parentOptions as $opt)
                                <option value="{{ $opt['id'] }}" {{ old('parent_id') == $opt['id'] ? 'selected' : '' }}>
                                    {{ $opt['name'] }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text">اتركه فارغاً لإنشاء فئة رئيسية</div>
                        @error('parent_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">أيقونة Font Awesome</label>
                        <div class="input-group">
                            <input type="text" name="categorie_icon" id="create_icon_input"
                                   class="form-control"
                                   value="{{ old('categorie_icon') }}"
                                   placeholder="fas fa-book">
                            <span class="input-group-text icon-input-preview" id="create_icon_preview">
                                <i class="{{ old('categorie_icon', 'fas fa-folder') }}"></i>
                            </span>
                        </div>
                        <div class="form-text">مثال: <code>fas fa-book</code> · <code>fas fa-star</code> · <code>fas fa-graduation-cap</code></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">صورة الفئة</label>
                        <input type="file" name="categorie_image" class="form-control" accept="image/*">
                        <div class="form-text">اختياري · JPG/PNG/WebP · الحد الأقصى 2MB</div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">إنشاء</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- Edit Modal                                                     -->
<!-- ============================================================ -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>تعديل الفئة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                <div class="mb-3">
                    <label class="form-label">الاسم <span class="text-danger">*</span></label>
                    <input type="text" id="edit_name" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">الفئة الأم</label>
                    <select id="edit_parent_id" class="form-select">
                        <option value="">— فئة رئيسية —</option>
                        @foreach($parentOptions as $opt)
                            <option value="{{ $opt['id'] }}">{{ $opt['name'] }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">اتركه فارغاً لإبقاء الفئة رئيسية</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">أيقونة Font Awesome</label>
                    <div class="input-group">
                        <input type="text" id="edit_icon_input" class="form-control" placeholder="fas fa-book">
                        <span class="input-group-text icon-input-preview" id="edit_icon_preview">
                            <i class="fas fa-folder"></i>
                        </span>
                    </div>
                    <div class="form-text">مثال: <code>fas fa-book</code> · <code>fas fa-star</code></div>
                </div>

                <div class="mb-3">
                    <label class="form-label">صورة جديدة (اختياري)</label>
                    <input type="file" id="edit_image_file" class="form-control" accept="image/*">
                    <div id="edit_current_image" class="mt-2"></div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-primary" id="saveEditBtn">حفظ</button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- Delete Confirm Modal                                          -->
<!-- ============================================================ -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header danger">
                <h5 class="modal-title">تأكيد الحذف</h5>
            </div>
            <div class="modal-body" id="deleteModalBody">
                هل أنت متأكد من حذف هذه الفئة؟
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-danger btn-sm" id="confirmDeleteBtn">حذف</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

// ── Toast ────────────────────────────────────────────────────
function showToast(message, type) {
    if (type === undefined) type = 'success';
    var id  = 'toast-' + Date.now();
    var el  = document.createElement('div');
    el.id   = id;
    el.className = 'toast-box ' + type;
    el.textContent = message;
    document.getElementById('toast-container').appendChild(el);
    setTimeout(function () { el.remove(); }, 3500);
}

// ── Expand / collapse children ───────────────────────────────
document.querySelectorAll('.expand-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var targetId = this.getAttribute('data-target');
        var group    = document.getElementById(targetId);
        var icon     = this.querySelector('i');
        if (!group) return;
        group.classList.toggle('open');
        icon.classList.toggle('fa-chevron-down');
        icon.classList.toggle('fa-chevron-up');
    });
});

// ── Live search ───────────────────────────────────────────────
document.getElementById('searchInput').addEventListener('input', function () {
    var q = this.value.trim().toLowerCase();
    document.querySelectorAll('#categoriesTable tbody tr.row-parent, #categoriesTable tbody tr.row-child').forEach(function (row) {
        var name = row.getAttribute('data-name') || '';
        row.style.display = (!q || name.includes(q)) ? '' : 'none';
    });
    document.querySelectorAll('.children-group').forEach(function (g) {
        if (q) g.classList.add('open');
    });
});

function clearSearch() {
    var input = document.getElementById('searchInput');
    input.value = '';
    input.dispatchEvent(new Event('input'));
}

// ── Icon live preview (create modal) ─────────────────────────
document.getElementById('create_icon_input').addEventListener('input', function () {
    document.getElementById('create_icon_preview').innerHTML =
        '<i class="' + (this.value || 'fas fa-folder') + '"></i>';
});

// ── Icon live preview (edit modal) ───────────────────────────
document.getElementById('edit_icon_input').addEventListener('input', function () {
    document.getElementById('edit_icon_preview').innerHTML =
        '<i class="' + (this.value || 'fas fa-folder') + '"></i>';
});

// ── Edit modal ───────────────────────────────────────────────
var editCategoryId = null;

function openEditModal(id, name, parentId, icon, imageUrl) {
    editCategoryId = id;
    document.getElementById('edit_name').value       = name;
    document.getElementById('edit_icon_input').value = icon;
    document.getElementById('edit_icon_preview').innerHTML =
        '<i class="' + (icon || 'fas fa-folder') + '"></i>';

    document.getElementById('edit_parent_id').value = parentId || '';

    var imgDiv = document.getElementById('edit_current_image');
    if (imageUrl) {
        imgDiv.innerHTML = '<img src="' + imageUrl + '" alt="الصورة الحالية" class="current-img-thumb">' +
                           '<small class="text-muted d-block mt-1">الصورة الحالية — ارفع صورة جديدة للاستبدال</small>';
    } else {
        imgDiv.innerHTML = '';
    }

    document.getElementById('edit_image_file').value = '';
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

document.getElementById('saveEditBtn').addEventListener('click', function () {
    if (!editCategoryId) return;

    var formData = new FormData();
    formData.append('_method', 'PUT');
    formData.append('_token', csrfToken);
    formData.append('name',           document.getElementById('edit_name').value);
    formData.append('parent_id',      document.getElementById('edit_parent_id').value);
    formData.append('categorie_icon', document.getElementById('edit_icon_input').value);
    var fileInput = document.getElementById('edit_image_file');
    if (fileInput.files.length > 0) {
        formData.append('categorie_image', fileInput.files[0]);
    }

    var btn = this;
    btn.disabled    = true;
    btn.textContent = '...';

    fetch('/admin/categories/' + editCategoryId, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken },
        body: formData,
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
        if (data.success) {
            showToast(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
            setTimeout(function () { location.reload(); }, 800);
        } else {
            showToast(data.message || 'حدث خطأ', 'danger');
        }
    })
    .catch(function () { showToast('حدث خطأ في الاتصال', 'danger'); })
    .finally(function () {
        btn.disabled    = false;
        btn.textContent = 'حفظ';
    });
});

// ── Delete modal ─────────────────────────────────────────────
var deleteCategoryId = null;

function confirmDelete(id, name) {
    deleteCategoryId = id;
    document.getElementById('deleteModalBody').textContent = 'هل أنت متأكد من حذف الفئة "' + name + '"؟';
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function () {
    if (!deleteCategoryId) return;
    var btn = this;
    btn.disabled    = true;
    btn.textContent = '...';

    fetch('/admin/categories/' + deleteCategoryId, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
        bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(function () { location.reload(); }, 800);
        } else {
            showToast(data.message, 'danger');
        }
    })
    .catch(function () { showToast('حدث خطأ في الاتصال', 'danger'); })
    .finally(function () {
        btn.disabled    = false;
        btn.textContent = 'حذف';
    });
});

// ── Re-open create modal on validation error ──────────────────
@if($errors->any() && old('name'))
    new bootstrap.Modal(document.getElementById('createModal')).show();
@endif
</script>
</body>
</html>
