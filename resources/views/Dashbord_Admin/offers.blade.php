<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>إدارة العروض</title>
    <link rel="stylesheet" href="{{ asset('css/variables.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components/modal.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/sidebardaschboard.css') }}">
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
        .btn-icon       { background:none;border:none;cursor:pointer;padding:4px 8px;border-radius:6px;transition:.2s; }
        .btn-icon:hover { background:#f0f0f0; }
        .offer-thumb    { width:54px;height:40px;object-fit:cover;border-radius:6px;border:1px solid #eee; }
        /* Book picker */
        .book-picker-wrap { position:relative; }
        .book-suggestions { position:absolute;z-index:1060;width:100%;max-height:240px;overflow-y:auto;display:none; }
        .selected-books   { display:flex;flex-wrap:wrap;gap:.4rem;margin-top:.6rem; }
        .book-chip        { background:#eef3ff;border:1px solid #cdddff;border-radius:20px;padding:.25rem .7rem;font-size:.82rem;display:inline-flex;align-items:center;gap:.4rem; }
        .book-chip button { background:none;border:none;color:#c0392b;cursor:pointer;line-height:1;font-size:.95rem; }
        /* Category browser */
        .cat-browser      { border:1px solid #e3e7ee;border-radius:10px;padding:.7rem; background:#fafbfc; }
        .browse-tabs      { display:flex;gap:.3rem;margin-bottom:.6rem; }
        .browse-tab       { flex:1;background:#fff;border:1px solid #e3e7ee;border-radius:8px;padding:.4rem;font-size:.85rem;cursor:pointer;color:#475569;transition:.15s; }
        .browse-tab:hover { background:#f6f9ff; }
        .browse-tab.active{ background:#2563eb;border-color:#2563eb;color:#fff;font-weight:600; }
        /* Parent/child tree */
        .cat-tree         { border:1px solid #e3e7ee;border-radius:8px;max-height:220px;overflow-y:auto;padding:.3rem;background:#fff; }
        .cat-node         { border-bottom:1px solid #f5f6f8; }
        .cat-node:last-child { border-bottom:none; }
        .cat-node-row     { display:flex;align-items:center;gap:.2rem; }
        .cat-exp          { background:none;border:none;cursor:pointer;color:#7a8aa0;padding:2px 6px;border-radius:6px;flex:none; }
        .cat-exp:hover    { background:#eef3ff;color:#2563eb; }
        .cat-exp.open i   { transform:rotate(-90deg); }
        .cat-exp i        { transition:transform .15s; }
        .cat-exp-spacer   { display:inline-block;width:26px;flex:none; }
        .cat-pick         { background:none;border:none;cursor:pointer;text-align:right;flex:1;padding:.35rem .45rem;border-radius:6px;font-size:.88rem;color:#222; }
        .cat-pick:hover   { background:#f6f9ff; }
        .cat-pick.active  { background:#2563eb;color:#fff;font-weight:600; }
        .cat-children     { padding:.1rem 1.7rem .3rem 0;display:flex;flex-direction:column;gap:.1rem; }
        .cat-child        { font-size:.83rem;color:#475569; }
        .cat-list-toolbar { display:flex;justify-content:space-between;align-items:center;margin-top:.6rem;padding:0 .15rem; }
        .cat-book-list    { border:1px solid #e3e7ee;border-radius:8px;background:#fff;max-height:280px;overflow-y:auto;margin-top:.4rem; }
        .cat-book-row     { display:flex;align-items:center;gap:.6rem;padding:.4rem .6rem;border-bottom:1px solid #f1f3f7;cursor:pointer; }
        .cat-book-row:last-child { border-bottom:none; }
        .cat-book-row:hover { background:#f6f9ff; }
        .cat-book-row img { width:32px;height:44px;object-fit:cover;border-radius:4px;border:1px solid #eee;flex:none; }
        .cat-book-row .no-img { width:32px;height:44px;border-radius:4px;background:#eef1f5;display:flex;align-items:center;justify-content:center;color:#aab;flex:none; }
        .cat-book-row .meta { flex:1;min-width:0; }
        .cat-book-row .meta .t { font-size:.85rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
        .cat-book-row .meta .s { font-size:.75rem;color:#6b7280; }
        .badge-stock-out  { background:#f8d7da;color:#721c24;border-radius:12px;padding:1px 8px;font-size:.7rem; }
        .selected-head    { display:flex;justify-content:space-between;align-items:center; }
        .cat-empty        { padding:.8rem;text-align:center;color:#9aa3b2;font-size:.85rem; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        @include('Dashbord_Admin.Sidebar')

        <main class="col main-content">
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
                <h1 class="fs-4 fw-bold mb-0"><i class="fas fa-tags me-2 text-primary"></i>إدارة العروض</h1>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createModal"
                        onclick="resetCreateForm()">
                    <i class="fas fa-plus me-1"></i> إنشاء عرض جديد
                </button>
            </div>

            <!-- Stats -->
            <div class="row g-3 mb-4">
                @php
                    $total  = \App\Models\Offer::count();
                    $active = \App\Models\Offer::active()->count();
                    $ended  = \App\Models\Offer::whereNotNull('ends_at')->where('ends_at', '<', now())->count();
                @endphp
                <div class="col-md-4"><div class="stat-card"><div class="text-muted small">إجمالي العروض</div><div class="fs-3 fw-bold">{{ $total }}</div></div></div>
                <div class="col-md-4"><div class="stat-card green"><div class="text-muted small">جارية الآن</div><div class="fs-3 fw-bold text-success">{{ $active }}</div></div></div>
                <div class="col-md-4"><div class="stat-card orange"><div class="text-muted small">منتهية</div><div class="fs-3 fw-bold text-warning">{{ $ended }}</div></div></div>
            </div>

            <!-- Filter Bar -->
            <form method="GET" action="{{ route('admin.offers.index') }}" class="filter-bar">
                <div class="form-group">
                    <label class="form-label small">بحث بالعنوان</label>
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="عنوان العرض..." value="{{ request('search') }}">
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
                    <a href="{{ route('admin.offers.index') }}" class="btn btn-sm btn-outline-secondary mt-4">إعادة تعيين</a>
                </div>
            </form>

            <!-- Table -->
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>الصورة</th>
                                    <th>العنوان</th>
                                    <th>العرض</th>
                                    <th>الكتب</th>
                                    <th>الفترة</th>
                                    <th>الحالة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($offers as $offer)
                                <tr id="row-{{ $offer->id }}">
                                    <td>
                                        @if($offer->banner_image)
                                            <img src="{{ asset($offer->banner_image) }}" class="offer-thumb" alt="">
                                        @else
                                            <span class="text-muted"><i class="fas fa-image"></i></span>
                                        @endif
                                    </td>
                                    <td><strong>{{ $offer->title }}</strong></td>
                                    <td><span class="badge bg-info text-dark">{{ $offer->quantity }} كتاب بـ {{ number_format($offer->fixed_price, 0) }} د.م</span></td>
                                    <td>{{ $offer->books_count }}</td>
                                    <td class="small text-muted">
                                        {{ $offer->starts_at ? $offer->starts_at->format('d-m-Y') : 'الآن' }}
                                        ←
                                        {{ $offer->ends_at ? $offer->ends_at->format('d-m-Y') : 'بلا نهاية' }}
                                    </td>
                                    <td>
                                        <span class="badge-{{ $offer->is_active ? 'active' : 'inactive' }}" id="status-{{ $offer->id }}">
                                            {{ $offer->is_active ? 'مفعّل' : 'معطّل' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display:flex;gap:.3rem;">
                                            <button class="btn-icon text-warning" title="تفعيل/تعطيل" onclick="toggleOffer({{ $offer->id }})">
                                                <i class="fas fa-toggle-{{ $offer->is_active ? 'on' : 'off' }}" id="toggle-icon-{{ $offer->id }}"></i>
                                            </button>
                                            <button class="btn-icon text-primary" title="تعديل"
                                                    onclick="openEdit({{ Illuminate\Support\Js::from([
                                                        "id" => $offer->id,
                                                        "title" => $offer->title,
                                                        "description" => $offer->description,
                                                        "quantity" => $offer->quantity,
                                                        "fixed_price" => $offer->fixed_price,
                                                        "min_price" => $offer->min_price,
                                                        "max_price" => $offer->max_price,
                                                        "starts_at" => optional($offer->starts_at)->format("Y-m-d\TH:i"),
                                                        "ends_at" => optional($offer->ends_at)->format("Y-m-d\TH:i"),
                                                        "is_active" => $offer->is_active,
                                                        "meta_title" => $offer->meta_title,
                                                        "meta_description" => $offer->meta_description,
                                                        "banner_image" => $offer->banner_image,
                                                        "books" => $offer->books->map(fn($b) => ["id" => $b->id, "title" => $b->title, "author" => optional($b->primaryAuthor)->name]),
                                                        "excluded" => \App\Models\Book::whereIn("id", $offer->excluded_book_ids ?? [])->get(["id", "title"])->map(fn($b) => ["id" => $b->id, "title" => $b->title]),
                                                        "series_units" => \App\Models\Series::whereIn("id", $offer->series_ids ?? [])->withCount("books")->get(["id", "name"])->map(fn($s) => ["id" => $s->id, "label" => $s->name, "count" => $s->books_count]),
                                                        "bundle_units" => \App\Models\Book::whereIn("id", $offer->bundle_ids ?? [])->withCount("items")->get(["id", "title"])->map(fn($b) => ["id" => $b->id, "label" => $b->title, "count" => $b->items_count]),
                                                    ]) }})">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-icon text-danger" title="حذف" onclick="confirmDelete({{ $offer->id }}, '{{ addslashes($offer->title) }}')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted py-4">لا توجد عروض</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if($offers instanceof \Illuminate\Pagination\LengthAwarePaginator || $offers instanceof \Illuminate\Pagination\Paginator)
            <nav class="mt-3">{{ $offers->links('pagination::bootstrap-4') }}</nav>
            @endif
        </main>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>إنشاء عرض جديد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('admin.offers.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    @include('Dashbord_Admin.partials.offer-form', ['ctx' => 'create'])
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
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>تعديل العرض</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editForm" action="" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    @include('Dashbord_Admin.partials.offer-form', ['ctx' => 'edit'])
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Confirm Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white"><h5 class="modal-title">تأكيد الحذف</h5></div>
            <div class="modal-body" id="deleteModalBody">هل أنت متأكد من حذف هذا العرض؟</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-danger btn-sm" id="confirmDeleteBtn">حذف</button>
            </div>
        </div>
    </div>
</div>

<div id="toast-container" style="position:fixed;bottom:1.5rem;left:1.5rem;z-index:9999;"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
const offersBaseUrl = "{{ url('/admin/offers') }}";
const searchBookUrl = "{{ route('admin.search.book') }}";
const pickerBooksUrl = "{{ route('admin.offers.picker-books') }}";

function escapeHtml(s) {
    const d = document.createElement('div');
    d.textContent = s == null ? '' : String(s);
    return d.innerHTML;
}

function showToast(message, type = 'success') {
    const bg = type === 'success' ? '#27ae60' : '#e74c3c';
    const el = document.createElement('div');
    el.style = `background:${bg};color:#fff;padding:.75rem 1.2rem;border-radius:8px;margin-top:.5rem;font-family:'Cairo',sans-serif;box-shadow:0 4px 12px rgba(0,0,0,.15);`;
    el.textContent = message;
    document.getElementById('toast-container').appendChild(el);
    setTimeout(() => el.remove(), 3500);
}

// ============ Book picker — one unified selection per ctx ============
// selection is the single source of truth; it renders the chips (+ hidden
// book_ids[] inputs), the counter, and the category checklist's checked state.
const pickers = {};

function initPicker(ctx) {
    if (pickers[ctx]) return pickers[ctx];

    const chips    = document.getElementById(`selectedBooks_${ctx}`);
    const countEl  = document.getElementById(`selectedCount_${ctx}`);
    const clearBtn = document.getElementById(`clearAll_${ctx}`);
    const listEl   = document.getElementById(`catBookList_${ctx}`);
    const selection = new Map(); // id(string) -> {title, author}

    function syncCheckboxes() {
        if (!listEl) return;
        listEl.querySelectorAll('input[type="checkbox"][data-id]').forEach(cb => {
            cb.checked = selection.has(cb.dataset.id);
        });
    }
    function render() {
        chips.innerHTML = '';
        selection.forEach((info, id) => {
            const chip = document.createElement('span');
            chip.className = 'book-chip';
            chip.innerHTML = `<input type="hidden" name="book_ids[]" value="${id}"><span></span><button type="button" title="إزالة">&times;</button>`;
            chip.querySelector('span').textContent = info.title + (info.author ? ' — ' + info.author : '');
            chip.querySelector('button').addEventListener('click', () => removeBook(id));
            chips.appendChild(chip);
        });
        countEl.textContent = selection.size;
        clearBtn.style.display = selection.size ? '' : 'none';
        syncCheckboxes();
    }
    function addBook(id, title, author) {
        id = String(id);
        if (!selection.has(id)) selection.set(id, { title: title || '', author: author || '' });
        render();
    }
    function removeBook(id) {
        id = String(id);
        if (selection.delete(id)) render();
    }
    function clearAll() { selection.clear(); render(); }
    function has(id) { return selection.has(String(id)); }

    const api = { ctx, addBook, removeBook, clearAll, has, render, syncCheckboxes };
    pickers[ctx] = api;

    clearBtn.addEventListener('click', clearAll);
    initUnitPicker(ctx);
    initSearch(api);
    initCategoryBrowser(api);
    render();
    return api;
}

// ---- series/bundle units (added whole; counted by book count) ----
const unitPickers = {};
function initUnitPicker(ctx) {
    const wrap = document.getElementById(`selectedUnitsWrap_${ctx}`);
    const list = document.getElementById(`selectedUnits_${ctx}`);
    if (!list) return;
    const units = new Map(); // `${type}:${id}` -> {type, id, label, count}

    function render() {
        list.innerHTML = '';
        units.forEach((u, key) => {
            const field = u.type === 'series' ? 'series_ids[]' : 'bundle_ids[]';
            const chip = document.createElement('span');
            chip.className = 'book-chip';
            chip.innerHTML = `<input type="hidden" name="${field}" value="${u.id}"><span></span><button type="button" title="إزالة">&times;</button>`;
            chip.querySelector('span').textContent = `${u.type === 'series' ? 'سلسلة' : 'باقة'}: ${u.label} (${u.count})`;
            chip.querySelector('button').addEventListener('click', () => { units.delete(key); render(); });
            list.appendChild(chip);
        });
        wrap.style.display = units.size ? '' : 'none';
    }
    function addUnit(type, id, label, count) {
        const key = `${type}:${id}`;
        if (!units.has(key)) { units.set(key, { type, id: String(id), label, count }); render(); }
    }
    unitPickers[ctx] = { addUnit, clear: () => { units.clear(); render(); } };
}

// ---- search-by-title (adds into the shared selection) ----
function initSearch(api) {
    const ctx = api.ctx;
    const input       = document.getElementById(`bookSearch_${ctx}`);
    const suggestions = document.getElementById(`bookSuggestions_${ctx}`);
    if (!input) return;
    let timer = null, abort = null;
    function hide() { suggestions.style.display = 'none'; suggestions.innerHTML = ''; }

    input.addEventListener('input', function () {
        const q = input.value.trim();
        clearTimeout(timer);
        if (q.length < 3) { hide(); return; }
        timer = setTimeout(() => {
            if (abort) abort.abort();
            abort = new AbortController();
            fetch(`${searchBookUrl}?q=${encodeURIComponent(q)}`, { headers: { 'Accept': 'application/json' }, signal: abort.signal })
                .then(r => r.ok ? r.json() : [])
                .then(books => {
                    if (!books.length) { hide(); return; }
                    suggestions.innerHTML = books.map(b => {
                        const author = b.primary_author ? b.primary_author.name : '';
                        return `<button type="button" class="list-group-item list-group-item-action"
                            data-id="${b.id}" data-title="${(b.title||'').replace(/"/g,'&quot;')}" data-author="${(author||'').replace(/"/g,'&quot;')}">
                            ${b.title}${author ? ' <small class="text-muted">— ' + author + '</small>' : ''}</button>`;
                    }).join('');
                    suggestions.className = 'list-group book-suggestions';
                    suggestions.style.display = 'block';
                })
                .catch(() => {});
        }, 250);
    });
    suggestions.addEventListener('mousedown', function (e) {
        const btn = e.target.closest('button[data-id]');
        if (!btn) return;
        e.preventDefault();
        api.addBook(btn.dataset.id, btn.dataset.title, btn.dataset.author);
        input.value = '';
        hide();
    });
    input.addEventListener('blur', () => setTimeout(hide, 150));
}

// ---- browse checklist: category tree + series/bundle lists (adds into the shared selection) ----
function initCategoryBrowser(api) {
    const ctx = api.ctx;
    const browser  = document.querySelector(`#browseTabs_${ctx}`)?.closest('.cat-browser');
    const tabs     = document.getElementById(`browseTabs_${ctx}`);
    const incChild = document.getElementById(`includeChildren_${ctx}`);
    const filter   = document.getElementById(`catFilter_${ctx}`);
    const toolbar  = document.getElementById(`catToolbar_${ctx}`);
    const countEl  = document.getElementById(`catCount_${ctx}`);
    const listEl   = document.getElementById(`catBookList_${ctx}`);
    const selAll   = document.getElementById(`catSelectAll_${ctx}`);
    const deselAll = document.getElementById(`catDeselectAll_${ctx}`);
    if (!browser) return;

    const panels = {
        category: document.getElementById(`panelCategory_${ctx}`),
        series:   document.getElementById(`panelSeries_${ctx}`),
        bundle:   document.getElementById(`panelBundle_${ctx}`),
    };
    let loaded = [], abort = null, currentSource = 'category', currentId = null;

    // Expand / collapse a parent to reveal its child categories.
    browser.querySelectorAll('.cat-exp').forEach(btn => {
        btn.addEventListener('click', () => {
            const children = btn.closest('.cat-node').querySelector('.cat-children');
            if (!children) return;
            const show = children.hasAttribute('hidden');
            children.toggleAttribute('hidden', !show);
            btn.classList.toggle('open', show);
        });
    });

    // Categories -> load a checklist of loose books. Series/bundle -> add a whole unit.
    browser.querySelectorAll('.cat-pick').forEach(btn => {
        btn.addEventListener('click', () => {
            const src = btn.dataset.source;
            if (src === 'series' || src === 'bundle') {
                const label = btn.textContent.replace(/\(.*?\)\s*$/, '').trim();
                const up = unitPickers[ctx];
                if (up) up.addUnit(src, btn.dataset.id, label, parseInt(btn.dataset.count || '0', 10));
                return;
            }
            browser.querySelectorAll('.cat-pick.active').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            currentSource = src;
            currentId = btn.dataset.id;
            filter.value = '';
            load();
        });
    });

    // Switch browse mode (tabs).
    tabs.querySelectorAll('.browse-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            tabs.querySelectorAll('.browse-tab').forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            const src = tab.dataset.source;
            Object.entries(panels).forEach(([k, el]) => { if (el) el.style.display = (k === src) ? '' : 'none'; });
            // Reset the result list when changing mode (selection is preserved).
            browser.querySelectorAll('.cat-pick.active').forEach(b => b.classList.remove('active'));
            currentId = null; loaded = [];
            listEl.style.display = 'none'; toolbar.style.display = 'none'; filter.style.display = 'none';
        });
    });

    function visible() {
        const q = (filter.value || '').trim().toLowerCase();
        return q ? loaded.filter(b => (b.title || '').toLowerCase().includes(q)) : loaded;
    }
    function renderList() {
        const books = visible();
        if (!loaded.length) {
            listEl.innerHTML = `<div class="cat-empty">لا توجد كتب هنا</div>`;
        } else if (!books.length) {
            listEl.innerHTML = `<div class="cat-empty">لا نتائج للتصفية</div>`;
        } else {
            listEl.innerHTML = books.map(b => {
                const img = b.image ? `<img src="/${b.image}" alt="">` : `<span class="no-img"><i class="fas fa-book"></i></span>`;
                const stock = b.in_stock ? '' : `<span class="badge-stock-out">نفد</span>`;
                const sub = (b.author ? escapeHtml(b.author) + ' ' : '') + (b.price != null ? `· ${b.price} د.م` : '');
                return `<label class="cat-book-row">
                    <input type="checkbox" data-id="${b.id}" ${api.has(b.id) ? 'checked' : ''}>
                    ${img}
                    <span class="meta"><span class="t">${escapeHtml(b.title)}</span><span class="s">${sub}</span></span>
                    ${stock}
                </label>`;
            }).join('');
        }
        countEl.textContent = `${books.length} كتاب`;
        toolbar.style.display = loaded.length ? 'flex' : 'none';
    }
    function load() {
        if (!currentId) { loaded = []; listEl.style.display = 'none'; toolbar.style.display = 'none'; filter.style.display = 'none'; return; }
        listEl.style.display = 'block'; filter.style.display = 'block';
        listEl.innerHTML = `<div class="cat-empty">جاري التحميل…</div>`;
        if (abort) abort.abort();
        abort = new AbortController();
        const params = new URLSearchParams({ source: currentSource, id: currentId });
        if (currentSource === 'category') params.set('include_children', incChild.checked ? 1 : 0);
        fetch(`${pickerBooksUrl}?${params.toString()}`, { headers: { 'Accept': 'application/json' }, signal: abort.signal })
            .then(r => r.ok ? r.json() : { books: [] })
            .then(data => { loaded = data.books || []; renderList(); })
            .catch(() => {});
    }

    listEl.addEventListener('change', function (e) {
        const cb = e.target.closest('input[type="checkbox"][data-id]');
        if (!cb) return;
        const b = loaded.find(x => String(x.id) === cb.dataset.id);
        if (!b) return;
        cb.checked ? api.addBook(b.id, b.title, b.author) : api.removeBook(b.id);
    });
    incChild.addEventListener('change', () => { if (currentSource === 'category') load(); });
    filter.addEventListener('input', renderList);
    selAll.addEventListener('click',  () => visible().forEach(b => api.addBook(b.id, b.title, b.author)));
    deselAll.addEventListener('click', () => visible().forEach(b => api.removeBook(b.id)));

    api.resetBrowser = function () {
        currentSource = 'category'; currentId = null; filter.value = ''; loaded = [];
        tabs.querySelectorAll('.browse-tab').forEach(t => t.classList.toggle('active', t.dataset.source === 'category'));
        Object.entries(panels).forEach(([k, el]) => { if (el) el.style.display = (k === 'category') ? '' : 'none'; });
        browser.querySelectorAll('.cat-pick.active').forEach(b => b.classList.remove('active'));
        browser.querySelectorAll('.cat-children').forEach(c => c.setAttribute('hidden', ''));
        browser.querySelectorAll('.cat-exp.open').forEach(e => e.classList.remove('open'));
        listEl.style.display = 'none'; toolbar.style.display = 'none'; filter.style.display = 'none';
    };
}

initPicker('create');
initPicker('edit');

// ---- live preview of how many books a price rule captures ----
const priceRulePreviews = {};
function initPriceRulePreview(ctx) {
    const minEl = document.getElementById(`min_price_${ctx}`);
    const maxEl = document.getElementById(`max_price_${ctx}`);
    const out   = document.getElementById(`priceRulePreview_${ctx}`);
    if (!minEl || !maxEl || !out) return;
    let timer = null, abort = null;

    function refresh() {
        const min = minEl.value.trim(), max = maxEl.value.trim();
        if (!min && !max) { out.textContent = ''; return; }
        out.innerHTML = '<span class="text-muted">...</span>';
        clearTimeout(timer);
        timer = setTimeout(() => {
            if (abort) abort.abort();
            abort = new AbortController();
            const p = new URLSearchParams({ source: 'price' });
            if (min) p.set('min', min);
            if (max) p.set('max', max);
            fetch(`${pickerBooksUrl}?${p.toString()}`, { headers: { 'Accept': 'application/json' }, signal: abort.signal })
                .then(r => r.ok ? r.json() : { books: [] })
                .then(d => {
                    const n = (d.books || []).length;
                    const label = n >= 300 ? '300+' : n;
                    out.innerHTML = n
                        ? `<span class="text-primary"><i class="fas fa-check-circle me-1"></i>سيشمل تلقائياً ${label} كتاب ضمن هذا النطاق</span>`
                        : `<span class="text-muted">لا توجد كتب ضمن هذا النطاق حالياً</span>`;
                })
                .catch(() => {});
        }, 350);
    }
    function toggleExcludeSection() {
        const section = document.getElementById(`excludeSection_${ctx}`);
        if (section) section.style.display = (minEl.value.trim() || maxEl.value.trim()) ? '' : 'none';
    }
    minEl.addEventListener('input', () => { refresh(); toggleExcludeSection(); });
    maxEl.addEventListener('input', () => { refresh(); toggleExcludeSection(); });
    priceRulePreviews[ctx] = () => { refresh(); toggleExcludeSection(); };
}
initPriceRulePreview('create');
initPriceRulePreview('edit');

// ---- exclude specific books from the price range ----
const excludePickers = {};
function initExcludePicker(ctx) {
    const search = document.getElementById(`excludeSearch_${ctx}`);
    const sugg   = document.getElementById(`excludeSuggestions_${ctx}`);
    const chips  = document.getElementById(`excludedBooks_${ctx}`);
    const minEl  = document.getElementById(`min_price_${ctx}`);
    const maxEl  = document.getElementById(`max_price_${ctx}`);
    if (!search) return;
    const excluded = new Map(); // id -> title
    let timer = null, abort = null;

    function render() {
        chips.innerHTML = '';
        excluded.forEach((title, id) => {
            const chip = document.createElement('span');
            chip.className = 'book-chip';
            chip.innerHTML = `<input type="hidden" name="excluded_book_ids[]" value="${id}"><span></span><button type="button" title="إزالة">&times;</button>`;
            chip.querySelector('span').textContent = title;
            chip.querySelector('button').addEventListener('click', () => { excluded.delete(id); render(); });
            chips.appendChild(chip);
        });
    }
    function addExcluded(id, title) { id = String(id); if (!excluded.has(id)) { excluded.set(id, title || ''); render(); } }
    function hide() { sugg.style.display = 'none'; sugg.innerHTML = ''; }

    search.addEventListener('input', function () {
        const q = search.value.trim();
        clearTimeout(timer);
        if (q.length < 2) { hide(); return; }
        timer = setTimeout(() => {
            const min = minEl.value.trim(), max = maxEl.value.trim();
            if (!min && !max) {
                sugg.innerHTML = '<div class="list-group-item text-muted small">حدّد نطاق السعر أولاً</div>';
                sugg.className = 'list-group book-suggestions'; sugg.style.display = 'block';
                return;
            }
            if (abort) abort.abort();
            abort = new AbortController();
            const p = new URLSearchParams({ source: 'price', q });
            if (min) p.set('min', min);
            if (max) p.set('max', max);
            fetch(`${pickerBooksUrl}?${p.toString()}`, { headers: { 'Accept': 'application/json' }, signal: abort.signal })
                .then(r => r.ok ? r.json() : { books: [] })
                .then(d => {
                    const books = d.books || [];
                    if (!books.length) { hide(); return; }
                    sugg.innerHTML = books.map(b =>
                        `<button type="button" class="list-group-item list-group-item-action" data-id="${b.id}" data-title="${(b.title || '').replace(/"/g, '&quot;')}">`
                        + `${escapeHtml(b.title)}${b.author ? ' <small class="text-muted">— ' + escapeHtml(b.author) + '</small>' : ''}</button>`
                    ).join('');
                    sugg.className = 'list-group book-suggestions'; sugg.style.display = 'block';
                })
                .catch(() => {});
        }, 250);
    });
    sugg.addEventListener('mousedown', function (e) {
        const btn = e.target.closest('button[data-id]');
        if (!btn) return;
        e.preventDefault();
        addExcluded(btn.dataset.id, btn.dataset.title);
        search.value = ''; hide();
    });
    search.addEventListener('blur', () => setTimeout(hide, 150));

    // Bulk-exclude an entire series: add all its books to the exclusion list.
    const seriesSel = document.getElementById(`excludeSeries_${ctx}`);
    const seriesBtn = document.getElementById(`excludeSeriesBtn_${ctx}`);
    if (seriesSel && seriesBtn) {
        seriesBtn.addEventListener('click', function () {
            const sid = seriesSel.value;
            if (!sid) return;
            const orig = seriesBtn.textContent;
            seriesBtn.disabled = true;
            seriesBtn.textContent = '...';
            fetch(`${pickerBooksUrl}?source=series&id=${encodeURIComponent(sid)}`, { headers: { 'Accept': 'application/json' } })
                .then(r => r.ok ? r.json() : { books: [] })
                .then(d => { (d.books || []).forEach(b => addExcluded(b.id, b.title)); })
                .catch(() => {})
                .finally(() => { seriesBtn.disabled = false; seriesBtn.textContent = orig; seriesSel.value = ''; });
        });
    }

    excludePickers[ctx] = { addExcluded, clear: () => { excluded.clear(); render(); } };
}
initExcludePicker('create');
initExcludePicker('edit');

function resetCreateForm() {
    document.querySelector('#createModal form').reset();
    pickers.create.clearAll();
    pickers.create.resetBrowser();
    document.getElementById('priceRulePreview_create').textContent = '';
    document.getElementById('excludeSection_create').style.display = 'none';
    if (excludePickers.create) excludePickers.create.clear();
    if (unitPickers.create) unitPickers.create.clear();
}

// ============ Edit ============
function openEdit(offer) {
    document.getElementById('editForm').action = `${offersBaseUrl}/${offer.id}`;
    document.getElementById('title_edit').value            = offer.title || '';
    document.getElementById('description_edit').value      = offer.description || '';
    document.getElementById('quantity_edit').value         = offer.quantity || 1;
    document.getElementById('fixed_price_edit').value      = offer.fixed_price || 0;
    document.getElementById('min_price_edit').value        = offer.min_price ?? '';
    document.getElementById('max_price_edit').value        = offer.max_price ?? '';
    if (priceRulePreviews.edit) priceRulePreviews.edit();
    if (excludePickers.edit) {
        excludePickers.edit.clear();
        (offer.excluded || []).forEach(b => excludePickers.edit.addExcluded(b.id, b.title));
    }
    if (unitPickers.edit) {
        unitPickers.edit.clear();
        (offer.series_units || []).forEach(u => unitPickers.edit.addUnit('series', u.id, u.label, u.count));
        (offer.bundle_units || []).forEach(u => unitPickers.edit.addUnit('bundle', u.id, u.label, u.count));
    }
    document.getElementById('starts_at_edit').value        = offer.starts_at || '';
    document.getElementById('ends_at_edit').value          = offer.ends_at || '';
    document.getElementById('is_active_edit').checked      = !!offer.is_active;
    document.getElementById('meta_title_edit').value       = offer.meta_title || '';
    document.getElementById('meta_description_edit').value = offer.meta_description || '';

    const current = document.getElementById('currentBanner_edit');
    if (offer.banner_image) {
        current.innerHTML = `<img src="/${offer.banner_image}" style="max-height:60px;border-radius:6px;"> <small class="text-muted d-block">الصورة الحالية — ارفع صورة جديدة لاستبدالها</small>`;
    } else {
        current.innerHTML = '';
    }

    const picker = pickers.edit;
    picker.resetBrowser();
    picker.clearAll();
    (offer.books || []).forEach(b => picker.addBook(b.id, b.title, b.author));

    new bootstrap.Modal(document.getElementById('editModal')).show();
}

// ============ Toggle ============
function toggleOffer(id) {
    fetch(`${offersBaseUrl}/${id}/toggle`, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(data => {
            if (!data.success) return showToast(data.message || 'حدث خطأ', 'error');
            const statusEl = document.getElementById('status-' + id);
            const iconEl   = document.getElementById('toggle-icon-' + id);
            statusEl.className   = data.is_active ? 'badge-active' : 'badge-inactive';
            statusEl.textContent = data.is_active ? 'مفعّل' : 'معطّل';
            iconEl.className     = data.is_active ? 'fas fa-toggle-on' : 'fas fa-toggle-off';
            showToast(data.message);
        })
        .catch(() => showToast('حدث خطأ في الاتصال', 'error'));
}

// ============ Delete ============
let deleteOfferId = null;
function confirmDelete(id, title) {
    deleteOfferId = id;
    document.getElementById('deleteModalBody').textContent = `هل أنت متأكد من حذف العرض "${title}"؟`;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
document.getElementById('confirmDeleteBtn').addEventListener('click', function () {
    if (!deleteOfferId) return;
    fetch(`${offersBaseUrl}/${deleteOfferId}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('row-' + deleteOfferId)?.remove();
                bootstrap.Modal.getInstance(document.getElementById('deleteModal'))?.hide();
                showToast(data.message);
            } else { showToast(data.message || 'حدث خطأ', 'error'); }
        })
        .catch(() => showToast('حدث خطأ في الاتصال', 'error'));
});
</script>
</body>
</html>
