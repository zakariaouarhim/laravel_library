<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>كاروسيلات الصفحة الرئيسية</title>
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
        .table th       { background:#f8f9fa;font-weight:700;font-size:.85rem; }
        .btn-icon       { background:none;border:none;cursor:pointer;padding:4px 8px;border-radius:6px;transition:.2s; }
        .btn-icon:hover { background:#f0f0f0; }
        .source-pill    { background:#eef3ff;border:1px solid #cdddff;border-radius:20px;padding:.15rem .6rem;font-size:.78rem; }
        /* Book picker */
        .book-picker-wrap { position:relative; }
        .book-suggestions { position:absolute;z-index:1060;width:100%;max-height:240px;overflow-y:auto;display:none; }
        .selected-books   { display:flex;flex-wrap:wrap;gap:.4rem;margin-top:.6rem; }
        .book-chip        { background:#eef3ff;border:1px solid #cdddff;border-radius:20px;padding:.25rem .7rem;font-size:.82rem;display:inline-flex;align-items:center;gap:.4rem; }
        .book-chip button { background:none;border:none;color:#c0392b;cursor:pointer;line-height:1;font-size:.95rem; }
        /* Category tree */
        .category-tree   { border:1px solid #e3e7ee;border-radius:10px;max-height:300px;overflow-y:auto;padding:.4rem; }
        .cat-parent      { border-bottom:1px solid #f1f3f7; }
        .cat-parent:last-child { border-bottom:none; }
        .cat-parent-row  { display:flex;align-items:center;gap:.4rem;padding:.35rem .25rem; }
        .cat-toggle      { background:none;border:none;cursor:pointer;color:#7a8aa0;padding:2px 6px;border-radius:6px;transition:.15s; }
        .cat-toggle:hover{ background:#eef3ff;color:#2563eb; }
        .cat-toggle.open i { transform:rotate(-90deg); }
        .cat-toggle i    { transition:transform .15s; }
        .cat-toggle-spacer { display:inline-block;width:28px; }
        .cat-label       { display:flex;align-items:center;gap:.45rem;margin:0;cursor:pointer;font-size:.9rem;flex:1; }
        .cat-children    { padding:.1rem 0 .4rem 0; }
        .cat-child       { padding:.25rem .25rem .25rem 2.6rem;font-size:.85rem;color:#475569; }
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
                <h1 class="fs-4 fw-bold mb-0"><i class="fas fa-images me-2 text-primary"></i>كاروسيلات الصفحة الرئيسية</h1>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createModal"
                        onclick="resetCreateForm()">
                    <i class="fas fa-plus me-1"></i> كاروسيل جديد
                </button>
            </div>

            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>الترتيب</th>
                                    <th>العنوان</th>
                                    <th>المصدر</th>
                                    <th>عدد الكتب</th>
                                    <th>الحالة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                            @forelse($carousels as $carousel)
                                <tr id="row-{{ $carousel->id }}">
                                    <td><span class="badge bg-secondary">{{ $carousel->sort_order }}</span></td>
                                    <td>
                                        <strong>{{ $carousel->title }}</strong>
                                        @if($carousel->is_system)
                                            <span class="badge bg-dark ms-1" title="كاروسيل مدمج — يُدار محتواه تلقائياً">مدمج</span>
                                        @endif
                                    </td>
                                    <td class="small">
                                        @if($carousel->is_system)
                                            <span class="source-pill"><i class="fas fa-cube me-1"></i>تلقائي</span>
                                        @elseif($carousel->source_type === 'author')
                                            <span class="source-pill"><i class="fas fa-user-pen me-1"></i>{{ optional($carousel->author)->name ?? '—' }}</span>
                                        @elseif($carousel->source_type === 'manual')
                                            <span class="source-pill"><i class="fas fa-hand-pointer me-1"></i>اختيار يدوي</span>
                                        @else
                                            @forelse($carousel->categories as $cat)
                                                <span class="source-pill mb-1">{{ $cat->name }}</span>
                                            @empty
                                                <span class="text-muted">—</span>
                                            @endforelse
                                        @endif
                                    </td>
                                    <td>
                                        @if($carousel->source_type === 'manual')
                                            {{ $carousel->books_count }} / {{ $carousel->book_limit }}
                                        @else
                                            حتى {{ $carousel->book_limit }}
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge-{{ $carousel->is_active ? 'active' : 'inactive' }}" id="status-{{ $carousel->id }}">
                                            {{ $carousel->is_active ? 'مفعّل' : 'معطّل' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div style="display:flex;gap:.3rem;">
                                            <button class="btn-icon text-warning" title="تفعيل/تعطيل" onclick="toggleCarousel({{ $carousel->id }})">
                                                <i class="fas fa-toggle-{{ $carousel->is_active ? 'on' : 'off' }}" id="toggle-icon-{{ $carousel->id }}"></i>
                                            </button>
                                            <button class="btn-icon text-primary" title="تعديل"
                                                    onclick="openEdit({{ Illuminate\Support\Js::from([
                                                        'id' => $carousel->id,
                                                        'title' => $carousel->title,
                                                        'source_type' => $carousel->source_type,
                                                        'author_id' => $carousel->author_id,
                                                        'book_limit' => $carousel->book_limit,
                                                        'sort_order' => $carousel->sort_order,
                                                        'is_active' => $carousel->is_active,
                                                        'is_system' => $carousel->is_system,
                                                        'show_unavailable' => $carousel->show_unavailable,
                                                        'category_ids' => $carousel->categories->pluck('id'),
                                                        'books' => $carousel->books->map(fn($b) => ['id' => $b->id, 'title' => $b->title, 'author' => optional($b->primaryAuthor)->name]),
                                                    ]) }})">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            @unless($carousel->is_system)
                                            <button class="btn-icon text-danger" title="حذف" onclick="confirmDelete({{ $carousel->id }}, '{{ addslashes($carousel->title) }}')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            @endunless
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="text-center text-muted py-4">لا توجد كاروسيلات بعد</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i>كاروسيل جديد</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="{{ route('admin.home-carousels.store') }}">
                @csrf
                <div class="modal-body">
                    @include('Dashbord_Admin.partials.home-carousel-form', ['ctx' => 'create'])
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
                <h5 class="modal-title"><i class="fas fa-edit me-2"></i>تعديل الكاروسيل</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editForm" action="">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    @include('Dashbord_Admin.partials.home-carousel-form', ['ctx' => 'edit'])
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
            <div class="modal-body" id="deleteModalBody">هل أنت متأكد من حذف هذا الكاروسيل؟</div>
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
const baseUrl = "{{ url('/admin/home-carousels') }}";
const searchBookUrl = "{{ route('admin.search.book') }}";

function showToast(message, type = 'success') {
    const bg = type === 'success' ? '#27ae60' : '#e74c3c';
    const el = document.createElement('div');
    el.style = `background:${bg};color:#fff;padding:.75rem 1.2rem;border-radius:8px;margin-top:.5rem;font-family:'Cairo',sans-serif;box-shadow:0 4px 12px rgba(0,0,0,.15);`;
    el.textContent = message;
    document.getElementById('toast-container').appendChild(el);
    setTimeout(() => el.remove(), 3500);
}

// Show only the input block matching the selected source type.
function onSourceChange(ctx) {
    const type = document.getElementById(`source_type_${ctx}`).value;
    document.querySelectorAll(`#block_categories_${ctx}, #block_author_${ctx}, #block_manual_${ctx}`).forEach(b => {
        b.style.display = (b.dataset.source === type) ? '' : 'none';
    });
}

// ============ Book picker (manual source) ============
function initBookPicker(ctx) {
    const input       = document.getElementById(`bookSearch_${ctx}`);
    const suggestions = document.getElementById(`bookSuggestions_${ctx}`);
    const chips       = document.getElementById(`selectedBooks_${ctx}`);
    if (!input || input.dataset.bound) return;
    input.dataset.bound = '1';

    let timer = null, abort = null;

    function selectedIds() {
        return Array.from(chips.querySelectorAll('input[name="book_ids[]"]')).map(i => i.value);
    }
    function addBook(id, title, author) {
        if (selectedIds().includes(String(id))) return;
        const chip = document.createElement('span');
        chip.className = 'book-chip';
        chip.innerHTML = `<input type="hidden" name="book_ids[]" value="${id}">
            <span>${title}${author ? ' — ' + author : ''}</span>
            <button type="button" title="إزالة">&times;</button>`;
        chip.querySelector('button').addEventListener('click', () => chip.remove());
        chips.appendChild(chip);
    }
    input._addBook = addBook;
    input._clear   = () => { chips.innerHTML = ''; };

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
        addBook(btn.dataset.id, btn.dataset.title, btn.dataset.author);
        input.value = '';
        hide();
    });
    input.addEventListener('blur', () => setTimeout(hide, 150));
}
initBookPicker('create');
initBookPicker('edit');

// ============ Category tree (categories source) ============
function initCategoryTree(ctx) {
    const tree = document.getElementById(`category_tree_${ctx}`);
    if (!tree || tree.dataset.bound) return;
    tree.dataset.bound = '1';

    // Expand / collapse a parent's children.
    tree.querySelectorAll('.cat-toggle').forEach(btn => {
        btn.addEventListener('click', () => {
            const children = btn.closest('.cat-parent').querySelector('.cat-children');
            if (!children) return;
            const show = children.hasAttribute('hidden');
            children.toggleAttribute('hidden', !show);
            btn.classList.toggle('open', show);
        });
    });

    // Checking a parent selects/deselects all of its children.
    tree.querySelectorAll('.cat-parent-check').forEach(parentCheck => {
        parentCheck.addEventListener('change', () => {
            parentCheck.closest('.cat-parent')
                .querySelectorAll('.cat-children .cat-check')
                .forEach(ch => { ch.checked = parentCheck.checked; });
        });
    });
}
initCategoryTree('create');
initCategoryTree('edit');

// Check the given category ids and expand parents that contain a selection.
function setTreeChecked(ctx, ids) {
    const tree = document.getElementById(`category_tree_${ctx}`);
    if (!tree) return;
    const want = (ids || []).map(String);
    tree.querySelectorAll('.cat-check').forEach(cb => { cb.checked = want.includes(cb.value); });
    tree.querySelectorAll('.cat-parent').forEach(parent => {
        const anyChecked = Array.from(parent.querySelectorAll('.cat-check')).some(cb => cb.checked);
        const children = parent.querySelector('.cat-children');
        const toggle   = parent.querySelector('.cat-toggle');
        if (children && toggle) {
            children.toggleAttribute('hidden', !anyChecked);
            toggle.classList.toggle('open', anyChecked);
        }
    });
}

function resetCreateForm() {
    document.querySelector('#createModal form').reset();
    document.getElementById('bookSearch_create')._clear();
    setTreeChecked('create', []);
    document.getElementById('sourceSection_create').style.display = '';
    onSourceChange('create');
}

// ============ Edit ============
function openEdit(c) {
    document.getElementById('editForm').action = `${baseUrl}/${c.id}`;
    document.getElementById('title_edit').value       = c.title || '';
    document.getElementById('source_type_edit').value = c.source_type || 'categories';
    document.getElementById('book_limit_edit').value  = c.book_limit || 12;
    document.getElementById('sort_order_edit').value  = c.sort_order || 0;
    document.getElementById('is_active_edit').checked  = !!c.is_active;
    document.getElementById('show_unavailable_edit').checked = !!c.show_unavailable;

    // Author
    document.getElementById('author_id_edit').value = c.author_id || '';

    // Categories tree
    setTreeChecked('edit', c.category_ids);

    // Manual books
    const picker = document.getElementById('bookSearch_edit');
    picker._clear();
    (c.books || []).forEach(b => picker._addBook(b.id, b.title, b.author));

    // Built-in carousels: content is code-driven, so hide the source picker.
    document.getElementById('sourceSection_edit').style.display = c.is_system ? 'none' : '';

    onSourceChange('edit');
    new bootstrap.Modal(document.getElementById('editModal')).show();
}

// ============ Toggle ============
function toggleCarousel(id) {
    fetch(`${baseUrl}/${id}/toggle`, { method: 'POST', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' } })
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
let deleteId = null;
function confirmDelete(id, title) {
    deleteId = id;
    document.getElementById('deleteModalBody').textContent = `هل أنت متأكد من حذف الكاروسيل "${title}"؟`;
    new bootstrap.Modal(document.getElementById('deleteModal')).show();
}
document.getElementById('confirmDeleteBtn').addEventListener('click', function () {
    if (!deleteId) return;
    fetch(`${baseUrl}/${deleteId}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('row-' + deleteId)?.remove();
                bootstrap.Modal.getInstance(document.getElementById('deleteModal'))?.hide();
                showToast(data.message);
            } else { showToast(data.message || 'حدث خطأ', 'error'); }
        })
        .catch(() => showToast('حدث خطأ في الاتصال', 'error'));
});

// Initial state
onSourceChange('create');
</script>
</body>
</html>
