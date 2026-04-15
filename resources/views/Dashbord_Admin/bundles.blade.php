<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>إدارة الباقات</title>
    <link rel="stylesheet" href="{{ asset('css/variables.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components/modal.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.rtl.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/dashboardCategories.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sidebardaschboard.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .bundle-items-list { max-height: 260px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 6px; padding: 8px; background:#fafafa; }
        .bundle-item-row { display:flex; align-items:center; gap:8px; padding:6px 4px; border-bottom:1px dashed #eee; }
        .bundle-item-row:last-child { border-bottom:none; }
        .bundle-item-row .title { flex:1; font-size: 0.9rem; }
        .bundle-item-row .vol-pill { background:#eef2ff; color:#3730a3; padding:1px 8px; border-radius:999px; font-size:.75rem; }
        .bundle-item-row input[type="number"] { width: 70px; }
        .savings-preview { background:#ecfdf5; border:1px solid #86efac; color:#065f46; padding:8px 12px; border-radius:6px; font-size:.88rem; }
    </style>
</head>
<body>

<div class="container-fluid">
    <div class="row">
        @include('Dashbord_Admin.Sidebar')

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">

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

            <div class="page-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h2><i class="fas fa-box header-icon"></i> إدارة الباقات</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                        <i class="fas fa-plus me-1"></i> باقة جديدة
                    </button>
                </div>
            </div>

            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-card-icon"><i class="fas fa-box text-primary"></i></div>
                    <div class="stat-card-title">إجمالي الباقات</div>
                    <div class="stat-card-value">{{ $stats['total'] }}</div>
                </div>
                <div class="stat-card green">
                    <div class="stat-card-icon"><i class="fas fa-check-circle text-success"></i></div>
                    <div class="stat-card-title">متوفرة</div>
                    <div class="stat-card-value">{{ $stats['in_stock'] }}</div>
                </div>
                <div class="stat-card orange">
                    <div class="stat-card-icon"><i class="fas fa-exclamation-triangle text-warning"></i></div>
                    <div class="stat-card-title">نفذت الكمية</div>
                    <div class="stat-card-value">{{ $stats['out_stock'] }}</div>
                </div>
            </div>

            <div class="filter-section">
                <div class="row g-3">
                    <div class="col-md-9">
                        <input type="text" id="searchInput" class="form-control" placeholder="البحث باسم الباقة...">
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-apply-filters w-100" onclick="clearSearch()">
                            <i class="fas fa-times me-2"></i>مسح البحث
                        </button>
                    </div>
                </div>
            </div>

            <div class="table-section">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="bundlesTable">
                        <thead>
                            <tr>
                                <th>الباقة</th>
                                <th class="text-center">السلسلة</th>
                                <th class="text-center">عدد الأجزاء</th>
                                <th class="text-center">السعر</th>
                                <th class="text-center">المخزون</th>
                                <th class="text-center">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($bundles as $bundle)
                            <tr data-name="{{ mb_strtolower($bundle->title) }}">
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        @if($bundle->image)
                                            <img src="{{ asset($bundle->image) }}" alt="{{ $bundle->title }}"
                                                 width="36" height="50" style="border-radius:4px; object-fit:cover;" loading="lazy">
                                        @endif
                                        <span>{{ $bundle->title }}</span>
                                    </div>
                                </td>
                                <td class="text-center">{{ $bundle->series->name ?? '—' }}</td>
                                <td class="text-center">{{ $bundle->items->count() }}</td>
                                <td class="text-center">{{ number_format((float) $bundle->price, 2) }}</td>
                                <td class="text-center">
                                    @if($bundle->quantity > 0)
                                        <span class="badge bg-success">{{ $bundle->quantity }}</span>
                                    @else
                                        <span class="badge bg-danger">نفذت</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="action-buttons">
                                        <button class="btn-icon text-primary" title="تعديل" onclick="openEditModal({{ $bundle->id }})">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn-icon text-danger" title="حذف"
                                                onclick="confirmDelete({{ $bundle->id }}, {{ json_encode($bundle->title) }})">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <i class="fas fa-box"></i>
                                        <p>لا توجد باقات بعد</p>
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

<div id="toast-container"></div>

<!-- Create modal -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>إنشاء باقة جديدة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('admin.bundles.store') }}" enctype="multipart/form-data" id="createForm">
                @csrf
                <div class="modal-body">

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">السلسلة <span class="text-danger">*</span></label>
                            <select name="series_id" id="create_series_id" class="form-select" required>
                                <option value="">— اختر سلسلة —</option>
                                @foreach($series as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">اسم الباقة <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" required placeholder="مثال: ثلاثية القاهرة — الأجزاء الثلاثة">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">وصف الباقة</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="اختياري"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">السعر <span class="text-danger">*</span></label>
                            <input type="number" name="price" class="form-control" step="0.01" min="0" required id="create_price">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">المخزون <span class="text-danger">*</span></label>
                            <input type="number" name="quantity" class="form-control" min="0" required value="0">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">صورة الغلاف</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label d-flex justify-content-between align-items-center">
                            <span>أجزاء الباقة <span class="text-danger">*</span></span>
                            <small class="text-muted">اختر الأجزاء المشمولة وحدد كميتها</small>
                        </label>
                        <div id="create_items_list" class="bundle-items-list">
                            <div class="text-muted text-center py-3">اختر السلسلة أولاً</div>
                        </div>
                        <div id="create_savings" class="savings-preview mt-2" style="display:none;"></div>
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

<!-- Edit modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>تعديل الباقة</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">السلسلة <span class="text-danger">*</span></label>
                        <select id="edit_series_id" class="form-select" required>
                            @foreach($series as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">اسم الباقة <span class="text-danger">*</span></label>
                        <input type="text" id="edit_title" class="form-control" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">وصف الباقة</label>
                    <textarea id="edit_description" class="form-control" rows="2"></textarea>
                </div>

                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">السعر <span class="text-danger">*</span></label>
                        <input type="number" id="edit_price" class="form-control" step="0.01" min="0" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">المخزون <span class="text-danger">*</span></label>
                        <input type="number" id="edit_quantity" class="form-control" min="0" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">صورة جديدة</label>
                        <input type="file" id="edit_image" class="form-control" accept="image/*">
                        <div id="edit_current_image" class="mt-1"></div>
                    </div>
                </div>

                <div class="mb-2">
                    <label class="form-label d-flex justify-content-between align-items-center">
                        <span>أجزاء الباقة <span class="text-danger">*</span></span>
                        <small class="text-muted">اختر الأجزاء المشمولة وحدد كميتها</small>
                    </label>
                    <div id="edit_items_list" class="bundle-items-list">
                        <div class="text-muted text-center py-3">—</div>
                    </div>
                    <div id="edit_savings" class="savings-preview mt-2" style="display:none;"></div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-primary" id="saveEditBtn">حفظ</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete confirm -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header danger">
                <h5 class="modal-title">تأكيد الحذف</h5>
            </div>
            <div class="modal-body" id="deleteModalBody">هل أنت متأكد؟</div>
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

function showToast(msg, type) {
    type = type || 'success';
    var el = document.createElement('div');
    el.className = 'toast-box ' + type;
    el.textContent = msg;
    document.getElementById('toast-container').appendChild(el);
    setTimeout(function(){ el.remove(); }, 3500);
}

document.getElementById('searchInput').addEventListener('input', function () {
    var q = this.value.trim().toLowerCase();
    document.querySelectorAll('#bundlesTable tbody tr[data-name]').forEach(function (row) {
        row.style.display = (!q || (row.getAttribute('data-name') || '').includes(q)) ? '' : 'none';
    });
});
function clearSearch() {
    var i = document.getElementById('searchInput'); i.value = ''; i.dispatchEvent(new Event('input'));
}

// Render items picker (items: array of book objects; preselected: {book_id: qty})
function renderItemsPicker(containerId, items, preselected) {
    var c = document.getElementById(containerId);
    preselected = preselected || {};
    if (!items.length) {
        c.innerHTML = '<div class="text-muted text-center py-3">لا توجد كتب في هذه السلسلة</div>';
        return;
    }
    var html = '';
    items.forEach(function (b) {
        var checked = preselected.hasOwnProperty(b.id) ? 'checked' : '';
        var qty = preselected[b.id] || 1;
        html += '<div class="bundle-item-row" data-book-id="'+b.id+'" data-price="'+(b.price||0)+'">'
             +    '<input type="checkbox" class="item-check" '+checked+'>'
             +    '<span class="title">'+b.title+'</span>'
             +    (b.volume_number ? '<span class="vol-pill">الجزء '+b.volume_number+'</span>' : '')
             +    '<span class="text-muted small">'+(parseFloat(b.price||0).toFixed(2))+'</span>'
             +    '<input type="number" class="form-control form-control-sm item-qty" min="1" max="50" value="'+qty+'">'
             +  '</div>';
    });
    c.innerHTML = html;
}

function collectItems(containerId) {
    var items = [];
    document.querySelectorAll('#' + containerId + ' .bundle-item-row').forEach(function (row) {
        var check = row.querySelector('.item-check');
        if (check.checked) {
            items.push({
                book_id: parseInt(row.getAttribute('data-book-id')),
                quantity: parseInt(row.querySelector('.item-qty').value) || 1,
                price: parseFloat(row.getAttribute('data-price')) || 0,
            });
        }
    });
    return items;
}

function updateSavings(containerId, priceInputId, savingsId) {
    var items = collectItems(containerId);
    var total = items.reduce(function (s, it) { return s + it.price * it.quantity; }, 0);
    var bundlePrice = parseFloat(document.getElementById(priceInputId).value) || 0;
    var saving = total - bundlePrice;
    var el = document.getElementById(savingsId);
    if (items.length && bundlePrice > 0 && saving > 0) {
        el.style.display = 'block';
        el.textContent = 'توفير ' + saving.toFixed(2) + ' — مجموع الأجزاء منفصلة: ' + total.toFixed(2);
    } else if (items.length && total > 0 && bundlePrice > 0) {
        el.style.display = 'block';
        el.textContent = 'مجموع الأجزاء منفصلة: ' + total.toFixed(2);
    } else {
        el.style.display = 'none';
    }
}

function attachSavingsListeners(containerId, priceInputId, savingsId) {
    document.getElementById(containerId).addEventListener('input', function () {
        updateSavings(containerId, priceInputId, savingsId);
    });
    document.getElementById(containerId).addEventListener('change', function () {
        updateSavings(containerId, priceInputId, savingsId);
    });
    document.getElementById(priceInputId).addEventListener('input', function () {
        updateSavings(containerId, priceInputId, savingsId);
    });
}

function loadSeriesBooks(seriesId) {
    return fetch('/admin/bundles-series/' + seriesId + '/books', {
        headers: { 'Accept': 'application/json' }
    }).then(function (r) { return r.json(); });
}

// Create: on series change load books
document.getElementById('create_series_id').addEventListener('change', function () {
    var id = this.value;
    if (!id) { document.getElementById('create_items_list').innerHTML = '<div class="text-muted text-center py-3">اختر السلسلة أولاً</div>'; return; }
    loadSeriesBooks(id).then(function (books) {
        renderItemsPicker('create_items_list', books, {});
        updateSavings('create_items_list', 'create_price', 'create_savings');
    });
});

attachSavingsListeners('create_items_list', 'create_price', 'create_savings');

// Submit create
document.getElementById('createForm').addEventListener('submit', function (e) {
    var items = collectItems('create_items_list');
    if (items.length === 0) {
        e.preventDefault();
        showToast('اختر جزءاً واحداً على الأقل', 'danger');
        return;
    }
    // Add items to FormData via hidden inputs
    items.forEach(function (it, i) {
        var h1 = document.createElement('input'); h1.type = 'hidden'; h1.name = 'items['+i+'][book_id]'; h1.value = it.book_id; this.appendChild(h1);
        var h2 = document.createElement('input'); h2.type = 'hidden'; h2.name = 'items['+i+'][quantity]'; h2.value = it.quantity; this.appendChild(h2);
    }, this);
});

// ── Edit ──
var editBundleId = null;
function openEditModal(id) {
    editBundleId = id;
    fetch('/admin/bundles/' + id, { headers: { 'Accept': 'application/json' } })
        .then(function(r){ return r.json(); })
        .then(function(b) {
            document.getElementById('edit_series_id').value = b.series_id || '';
            document.getElementById('edit_title').value = b.title;
            document.getElementById('edit_description').value = b.description || '';
            document.getElementById('edit_price').value = b.price;
            document.getElementById('edit_quantity').value = b.quantity;
            document.getElementById('edit_image').value = '';
            document.getElementById('edit_current_image').innerHTML = b.image ? '<img src="/'+b.image+'" style="max-width:60px; border-radius:4px;">' : '';

            var preselected = {};
            b.items.forEach(function (it) { preselected[it.book_id] = it.quantity; });

            return loadSeriesBooks(b.series_id).then(function (books) {
                // ensure any bundle items from different/merged state still show
                var ids = new Set(books.map(function(x){ return x.id; }));
                b.items.forEach(function (it) {
                    if (!ids.has(it.book_id)) {
                        books.push({ id: it.book_id, title: it.title, volume_number: it.volume_number, price: it.price });
                    }
                });
                renderItemsPicker('edit_items_list', books, preselected);
                updateSavings('edit_items_list', 'edit_price', 'edit_savings');
                new bootstrap.Modal(document.getElementById('editModal')).show();
            });
        });
}

document.getElementById('edit_series_id').addEventListener('change', function () {
    var id = this.value;
    if (!id) return;
    loadSeriesBooks(id).then(function (books) {
        renderItemsPicker('edit_items_list', books, {});
        updateSavings('edit_items_list', 'edit_price', 'edit_savings');
    });
});

attachSavingsListeners('edit_items_list', 'edit_price', 'edit_savings');

document.getElementById('saveEditBtn').addEventListener('click', function () {
    if (!editBundleId) return;
    var items = collectItems('edit_items_list');
    if (items.length === 0) { showToast('اختر جزءاً واحداً على الأقل', 'danger'); return; }

    var fd = new FormData();
    fd.append('_token', csrfToken);
    fd.append('series_id', document.getElementById('edit_series_id').value);
    fd.append('title', document.getElementById('edit_title').value);
    fd.append('description', document.getElementById('edit_description').value);
    fd.append('price', document.getElementById('edit_price').value);
    fd.append('quantity', document.getElementById('edit_quantity').value);
    var f = document.getElementById('edit_image');
    if (f.files.length > 0) fd.append('image', f.files[0]);
    items.forEach(function (it, i) {
        fd.append('items['+i+'][book_id]', it.book_id);
        fd.append('items['+i+'][quantity]', it.quantity);
    });

    var btn = this; btn.disabled = true; btn.textContent = '...';
    fetch('/admin/bundles/' + editBundleId, {
        method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }, body: fd
    })
    .then(function(r){ return r.json(); })
    .then(function(d){
        if (d.success) {
            showToast(d.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
            setTimeout(function(){ location.reload(); }, 700);
        } else { showToast(d.message || 'حدث خطأ', 'danger'); }
    })
    .catch(function(){ showToast('خطأ في الاتصال', 'danger'); })
    .finally(function(){ btn.disabled = false; btn.textContent = 'حفظ'; });
});

// ── Delete ──
var deleteBundleId = null;
function confirmDelete(id, name) {
    deleteBundleId = id;
    document.getElementById('deleteModalBody').textContent = 'حذف الباقة "' + name + '"؟';
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
document.getElementById('confirmDeleteBtn').addEventListener('click', function () {
    if (!deleteBundleId) return;
    var btn = this; btn.disabled = true; btn.textContent = '...';
    fetch('/admin/bundles/' + deleteBundleId, {
        method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
    })
    .then(function(r){ return r.json(); })
    .then(function(d){
        bootstrap.Modal.getInstance(document.getElementById('deleteModal')).hide();
        if (d.success) { showToast(d.message, 'success'); setTimeout(function(){ location.reload(); }, 700); }
        else { showToast(d.message, 'danger'); }
    })
    .catch(function(){ showToast('خطأ في الاتصال', 'danger'); })
    .finally(function(){ btn.disabled = false; btn.textContent = 'حذف'; });
});
</script>
</body>
</html>
