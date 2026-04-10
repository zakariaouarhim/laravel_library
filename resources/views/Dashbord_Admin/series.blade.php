<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>إدارة السلاسل</title>
    <link rel="stylesheet" href="{{ asset('css/variables.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components/modal.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.rtl.min.css">
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
                        <i class="fas fa-layer-group header-icon"></i>
                        إدارة السلاسل
                    </h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                        <i class="fas fa-plus me-1"></i> سلسلة جديدة
                    </button>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-card-icon"><i class="fas fa-layer-group text-primary"></i></div>
                    <div class="stat-card-title">إجمالي السلاسل</div>
                    <div class="stat-card-value">{{ $stats['total'] }}</div>
                </div>
                <div class="stat-card green">
                    <div class="stat-card-icon"><i class="fas fa-check-circle text-success"></i></div>
                    <div class="stat-card-title">مكتملة</div>
                    <div class="stat-card-value">{{ $stats['complete'] }}</div>
                </div>
                <div class="stat-card orange">
                    <div class="stat-card-icon"><i class="fas fa-spinner text-warning"></i></div>
                    <div class="stat-card-title">مستمرة</div>
                    <div class="stat-card-value">{{ $stats['ongoing'] }}</div>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <div class="row g-3">
                    <div class="col-md-9">
                        <input type="text" id="searchInput" class="form-control"
                               placeholder="البحث باسم السلسلة...">
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-apply-filters w-100" onclick="clearSearch()">
                            <i class="fas fa-times me-2"></i>مسح البحث
                        </button>
                    </div>
                </div>
            </div>

            <!-- Series Table -->
            <div class="table-section">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="seriesTable">
                        <thead>
                            <tr>
                                <th>اسم السلسلة</th>
                                <th class="text-center">المؤلف</th>
                                <th class="text-center">عدد الكتب</th>
                                <th class="text-center">عدد الأجزاء</th>
                                <th class="text-center">الحالة</th>
                                <th class="text-center">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($series as $s)
                            <tr data-name="{{ mb_strtolower($s->name) }}">
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @if($s->cover_image)
                                            <img src="{{ asset('storage/' . $s->cover_image) }}" alt="{{ $s->name }}"
                                                 width="36" height="50" style="border-radius:4px; object-fit:cover;" loading="lazy">
                                        @endif
                                        <span>{{ $s->name }}</span>
                                    </div>
                                </td>
                                <td class="text-center">{{ $s->author->name ?? '—' }}</td>
                                <td class="text-center">{{ $s->books_count }}</td>
                                <td class="text-center">{{ $s->total_volumes ?? '—' }}</td>
                                <td class="text-center">
                                    @if($s->is_complete)
                                        <span class="badge bg-success">مكتملة</span>
                                    @else
                                        <span class="badge bg-warning text-dark">مستمرة</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="action-buttons">
                                        <button class="btn-icon text-primary" title="تعديل"
                                                onclick="openEditModal({{ $s->id }}, {{ json_encode($s->name) }}, {{ json_encode($s->description ?? '') }}, {{ $s->author_id ?? 'null' }}, {{ $s->total_volumes ?? 'null' }}, {{ $s->is_complete ? 'true' : 'false' }}, '{{ $s->cover_image ? asset('storage/'.$s->cover_image) : '' }}')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn-icon text-danger" title="حذف"
                                                onclick="confirmDelete({{ $s->id }}, {{ json_encode($s->name) }})">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <i class="fas fa-layer-group"></i>
                                        <p>لا توجد سلاسل بعد</p>
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
                <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>إنشاء سلسلة جديدة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('admin.series.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label">اسم السلسلة <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}" required placeholder="مثال: ثلاثية القاهرة">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">وصف السلسلة</label>
                        <textarea name="description" class="form-control" rows="3"
                                  placeholder="وصف مختصر عن السلسلة...">{{ old('description') }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">المؤلف</label>
                        <select name="author_id" class="form-select @error('author_id') is-invalid @enderror">
                            <option value="">— بدون مؤلف —</option>
                            @foreach($authors as $author)
                                <option value="{{ $author->id }}" {{ old('author_id') == $author->id ? 'selected' : '' }}>
                                    {{ $author->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('author_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">عدد الأجزاء</label>
                                <input type="number" name="total_volumes" class="form-control"
                                       value="{{ old('total_volumes') }}" min="1" placeholder="مثال: 3">
                                <div class="form-text">اتركه فارغاً إذا كانت السلسلة مستمرة</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">الحالة</label>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_complete" value="1"
                                           id="createIsComplete" {{ old('is_complete') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="createIsComplete">مكتملة</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">صورة الغلاف</label>
                        <input type="file" name="cover_image" class="form-control" accept="image/*">
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
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>تعديل السلسلة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                <div class="mb-3">
                    <label class="form-label">اسم السلسلة <span class="text-danger">*</span></label>
                    <input type="text" id="edit_name" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">وصف السلسلة</label>
                    <textarea id="edit_description" class="form-control" rows="3"></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label">المؤلف</label>
                    <select id="edit_author_id" class="form-select">
                        <option value="">— بدون مؤلف —</option>
                        @foreach($authors as $author)
                            <option value="{{ $author->id }}">{{ $author->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">عدد الأجزاء</label>
                            <input type="number" id="edit_total_volumes" class="form-control" min="1">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">الحالة</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" id="edit_is_complete">
                                <label class="form-check-label" for="edit_is_complete">مكتملة</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">صورة جديدة (اختياري)</label>
                    <input type="file" id="edit_cover_image" class="form-control" accept="image/*">
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
                هل أنت متأكد من حذف هذه السلسلة؟
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-danger btn-sm" id="confirmDeleteBtn">حذف</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js" defer></script>
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

// ── Live search ───────────────────────────────────────────────
document.getElementById('searchInput').addEventListener('input', function () {
    var q = this.value.trim().toLowerCase();
    document.querySelectorAll('#seriesTable tbody tr[data-name]').forEach(function (row) {
        var name = row.getAttribute('data-name') || '';
        row.style.display = (!q || name.includes(q)) ? '' : 'none';
    });
});

function clearSearch() {
    var input = document.getElementById('searchInput');
    input.value = '';
    input.dispatchEvent(new Event('input'));
}

// ── Edit modal ───────────────────────────────────────────────
var editSeriesId = null;

function openEditModal(id, name, description, authorId, totalVolumes, isComplete, imageUrl) {
    editSeriesId = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_description').value = description || '';
    document.getElementById('edit_author_id').value = authorId || '';
    document.getElementById('edit_total_volumes').value = totalVolumes || '';
    document.getElementById('edit_is_complete').checked = isComplete;

    var imgDiv = document.getElementById('edit_current_image');
    if (imageUrl) {
        imgDiv.innerHTML = '<img src="' + imageUrl + '" alt="الغلاف الحالي" style="max-width:80px; border-radius:4px;">' +
                           '<small class="text-muted d-block mt-1">الغلاف الحالي — ارفع صورة جديدة للاستبدال</small>';
    } else {
        imgDiv.innerHTML = '';
    }

    document.getElementById('edit_cover_image').value = '';
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

document.getElementById('saveEditBtn').addEventListener('click', function () {
    if (!editSeriesId) return;

    var formData = new FormData();
    formData.append('_method', 'PUT');
    formData.append('_token', csrfToken);
    formData.append('name', document.getElementById('edit_name').value);
    formData.append('description', document.getElementById('edit_description').value);
    formData.append('author_id', document.getElementById('edit_author_id').value);
    formData.append('total_volumes', document.getElementById('edit_total_volumes').value);
    formData.append('is_complete', document.getElementById('edit_is_complete').checked ? '1' : '0');

    var fileInput = document.getElementById('edit_cover_image');
    if (fileInput.files.length > 0) {
        formData.append('cover_image', fileInput.files[0]);
    }

    var btn = this;
    btn.disabled    = true;
    btn.textContent = '...';

    fetch('/admin/series/' + editSeriesId, {
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
var deleteSeriesId = null;

function confirmDelete(id, name) {
    deleteSeriesId = id;
    document.getElementById('deleteModalBody').textContent = 'هل أنت متأكد من حذف السلسلة "' + name + '"؟';
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function () {
    if (!deleteSeriesId) return;
    var btn = this;
    btn.disabled    = true;
    btn.textContent = '...';

    fetch('/admin/series/' + deleteSeriesId, {
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
