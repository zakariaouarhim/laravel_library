<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>إدارة المستخدمين</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/sidebardaschboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/clients.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .role-badge-user       { background:#e3f2fd;color:#1565c0;padding:3px 10px;border-radius:20px;font-size:.8rem;font-weight:600; }
        .role-badge-admin      { background:#fce4ec;color:#c62828;padding:3px 10px;border-radius:20px;font-size:.8rem;font-weight:600; }
        .btn-promote           { background:#27ae60;color:#fff;border:none;border-radius:6px;padding:5px 10px;font-size:.8rem;cursor:pointer; }
        .btn-promote:hover     { background:#1e8449; }
        .btn-demote            { background:#f39c12;color:#fff;border:none;border-radius:6px;padding:5px 10px;font-size:.8rem;cursor:pointer; }
        .btn-demote:hover      { background:#d68910; }
        .filter-bar            { display:flex;gap:1rem;flex-wrap:wrap;align-items:flex-end;margin-bottom:1.5rem; }
        .filter-bar .form-group{ flex:1;min-width:180px; }
        .stat-card             { background:#fff;border-radius:12px;padding:1.2rem 1.5rem;box-shadow:0 2px 8px rgba(0,0,0,.07);border-right:4px solid #3498db; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        @include('Dashbord_Admin.Sidebar')

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">

            <!-- Page Header -->
            <div class="page-header">
                <h1>
                    <i class="fas fa-user-shield"></i>
                    إدارة المستخدمين
                    <span style="font-size:.75rem;background:#c0392b;color:#fff;padding:2px 10px;border-radius:20px;vertical-align:middle;">مشرف عام</span>
                </h1>
            </div>

            <!-- Stats -->
            <div class="stats-row" style="display:flex;gap:1rem;margin-bottom:1.5rem;flex-wrap:wrap;">
                <div class="stat-card" style="flex:1;min-width:150px;">
                    <div class="stat-label">إجمالي الزبائن</div>
                    <div class="stat-value">{{ $totalUsers }}</div>
                </div>
                <div class="stat-card" style="flex:1;min-width:150px;border-right-color:#e74c3c;">
                    <div class="stat-label">المشرفون</div>
                    <div class="stat-value">{{ $totalAdmins }}</div>
                </div>
                <div class="stat-card" style="flex:1;min-width:150px;border-right-color:#27ae60;">
                    <div class="stat-label">جدد هذا الشهر</div>
                    <div class="stat-value">{{ $newThisMonth }}</div>
                </div>
            </div>

            <!-- Filters -->
            <form method="GET" action="{{ route('admin.users.index') }}" class="filter-bar">
                <div class="form-group">
                    <label>بحث</label>
                    <input type="text" name="search" class="form-control" placeholder="الاسم أو البريد الإلكتروني..."
                           value="{{ request('search') }}" maxlength="100">
                </div>
                <div class="form-group" style="max-width:200px;">
                    <label>الدور</label>
                    <select name="role" class="form-select">
                        <option value="">الكل</option>
                        <option value="user"  {{ request('role') === 'user'  ? 'selected' : '' }}>زبون</option>
                        <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>مشرف</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="height:38px;">
                    <i class="fas fa-search me-1"></i>بحث
                </button>
                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary" style="height:38px;">
                    <i class="fas fa-redo me-1"></i>إعادة تعيين
                </a>
            </form>

            <!-- Alerts -->
            @if(session('success'))
                <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>{{ session('error') }}</div>
            @endif

            <!-- Table -->
            <div class="table-section">
                <div class="table-responsive">
                    <table class="table table-hover" id="usersTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>المستخدم</th>
                                <th>البريد الإلكتروني</th>
                                <th>الدور</th>
                                <th>تاريخ التسجيل</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $user)
                            <tr id="user-row-{{ $user->id }}">
                                <td>#{{ $user->id }}</td>
                                <td>
                                    <div class="client-name">
                                        <div class="client-avatar">{{ substr($user->name, 0, 1) }}</div>
                                        {{ $user->name }}
                                    </div>
                                </td>
                                <td><a href="mailto:{{ $user->email }}" class="client-email">{{ $user->email }}</a></td>
                                <td>
                                    @if($user->role === 'admin')
                                        <span class="role-badge-admin"><i class="fas fa-shield-alt me-1"></i>مشرف</span>
                                    @else
                                        <span class="role-badge-user"><i class="fas fa-user me-1"></i>زبون</span>
                                    @endif
                                </td>
                                <td>{{ $user->created_at?->format('d-m-Y') ?? '-' }}</td>
                                <td>
                                    <div class="action-buttons" style="gap:.4rem;display:flex;flex-wrap:wrap;">
                                        @if($user->role === 'user')
                                            <button class="btn-promote" onclick="changeRole({{ $user->id }}, 'promote')" title="ترقية إلى مشرف">
                                                <i class="fas fa-arrow-up me-1"></i>ترقية
                                            </button>
                                        @else
                                            <button class="btn-demote" onclick="changeRole({{ $user->id }}, 'demote')" title="تخفيض إلى زبون">
                                                <i class="fas fa-arrow-down me-1"></i>تخفيض
                                            </button>
                                        @endif
                                        <button class="btn-action btn-delete" onclick="deleteUser({{ $user->id }})" title="حذف">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <i class="fas fa-inbox"></i>
                                        <p>لا يوجد مستخدمون</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            
            @if($users instanceof \Illuminate\Pagination\Paginator || $users instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <nav>
                    {{ $users->links('pagination::bootstrap-4') }}
                </nav>
            @endif
        </main>
    </div>
</div>

<!-- Confirm Modal -->
<div class="modal fade" id="confirmModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmTitle">تأكيد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="confirmBody">هل أنت متأكد؟</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-danger" id="confirmBtn">تأكيد</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
let confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
let pendingAction = null;

function changeRole(userId, action) {
    const isPromote = action === 'promote';
    document.getElementById('confirmTitle').textContent = isPromote ? 'ترقية مستخدم' : 'تخفيض مشرف';
    document.getElementById('confirmBody').textContent  = isPromote
        ? 'هل تريد ترقية هذا المستخدم إلى مشرف؟'
        : 'هل تريد تخفيض هذا المشرف إلى زبون؟';
    document.getElementById('confirmBtn').className = isPromote
        ? 'btn btn-success' : 'btn btn-warning';

    pendingAction = () => {
        fetch(`/admin/users/${userId}/${action}`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            confirmModal.hide();
            if (data.success) {
                showAlert(data.message, 'success');
                setTimeout(() => location.reload(), 1200);
            } else {
                showAlert(data.message, 'danger');
            }
        })
        .catch(() => showAlert('حدث خطأ، يرجى المحاولة لاحقاً', 'danger'));
    };

    confirmModal.show();
}

function deleteUser(userId) {
    document.getElementById('confirmTitle').textContent = 'حذف مستخدم';
    document.getElementById('confirmBody').textContent  = 'هل تريد حذف هذا المستخدم نهائياً؟ لا يمكن التراجع عن هذه العملية.';
    document.getElementById('confirmBtn').className = 'btn btn-danger';

    pendingAction = () => {
        fetch(`/admin/client/${userId}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            confirmModal.hide();
            if (data.success) {
                showAlert(data.message, 'success');
                const row = document.getElementById(`user-row-${userId}`);
                if (row) row.remove();
            } else {
                showAlert(data.message, 'danger');
            }
        })
        .catch(() => showAlert('حدث خطأ، يرجى المحاولة لاحقاً', 'danger'));
    };

    confirmModal.show();
}

document.getElementById('confirmBtn').addEventListener('click', () => {
    if (pendingAction) pendingAction();
});

function showAlert(message, type) {
    const div = document.createElement('div');
    div.className = `alert alert-${type} alert-dismissible fade show`;
    div.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
    document.querySelector('.main-content').prepend(div);
    setTimeout(() => div.remove(), 4000);
}
</script>
</body>
</html>
