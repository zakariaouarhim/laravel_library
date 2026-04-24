<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>إدارة الاقتباسات</title>
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
                        <i class="fas fa-quote-right"></i>
                        إدارة الاقتباسات
                    </h1>
                </div>

                <!-- Stats Cards -->
                <div class="stats-row">
                    <div class="stat-card">
                        <div class="stat-label">إجمالي الاقتباسات</div>
                        <div class="stat-value">{{ $totalQuotes ?? 0 }}</div>
                    </div>
                    <div class="stat-card" style="border-left-color: #27ae60;">
                        <div class="stat-label">الظاهرة للجمهور</div>
                        <div class="stat-value">{{ $visibleQuotes ?? 0 }}</div>
                    </div>
                    <div class="stat-card" style="border-left-color: #95a5a6;">
                        <div class="stat-label">المخفية</div>
                        <div class="stat-value">{{ $hiddenQuotes ?? 0 }}</div>
                    </div>
                    <div class="stat-card" style="border-left-color: #f39c12;">
                        <div class="stat-label">كتب لها اقتباسات</div>
                        <div class="stat-value">{{ $booksWithQuotes ?? 0 }}</div>
                    </div>
                </div>

                <!-- Search and Filter Section -->
                <div class="search-section">
                    <form action="{{ route('admin.quotes.index') }}" method="GET" class="search-controls" style="width: 100%; display: flex; gap: 15px;">

                        <div class="form-group" style="flex: 1;">
                            <label for="searchInput">بحث</label>
                            <div class="input-group">
                                <input
                                    type="text"
                                    name="search"
                                    id="searchInput"
                                    class="form-control"
                                    placeholder="ابحث في النص أو اسم الكتاب أو المستخدم..."
                                    value="{{ request('search') }}"
                                >
                                <button class="btn btn-outline-primary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>

                        <div class="form-group" style="width: 220px;">
                            <label for="visibilityFilter">الحالة</label>
                            <select name="visibility" id="visibilityFilter" class="form-select" onchange="this.form.submit()">
                                <option value="">الكل</option>
                                <option value="visible" {{ request('visibility') === 'visible' ? 'selected' : '' }}>الظاهرة فقط</option>
                                <option value="hidden"  {{ request('visibility') === 'hidden'  ? 'selected' : '' }}>المخفية فقط</option>
                            </select>
                        </div>

                        <div class="form-group" style="display: flex; align-items: end;">
                            <a href="{{ route('admin.quotes.index') }}" class="btn-add" style="background: #95a5a6; text-decoration: none; padding: 8px 15px; display: inline-block;">
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
                        <table class="table table-hover" id="quotesTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>الكتاب</th>
                                    <th>المستخدم</th>
                                    <th>النص</th>
                                    <th>الإعجابات</th>
                                    <th>التاريخ</th>
                                    <th>الحالة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($quotes ?? [] as $quote)
                                <tr data-quote-id="{{ $quote->id }}">
                                    <td>#{{ $quote->id }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            @if($quote->book && $quote->book->image)
                                                <img src="{{ asset('/' . $quote->book->image) }}" alt="{{ $quote->book->title }}" width="40" height="55" loading="lazy" style="border-radius: 4px;">
                                            @endif
                                            <div class="product-name">{{ $quote->book->title ?? '—' }}</div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="product-author">{{ $quote->user->name ?? '—' }}</div>
                                        <small class="text-muted">{{ $quote->user->email ?? '' }}</small>
                                    </td>
                                    <td>
                                        <div class="product-description" title="{{ $quote->text }}">
                                            {{ \Illuminate\Support\Str::limit($quote->text, 100) }}
                                        </div>
                                    </td>
                                    <td>
                                        <i class="fas fa-heart text-danger"></i> {{ $quote->likes_count ?? 0 }}
                                    </td>
                                    <td>{{ $quote->created_at->diffForHumans() }}</td>
                                    <td>
                                        <span class="badge {{ $quote->is_approved ? 'bg-success' : 'bg-secondary' }}" data-status-badge>
                                            {{ $quote->is_approved ? 'ظاهرة' : 'مخفية' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button
                                                class="btn-action btn-view"
                                                onclick="viewQuote({{ $quote->id }})"
                                                title="عرض النص الكامل"
                                            >
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button
                                                class="btn-action {{ $quote->is_approved ? 'btn-edit' : 'btn-view' }}"
                                                onclick="toggleQuote({{ $quote->id }})"
                                                title="{{ $quote->is_approved ? 'إخفاء' : 'إظهار' }}"
                                                data-toggle-btn
                                            >
                                                <i class="fas {{ $quote->is_approved ? 'fa-eye-slash' : 'fa-check' }}"></i>
                                            </button>
                                            <button
                                                class="btn-action btn-delete"
                                                onclick="deleteQuote({{ $quote->id }})"
                                                title="حذف"
                                            >
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8">
                                        <div class="empty-state">
                                            <i class="fas fa-inbox"></i>
                                            <p>لا توجد اقتباسات</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                @if($quotes instanceof \Illuminate\Pagination\Paginator || $quotes instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <nav>
                    {{ $quotes->links('pagination::bootstrap-4') }}
                </nav>
                @endif
            </main>
        </div>
    </div>

    <!-- View Quote Modal -->
    <div class="modal fade" id="viewQuoteModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-quote-right me-2"></i>تفاصيل الاقتباس
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="viewQuoteContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">جاري التحميل...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js" defer></script>
    <script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    window.viewQuote = function(id) {
        const content = document.getElementById('viewQuoteContent');
        content.innerHTML = `<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>`;
        new bootstrap.Modal(document.getElementById('viewQuoteModal')).show();

        fetch(`/admin/quotes/${id}`, {
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(q => {
            const visibilityBadge = q.is_approved
                ? '<span class="badge bg-success">ظاهرة للجمهور</span>'
                : '<span class="badge bg-secondary">مخفية</span>';
            content.innerHTML = `
                <div class="mb-3">
                    <strong>الكتاب:</strong> ${q.book ? q.book.title : '—'}
                </div>
                <div class="mb-3">
                    <strong>المستخدم:</strong> ${q.user ? `${q.user.name} <small class="text-muted">(${q.user.email})</small>` : '—'}
                </div>
                <div class="mb-3">
                    <strong>الحالة:</strong> ${visibilityBadge}
                </div>
                <div class="mb-3">
                    <strong>الإعجابات:</strong> <i class="fas fa-heart text-danger"></i> ${q.likes_count ?? 0}
                </div>
                <hr>
                <div class="mb-2"><strong>النص:</strong></div>
                <blockquote class="border-start border-3 border-primary ps-3" style="white-space: pre-wrap;">${(q.text || '').replace(/[<>&]/g, c => ({'<':'&lt;','>':'&gt;','&':'&amp;'}[c]))}</blockquote>
            `;
        })
        .catch(err => {
            console.error(err);
            content.innerHTML = '<div class="alert alert-danger">فشل في تحميل البيانات</div>';
        });
    };

    window.toggleQuote = function(id) {
        fetch(`/admin/quotes/${id}/toggle`, {
            method: 'PATCH',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            }
        })
        .then(r => r.json().then(data => ({ ok: r.ok, data })))
        .then(({ ok, data }) => {
            if (!ok || !data.success) {
                alert(data.message || 'فشل تغيير الحالة');
                return;
            }
            const row = document.querySelector(`tr[data-quote-id="${id}"]`);
            if (row) {
                const badge = row.querySelector('[data-status-badge]');
                const btn = row.querySelector('[data-toggle-btn]');
                const icon = btn.querySelector('i');
                if (data.is_approved) {
                    badge.className = 'badge bg-success';
                    badge.textContent = 'ظاهرة';
                    btn.title = 'إخفاء';
                    btn.classList.remove('btn-view');
                    btn.classList.add('btn-edit');
                    icon.className = 'fas fa-eye-slash';
                } else {
                    badge.className = 'badge bg-secondary';
                    badge.textContent = 'مخفية';
                    btn.title = 'إظهار';
                    btn.classList.remove('btn-edit');
                    btn.classList.add('btn-view');
                    icon.className = 'fas fa-check';
                }
            }
        })
        .catch(err => {
            console.error(err);
            alert('حدث خطأ أثناء تغيير الحالة');
        });
    };

    window.deleteQuote = function(id) {
        if (!confirm('هل أنت متأكد من حذف الاقتباس؟ هذا الإجراء لا يمكن التراجع عنه.')) return;

        fetch(`/admin/quotes/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            }
        })
        .then(r => r.json().then(data => ({ ok: r.ok, data })))
        .then(({ ok, data }) => {
            if (ok && data.success) {
                const row = document.querySelector(`tr[data-quote-id="${id}"]`);
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
