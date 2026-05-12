<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>إضافة كتاب من API</title>
    <link rel="stylesheet" href="{{ asset('css/variables.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/sidebardaschboard.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        #apiLoadingOverlay {
            position: fixed; inset: 0; background: rgba(0, 0, 0, 0.55);
            z-index: 9999; display: none;
            align-items: center; justify-content: center;
        }
        #apiLoadingOverlay.show { display: flex; }
        #apiLoadingOverlay .loader-card {
            background: #fff; border-radius: 12px; padding: 2rem 2.5rem;
            text-align: center; max-width: 420px; width: 90%;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
        #apiLoadingOverlay .loader-card .spinner-border {
            width: 3.5rem; height: 3.5rem; color: #0d6efd;
        }
        #apiLoadingOverlay .loader-card h5 { margin-top: 1rem; font-weight: 700; }
        #apiLoadingOverlay .loader-card p { color: #6c757d; margin: 0.5rem 0 0; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        @include('Dashbord_Admin.Sidebar')

        <main class="col main-content">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show mt-3">
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
                <h1 class="fs-4 fw-bold mb-0">
                    <i class="fas fa-cloud-download-alt me-2 text-primary"></i>إضافة كتاب من API
                </h1>
                <a href="{{ route('admin.books.pending.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-list me-1"></i> الكتب قيد المراجعة
                </a>
            </div>

            <div class="card">
                <div class="card-body">
                    <ul class="nav nav-tabs mb-3" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="tab-title-btn" data-bs-toggle="tab" data-bs-target="#tab-title" type="button" role="tab">
                                <i class="fas fa-search me-1"></i> بحث بالعنوان والمؤلف
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="tab-isbn-btn" data-bs-toggle="tab" data-bs-target="#tab-isbn" type="button" role="tab">
                                <i class="fas fa-barcode me-1"></i> بحث بـ ISBN
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="tab-title" role="tabpanel">
                            <p class="text-muted">
                                أدخل عنوان الكتاب واسم المؤلف. سيقوم النظام بالبحث في BNF و Google Books و Open Library و Wikipedia لجلب الوصف والغلاف وISBN.
                            </p>

                            <form method="POST" action="{{ route('admin.books.ingest.store') }}" id="ingestForm">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">العنوان</label>
                                        <input type="text" name="title" value="{{ old('title') }}" class="form-control" required dir="auto" placeholder="L'Étranger">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">المؤلف</label>
                                        <div class="position-relative">
                                            <input type="text" name="author" id="authorInput" value="{{ old('author') }}" class="form-control" required dir="auto" placeholder="Albert Camus" autocomplete="off">
                                            <ul id="authorSuggestions" class="list-group position-absolute w-100 shadow-sm" style="display:none; z-index:1050; top:100%; max-height:260px; overflow-y:auto;"></ul>
                                        </div>
                                        <small class="text-muted">ابدأ بالكتابة للبحث في المؤلفين الموجودين — يمنع إنشاء نسخ مكررة</small>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">اللغة</label>
                                        <select name="language" class="form-select" required>
                                            @foreach(['french' => 'الفرنسية', 'english' => 'الإنجليزية', 'arabic' => 'العربية', 'spanish' => 'الإسبانية', 'german' => 'الألمانية'] as $val => $label)
                                                <option value="{{ $val }}" {{ old('language', 'french') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="form-check mt-3">
                                    <input type="checkbox" name="force" value="1" id="forceInput" class="form-check-input" {{ old('force') ? 'checked' : '' }}>
                                    <label for="forceInput" class="form-check-label text-muted small">
                                        تجاهل الذاكرة المؤقتة وإعادة جلب البيانات من الـAPIs (أبطأ، استخدمه عند الشك في صحة النتائج)
                                    </label>
                                </div>

                                <button type="submit" class="btn btn-primary mt-4" id="ingestSubmitBtn">
                                    <i class="fas fa-search me-1"></i> بحث وإضافة للمراجعة
                                </button>
                            </form>
                        </div>

                        <div class="tab-pane fade" id="tab-isbn" role="tabpanel">
                            <p class="text-muted">
                                أدخل رقم ISBN فقط — سيقوم النظام بجلب العنوان والمؤلف والوصف والغلاف من المصادر تلقائياً. أسرع وأكثر دقة عند توفر الكتاب الفيزيائي.
                            </p>

                            <form method="POST" action="{{ route('admin.books.ingest.isbn') }}" id="ingestIsbnForm">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label class="form-label">ISBN</label>
                                        <input type="text" name="isbn" value="{{ old('isbn') }}" class="form-control" required dir="ltr" placeholder="9782070360024" pattern="[\d\-Xx ]+">
                                        <small class="text-muted">10 أو 13 رقم. يقبل الشرطات والمسافات.</small>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">اللغة</label>
                                        <select name="language" class="form-select" required>
                                            @foreach(['french' => 'الفرنسية', 'english' => 'الإنجليزية', 'arabic' => 'العربية', 'spanish' => 'الإسبانية', 'german' => 'الألمانية'] as $val => $label)
                                                <option value="{{ $val }}" {{ old('language', 'french') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <div class="form-check mt-3">
                                    <input type="checkbox" name="force" value="1" id="forceIsbnInput" class="form-check-input">
                                    <label for="forceIsbnInput" class="form-check-label text-muted small">
                                        تجاهل الذاكرة المؤقتة وإعادة جلب البيانات
                                    </label>
                                </div>

                                <button type="submit" class="btn btn-primary mt-4" id="ingestIsbnSubmitBtn">
                                    <i class="fas fa-barcode me-1"></i> جلب من ISBN
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="alert alert-info mt-4">
                <i class="fas fa-info-circle me-1"></i>
                للإضافة الجماعية من ملف CSV، استخدم: <code dir="ltr">php artisan books:ingest list.csv --language=french</code>
            </div>
        </main>
    </div>
</div>

<div id="apiLoadingOverlay">
    <div class="loader-card">
        <div class="spinner-border" role="status">
            <span class="visually-hidden">جاري التحميل...</span>
        </div>
        <h5>جاري البحث في قواعد البيانات</h5>
        <p>يتم استعلام BNF و Google Books و Open Library و Wikipedia. قد تستغرق العملية حتى دقيقة.</p>
        <p class="small text-warning mt-2"><i class="fas fa-exclamation-triangle me-1"></i>لا تغلق الصفحة ولا تضغط زر الإرجاع.</p>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<script>
    (function () {
        var form = document.getElementById('ingestForm');
        var btn  = document.getElementById('ingestSubmitBtn');
        var overlay = document.getElementById('apiLoadingOverlay');
        if (!form) return;

        form.addEventListener('submit', function () {
            overlay.classList.add('show');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> جاري البحث...';
            }
        });

        var isbnForm = document.getElementById('ingestIsbnForm');
        var isbnBtn  = document.getElementById('ingestIsbnSubmitBtn');
        if (isbnForm) {
            isbnForm.addEventListener('submit', function () {
                overlay.classList.add('show');
                if (isbnBtn) {
                    isbnBtn.disabled = true;
                    isbnBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> جاري الجلب...';
                }
            });
        }

        // Author autocomplete — debounced query against /search-authors.
        var authorInput = document.getElementById('authorInput');
        var suggestionsList = document.getElementById('authorSuggestions');
        if (!authorInput || !suggestionsList) return;

        var debounceTimer = null;
        var currentAbort = null;

        function hideSuggestions() {
            suggestionsList.style.display = 'none';
            suggestionsList.innerHTML = '';
        }

        function renderSuggestions(authors) {
            if (!authors.length) { hideSuggestions(); return; }
            suggestionsList.innerHTML = authors.map(function (a) {
                var sub = a.nationality ? ' <span class="text-muted small">(' + a.nationality + ')</span>' : '';
                return '<li class="list-group-item list-group-item-action" style="cursor:pointer;" data-name="' + a.name.replace(/"/g, '&quot;') + '">' + a.name + sub + '</li>';
            }).join('');
            suggestionsList.style.display = '';
        }

        authorInput.addEventListener('input', function () {
            var q = authorInput.value.trim();
            clearTimeout(debounceTimer);
            if (q.length < 2) { hideSuggestions(); return; }

            debounceTimer = setTimeout(function () {
                if (currentAbort) currentAbort.abort();
                currentAbort = new AbortController();
                fetch('{{ route('admin.search.authors') }}?q=' + encodeURIComponent(q), {
                    headers: { 'Accept': 'application/json' },
                    signal: currentAbort.signal
                })
                .then(function (r) { return r.ok ? r.json() : []; })
                .then(renderSuggestions)
                .catch(function () { /* aborted or network — silent */ });
            }, 200);
        });

        suggestionsList.addEventListener('mousedown', function (e) {
            // mousedown beats input's blur — fills cleanly without the list disappearing first.
            var li = e.target.closest('li[data-name]');
            if (!li) return;
            e.preventDefault();
            authorInput.value = li.dataset.name;
            hideSuggestions();
        });

        authorInput.addEventListener('blur', function () { setTimeout(hideSuggestions, 150); });
        authorInput.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') hideSuggestions();
        });
    })();
</script>
</body>
</html>
