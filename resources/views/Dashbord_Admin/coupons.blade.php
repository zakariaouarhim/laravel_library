<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>إدارة الكوبونات</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/sidebardaschboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/clients.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .badge-active   { background:#d4edda;color:#155724;padding:3px 10px;border-radius:20px;font-size:.8rem;font-weight:600; }
        .badge-inactive { background:#f8d7da;color:#721c24;padding:3px 10px;border-radius:20px;font-size:.8rem;font-weight:600; }
        .filter-bar     { display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-end;margin-bottom:1.5rem; }
        .filter-bar .form-group { flex:1;min-width:180px; }
        .stat-card      { background:#fff;border-radius:12px;padding:1.2rem 1.5rem;box-shadow:0 2px 8px rgba(0,0,0,.07);border-right:4px solid #3498db; }
        .stat-card.green{ border-right-color:#27ae60; }
        .stat-card.orange{ border-right-color:#f39c12; }
        .table th       { background:#f8f9fa;font-weight:700;font-size:.85rem; }
        .coupon-code    { font-family:monospace;font-size:.95rem;font-weight:700;letter-spacing:1px;color:#2c3e50; }
        .btn-icon       { background:none;border:none;cursor:pointer;padding:4px 8px;border-radius:6px;transition:.2s; }
        .btn-icon:hover { background:#f0f0f0; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        @include('Dashbord_Admin.Sidebar')

        <main class="col main-content">
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

            <div class="d-flex justify-content-between align-items-center my-4">
                <h1 class="fs-4 fw-bold mb-0"><i class="fas fa-ticket-alt me-2 text-primary"></i>إدارة الكوبونات</h1>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createModal">
                    <i class="fas fa-plus me-1"></i> إنشاء كوبون جديد
                </button>
            </div>

            <!-- Stats -->
            <div class="row g-3 mb-4">
                @php
                    $total   = \App\Models\Coupon::count();
                    $active  = \App\Models\Coupon::where('is_active', true)->count();
                    $expired = \App\Models\Coupon::where('expires_at', '<', now())->count();
                @endphp
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="text-muted small">إجمالي الكوبونات</div>
                        <div class="fs-3 fw-bold">{{ $total }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card green">
                        <div class="text-muted small">مفعّلة</div>
                        <div class="fs-3 fw-bold text-success">{{ $active }}</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card orange">
                        <div class="text-muted small">منتهية الصلاحية</div>
                        <div class="fs-3 fw-bold text-warning">{{ $expired }}</div>
                    </div>
                </div>
            </div>

            <!-- Filter Bar -->
            <form method="GET" action="{{ route('admin.coupons.index') }}" class="filter-bar">
                <div class="form-group">
                    <label class="form-label small">بحث بالكود</label>
                    <input type="text" name="search" class="form-control form-control-sm"
                           placeholder="أدخل الكود..." value="{{ request('search') }}">
                </div>
                <div class="form-group">
                    <label class="form-label small">الحالة</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">الكل</option>
                        <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>مفعّل</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>معطّل</option>
                    </select>
                </div>
                <div>
                    <button type="submit" class="btn btn-sm btn-outline-primary mt-4">بحث</button>
                    <a href="{{ route('admin.coupons.index') }}" class="btn btn-sm btn-outline-secondary mt-4">إعادة تعيين</a>
                </div>
            </form>

            <!-- Table -->
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>الكود</th>
                                    <th>النوع</th>
                                    <th>القيمة</th>
                                    <th>الحد الأدنى</th>
                                    <th>الاستخدام</th>
                                    <th>انتهاء الصلاحية</th>
                                    <th>الحالة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($coupons as $coupon)
                                <tr id="row-{{ $coupon->id }}">
                                    <td><span class="coupon-code">{{ $coupon->code }}</span></td>
                                    <td>
                                        @if($coupon->type === 'percentage')
                                            <span class="badge bg-info text-dark">نسبة مئوية</span>
                                        @else
                                            <span class="badge bg-secondary">مبلغ ثابت</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($coupon->type === 'percentage')
                                            {{ $coupon->value }}%
                                        @else
                                            {{ number_format($coupon->value, 2) }} ر.س
                                        @endif
                                    </td>
                                    <td>
                                        @if($coupon->min_order_amount > 0)
                                            {{ number_format($coupon->min_order_amount, 2) }} ر.س
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $coupon->used_count }}
                                        @if($coupon->max_uses)
                                            / {{ $coupon->max_uses }}
                                        @else
                                            / ∞
                                        @endif
                                    </td>
                                    <td>
                                        @if($coupon->expires_at)
                                            <span class="{{ $coupon->expires_at->isPast() ? 'text-danger' : 'text-muted' }}">
                                                {{ $coupon->expires_at->format('d-m-Y') }}
                                            </span>
                                        @else
                                            <span class="text-muted">بلا انتهاء</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge-{{ $coupon->is_active ? 'active' : 'inactive' }}" id="status-{{ $coupon->id }}">
                                            {{ $coupon->is_active ? 'مفعّل' : 'معطّل' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display:flex;gap:.3rem;">
                                            <button class="btn-icon text-warning" title="تفعيل/تعطيل"
                                                    onclick="toggleCoupon({{ $coupon->id }})">
                                                <i class="fas fa-toggle-{{ $coupon->is_active ? 'on' : 'off' }}" id="toggle-icon-{{ $coupon->id }}"></i>
                                            </button>
                                            <button class="btn-icon text-primary" title="تعديل"
                                                    onclick="openEdit({{ $coupon->id }}, {{ json_encode($coupon) }})">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-icon text-danger" title="حذف"
                                                    onclick="confirmDelete({{ $coupon->id }}, '{{ $coupon->code }}')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">لا توجد كوبونات</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if($coupons instanceof \Illuminate\Pagination\LengthAwarePaginator || $coupons instanceof \Illuminate\Pagination\Paginator)
            <nav class="mt-3">{{ $coupons->links('pagination::bootstrap-4') }}</nav>
            @endif
        </main>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>إنشاء كوبون جديد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('admin.coupons.store') }}">
                @csrf
                <div class="modal-body">
                    @include('Dashbord_Admin.partials.coupon-form', ['coupon' => null])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">إنشاء</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>تعديل الكوبون</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="editModalBody">
                @include('Dashbord_Admin.partials.coupon-form', ['coupon' => null])
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-primary" onclick="submitEdit()">حفظ</button>
            </div>
        </div>
    </div>
</div>

<!-- Confirm Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">تأكيد الحذف</h5>
            </div>
            <div class="modal-body" id="deleteModalBody">هل أنت متأكد من حذف هذا الكوبون؟</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-danger btn-sm" id="confirmDeleteBtn">حذف</button>
            </div>
        </div>
    </div>
</div>

<div id="toast-container" style="position:fixed;bottom:1.5rem;left:1.5rem;z-index:9999;"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

function showToast(message, type = 'success') {
    const id  = 'toast-' + Date.now();
    const bg  = type === 'success' ? '#27ae60' : '#e74c3c';
    const el  = document.createElement('div');
    el.id     = id;
    el.style  = `background:${bg};color:#fff;padding:.75rem 1.2rem;border-radius:8px;margin-top:.5rem;font-family:'Cairo',sans-serif;box-shadow:0 4px 12px rgba(0,0,0,.15);`;
    el.textContent = message;
    document.getElementById('toast-container').appendChild(el);
    setTimeout(function () { el.remove(); }, 3500);
}

// ----- Toggle active -----
function toggleCoupon(id) {
    fetch(`/admin/coupons/${id}/toggle`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const statusEl = document.getElementById('status-' + id);
            const iconEl   = document.getElementById('toggle-icon-' + id);
            if (data.is_active) {
                statusEl.className   = 'badge-active';
                statusEl.textContent = 'مفعّل';
                iconEl.className     = 'fas fa-toggle-on';
            } else {
                statusEl.className   = 'badge-inactive';
                statusEl.textContent = 'معطّل';
                iconEl.className     = 'fas fa-toggle-off';
            }
            showToast(data.message);
        } else {
            showToast(data.message || 'حدث خطأ', 'error');
        }
    })
    .catch(() => showToast('حدث خطأ في الاتصال', 'error'));
}

// ----- Edit -----
let editCouponId = null;
function openEdit(id, coupon) {
    editCouponId = id;
    const body = document.getElementById('editModalBody');
    body.innerHTML = `
        <div class="mb-3">
            <label class="form-label">الكود *</label>
            <input type="text" id="edit_code" class="form-control" value="${coupon.code}" required style="text-transform:uppercase;">
        </div>
        <div class="mb-3">
            <label class="form-label">نوع الخصم *</label>
            <select id="edit_type" class="form-select">
                <option value="percentage" ${coupon.type === 'percentage' ? 'selected' : ''}>نسبة مئوية (%)</option>
                <option value="fixed"      ${coupon.type === 'fixed'      ? 'selected' : ''}>مبلغ ثابت (ر.س)</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">القيمة *</label>
            <input type="number" id="edit_value" class="form-control" step="0.01" min="0.01" value="${coupon.value}" required>
        </div>
        <div class="mb-3">
            <label class="form-label">الحد الأدنى للطلب (ر.س)</label>
            <input type="number" id="edit_min_order" class="form-control" step="0.01" min="0" value="${coupon.min_order_amount || 0}">
        </div>
        <div class="mb-3">
            <label class="form-label">الحد الأقصى للاستخدام</label>
            <input type="number" id="edit_max_uses" class="form-control" min="1" value="${coupon.max_uses || ''}">
            <div class="form-text">اتركه فارغاً لاستخدام غير محدود</div>
        </div>
        <div class="mb-3">
            <label class="form-label">تاريخ الانتهاء</label>
            <input type="date" id="edit_expires_at" class="form-control" value="${coupon.expires_at ? coupon.expires_at.substr(0,10) : ''}">
        </div>
        <div class="form-check form-switch mb-2">
            <input class="form-check-input" type="checkbox" id="edit_is_active" ${coupon.is_active ? 'checked' : ''}>
            <label class="form-check-label" for="edit_is_active">مفعّل</label>
        </div>
    `;
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

function submitEdit() {
    if (!editCouponId) return;
    const payload = {
        code:             document.getElementById('edit_code').value,
        type:             document.getElementById('edit_type').value,
        value:            document.getElementById('edit_value').value,
        min_order_amount: document.getElementById('edit_min_order').value,
        max_uses:         document.getElementById('edit_max_uses').value,
        expires_at:       document.getElementById('edit_expires_at').value,
        is_active:        document.getElementById('edit_is_active').checked ? 1 : 0,
    };
    fetch(`/admin/coupons/${editCouponId}`, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showToast(data.message);
            setTimeout(() => location.reload(), 800);
        } else {
            showToast(data.message || 'حدث خطأ', 'error');
        }
    })
    .catch(() => showToast('حدث خطأ في الاتصال', 'error'));
}

// ----- Delete -----
let deleteCouponId = null;
function confirmDelete(id, code) {
    deleteCouponId = id;
    document.getElementById('deleteModalBody').textContent = `هل أنت متأكد من حذف الكوبون "${code}"؟`;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
document.getElementById('confirmDeleteBtn').addEventListener('click', function () {
    if (!deleteCouponId) return;
    fetch(`/admin/coupons/${deleteCouponId}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('row-' + deleteCouponId)?.remove();
            bootstrap.Modal.getInstance(document.getElementById('deleteModal'))?.hide();
            showToast(data.message);
        } else {
            showToast(data.message || 'حدث خطأ', 'error');
        }
    })
    .catch(() => showToast('حدث خطأ في الاتصال', 'error'));
});
</script>
</body>
</html>
