<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>مراجعة كتاب: {{ $pendingBook->title }}</title>
    <link rel="stylesheet" href="{{ asset('css/variables.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/sidebardaschboard.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .source-label {
            font-weight: 600;
            font-size: .8rem;
            padding: .15rem .5rem;
            border-radius: .35rem;
            display: inline-block;
            margin-bottom: .25rem;
        }
        .source-bnf          { background:#e7f1ff; color:#084298; }
        .source-google_books { background:#fff3cd; color:#856404; }
        .source-open_library { background:#d1e7dd; color:#0f5132; }
        .source-wikipedia    { background:#e2d9f3; color:#3d2c63; }
        .source-custom       { background:#f8d7da; color:#842029; }
        .source-upload       { background:#cff4fc; color:#055160; }

        .compare-grid {
            display: grid;
            gap: .5rem;
            margin-bottom: .75rem;
        }
        .compare-cell {
            border: 1px solid #dee2e6;
            border-radius: .4rem;
            padding: .6rem;
            background: #fff;
            cursor: pointer;
            transition: border-color .12s, background .12s;
        }
        .compare-cell:hover     { border-color: #6c757d; }
        .compare-cell.selected  { border-color: #0d6efd; background: #f0f7ff; box-shadow: inset 0 0 0 1px #0d6efd; }
        .compare-cell.empty     { background: #f8f9fa; color: #adb5bd; cursor: not-allowed; }
        .compare-cell.agree      { border-inline-start: 4px solid #198754; }
        .compare-cell.unverified { border-inline-start: 4px solid #ffc107; }
        .compare-cell.conflict   { border-inline-start: 4px solid #dc3545; }
        .compare-cell input[type="radio"] { margin-inline-end: .35rem; }
        .compare-cell .value-text {
            display: block;
            margin-top: .35rem;
            font-size: .9rem;
            white-space: pre-wrap;
            word-break: break-word;
            max-height: 8rem;
            overflow-y: auto;
        }
        .compare-cell.custom textarea,
        .compare-cell.custom input[type="text"] {
            margin-top: .35rem;
            width: 100%;
            font-size: .9rem;
        }

        .cover-picker {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .cover-option {
            border: 2px solid transparent;
            border-radius: .5rem;
            padding: .5rem;
            text-align: center;
            cursor: pointer;
            transition: border-color .12s;
            background: #fff;
        }
        .cover-option:hover    { border-color: #6c757d; }
        .cover-option.selected { border-color: #0d6efd; box-shadow: 0 4px 12px rgba(13,110,253,.15); }
        .cover-option img {
            max-width: 140px;
            max-height: 200px;
            display: block;
            border-radius: .3rem;
            margin: 0 auto;
        }
        #upload-preview {
            width: 140px;
            height: 200px;
            object-fit: cover;
        }
        .cover-option .no-cover {
            width: 140px;
            height: 200px;
            display:flex;
            align-items: center;
            justify-content: center;
            color: #adb5bd;
            font-size: 2.5rem;
            background:#f8f9fa;
            border-radius: .3rem;
        }

        .field-row + .field-row { margin-top: 1.25rem; }
        .field-label { font-weight: 700; margin-bottom: .35rem; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        @include('Dashbord_Admin.Sidebar')

        <main class="col main-content">
            @if(session('error'))
                <div class="alert alert-danger mt-3">{{ session('error') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger mt-3">
                    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            <div class="d-flex justify-content-between align-items-center my-4">
                <div>
                    <a href="{{ route('admin.books.pending.index') }}" class="text-muted text-decoration-none">
                        <i class="fas fa-arrow-left me-1"></i> العودة للقائمة
                    </a>
                    <h1 class="fs-4 fw-bold mb-0 mt-2">مراجعة: {{ $pendingBook->title }}</h1>
                </div>
                <div>
                    @foreach($pendingBook->availableSources() as $src)
                        <span class="source-label source-{{ $src }}">{{ $src }}</span>
                    @endforeach
                </div>
            </div>

            @if($pendingBook->status === 'duplicate' && $pendingBook->existingBook)
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    هذا الكتاب موجود بالفعل في المتجر:
                    <strong>#{{ $pendingBook->existing_book_id }} — {{ $pendingBook->existingBook->title }}</strong>.
                </div>
            @endif

            @if($pendingBook->status === 'failed')
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-1"></i>
                    لم يتم العثور على بيانات في أي مصدر. املأ الحقول يدوياً أو تجاهل الإدخال.
                </div>
            @endif

            @if(in_array($pendingBook->status, ['approved', 'discarded']))
                <div class="alert alert-info">
                    تم {{ $pendingBook->status === 'approved' ? 'اعتماد' : 'تجاهل' }} هذا الإدخال.
                </div>
            @endif

            @if($pendingBook->isReviewable() && $pendingBook->status !== 'duplicate')
                @php
                    $sources    = $pendingBook->availableSources();
                    $apiResults = $pendingBook->api_results ?? [];
                    $stagingImg = $pendingBook->staging_images ?? [];

                    $fields = [
                        ['key' => 'title',          'label' => 'العنوان',     'type' => 'text',     'fetchKey' => 'title'],
                        ['key' => 'author_name',    'label' => 'المؤلف',      'type' => 'text',     'fetchKey' => 'author'],
                        ['key' => 'isbn',           'label' => 'ISBN',        'type' => 'text',     'fetchKey' => 'isbn'],
                        ['key' => 'page_num',       'label' => 'عدد الصفحات', 'type' => 'number',   'fetchKey' => 'page_num'],
                        ['key' => 'publisher_name', 'label' => 'دار النشر',   'type' => 'text',     'fetchKey' => 'publisher'],
                        ['key' => 'description',    'label' => 'الوصف',       'type' => 'textarea', 'fetchKey' => 'description'],
                    ];
                @endphp

                @php $boundAuthor = $pendingBook->author_id ? $pendingBook->author : null; @endphp
                @if($boundAuthor)
                    <div class="alert alert-info py-2 mb-3">
                        <i class="fas fa-link me-1"></i>
                        مرتبط بالمؤلف:
                        <strong>{{ $boundAuthor->name }}</strong>
                        <span class="text-muted">(#{{ $boundAuthor->id }})</span>
                        — سيتم استخدام هذا المؤلف عند الاعتماد ما لم يتم تعديل الاسم.
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.books.pending.approve', $pendingBook->id) }}" enctype="multipart/form-data">
                    @csrf

                    {{-- ========== COVER PICKER ========== --}}
                    <div class="card mb-4">
                        <div class="card-header bg-light"><strong>الغلاف</strong> — اختر مصدر الغلاف</div>
                        <div class="card-body">
                            <div class="cover-picker">
                                @php $defaultImageSource = $pendingBook->getDefaultImageSource(); @endphp

                                @foreach($sources as $src)
                                    @if($pendingBook->hasImage($src))
                                        <label class="cover-option {{ $src === $defaultImageSource ? 'selected' : '' }}" data-image-source>
                                            <input type="radio" name="image_source" value="{{ $src }}" {{ $src === $defaultImageSource ? 'checked' : '' }} hidden>
                                            <img src="{{ asset($stagingImg[$src]) }}" alt="cover from {{ $src }}">
                                            <div class="mt-1">
                                                <span class="source-label source-{{ $src }}">{{ $src }}</span>
                                            </div>
                                        </label>
                                    @endif
                                @endforeach

                                <label class="cover-option" data-image-source data-upload-tile>
                                    <input type="radio" name="image_source" value="custom" hidden>
                                    <div class="no-cover" id="upload-placeholder"><i class="fas fa-cloud-upload-alt"></i></div>
                                    <img id="upload-preview" alt="uploaded preview" style="display:none;">
                                    <div class="mt-1"><span class="source-label source-upload">رفع صورة</span></div>
                                </label>

                                <label class="cover-option {{ !$defaultImageSource ? 'selected' : '' }}" data-image-source>
                                    <input type="radio" name="image_source" value="" {{ !$defaultImageSource ? 'checked' : '' }} hidden>
                                    <div class="no-cover"><i class="fas fa-ban"></i></div>
                                    <div class="mt-1"><small>بدون غلاف</small></div>
                                </label>
                            </div>

                            {{-- Hidden file input revealed when "رفع صورة" tile is picked. --}}
                            <div id="upload-file-row" class="mt-3" style="display:none;">
                                <input type="file" name="uploaded_cover" id="uploaded_cover" accept="image/*" class="form-control">
                                <small class="text-muted">JPG, PNG, WebP. الحد الأقصى 5 ميغابايت.</small>
                            </div>
                        </div>
                    </div>

                    {{-- ========== FIELDS COMPARISON GRID ========== --}}
                    <div class="card mb-4">
                        <div class="card-header bg-light d-flex flex-wrap gap-3 align-items-center">
                            <span><strong>الحقول</strong> — اختر القيمة من أحد المصادر أو أدخل قيمة مخصصة</span>
                            <span class="text-muted small ms-auto">
                                <span class="d-inline-block" style="width:10px;height:10px;background:#198754;border-radius:2px;"></span> متطابق
                                <span class="d-inline-block ms-2" style="width:10px;height:10px;background:#ffc107;border-radius:2px;"></span> غير مؤكد
                                <span class="d-inline-block ms-2" style="width:10px;height:10px;background:#dc3545;border-radius:2px;"></span> متعارض
                            </span>
                        </div>
                        <div class="card-body">

                            @foreach($fields as $field)
                                @php
                                    $fieldKey   = $field['key'];
                                    $fetchKey   = $field['fetchKey'];
                                    $defaultSrc = $pendingBook->getDefaultSourceForField($fetchKey);

                                    // Fallback when no source returned this field (e.g. older PendingBooks
                                    // staged before parsers added 'author'). Lets the admin see what was
                                    // typed at stage time and skip having to retype it.
                                    $customFallback = '';
                                    if (!$defaultSrc) {
                                        if ($fieldKey === 'author_name') $customFallback = $pendingBook->author_name;
                                        elseif ($fieldKey === 'title')   $customFallback = $pendingBook->title;
                                    }

                                    // Cross-source agreement classification. Skipped for description
                                    // (long-form text won't ever match across sources).
                                    $skipAgreement = ($field['type'] === 'textarea');
                                    $normalized = [];
                                    if (!$skipAgreement) {
                                        foreach ($sources as $s) {
                                            $v = $apiResults[$s][$fetchKey] ?? null;
                                            if ($v === null || $v === '' || $v === 0 || $v === '0') continue;
                                            if ($fieldKey === 'isbn') {
                                                $n = preg_replace('/[^0-9Xx]/', '', (string) $v);
                                            } elseif ($fieldKey === 'page_num') {
                                                $n = (string) (int) $v;
                                            } else {
                                                $n = mb_strtolower(trim((string) $v));
                                            }
                                            if ($n !== '') $normalized[$s] = $n;
                                        }
                                    }
                                    $valueCounts = array_count_values($normalized);
                                    $agreementClass = function ($src) use ($normalized, $valueCounts, $skipAgreement) {
                                        if ($skipAgreement || !isset($normalized[$src])) return '';
                                        if (($valueCounts[$normalized[$src]] ?? 0) >= 2) return 'agree';
                                        if (count($valueCounts) === 1) return 'unverified';
                                        return 'conflict';
                                    };
                                @endphp

                                <div class="field-row" data-field="{{ $fieldKey }}">
                                    <div class="field-label">{{ $field['label'] }}</div>

                                    {{-- Hidden input that actually gets submitted. JS keeps it in sync. --}}
                                    <input type="hidden" name="{{ $fieldKey }}" id="hidden-{{ $fieldKey }}"
                                           value="{{ $defaultSrc ? ($apiResults[$defaultSrc][$fetchKey] ?? '') : $customFallback }}">

                                    <div class="compare-grid" style="grid-template-columns: repeat({{ count($sources) + 1 }}, minmax(0, 1fr));">

                                        @foreach($sources as $src)
                                            @php $value = $apiResults[$src][$fetchKey] ?? null; @endphp
                                            <div class="compare-cell {{ empty($value) && $value !== 0 ? 'empty' : '' }} {{ $src === $defaultSrc ? 'selected' : '' }} {{ $agreementClass($src) }}"
                                                 data-source="{{ $src }}"
                                                 data-value="{{ $value !== null ? (string) $value : '' }}">
                                                <label>
                                                    <input type="radio" name="src_{{ $fieldKey }}" value="{{ $src }}"
                                                           {{ $src === $defaultSrc ? 'checked' : '' }}
                                                           {{ empty($value) && $value !== 0 ? 'disabled' : '' }}>
                                                    <span class="source-label source-{{ $src }}">{{ $src }}</span>
                                                </label>
                                                <div class="value-text">{{ $value !== null && $value !== '' ? $value : '—' }}</div>
                                            </div>
                                        @endforeach

                                        {{-- Custom cell --}}
                                        <div class="compare-cell custom {{ !$defaultSrc ? 'selected' : '' }}" data-source="custom">
                                            <label>
                                                <input type="radio" name="src_{{ $fieldKey }}" value="custom" {{ !$defaultSrc ? 'checked' : '' }}>
                                                <span class="source-label source-custom">مخصص</span>
                                            </label>
                                            @if($field['type'] === 'textarea')
                                                <textarea class="form-control custom-input" rows="4" data-target="{{ $fieldKey }}">{{ !$defaultSrc ? old($fieldKey, $customFallback) : '' }}</textarea>
                                            @else
                                                <input type="{{ $field['type'] }}" class="form-control custom-input" data-target="{{ $fieldKey }}" value="{{ !$defaultSrc ? old($fieldKey, $customFallback) : '' }}">
                                            @endif
                                        </div>

                                    </div>
                                </div>
                            @endforeach

                        </div>
                    </div>

                    {{-- ========== ADMIN-ONLY FIELDS ========== --}}
                    <div class="card mb-4">
                        <div class="card-header bg-light"><strong>الإعدادات العامة</strong></div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">المؤلف</label>
                                    <input type="text" name="author_name" value="{{ old('author_name', $pendingBook->author_name) }}" class="form-control" required dir="auto">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">اللغة</label>
                                    <select name="language" class="form-select" required>
                                        @foreach(['french' => 'الفرنسية', 'english' => 'الإنجليزية', 'arabic' => 'العربية', 'spanish' => 'الإسبانية', 'german' => 'الألمانية'] as $val => $label)
                                            <option value="{{ $val }}" {{ old('language', $pendingBook->language) === $val ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">السعر (د.م)</label>
                                    <input type="number" step="0.01" name="price" value="{{ old('price', 0) }}" class="form-control" min="0" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">الكمية المتوفرة</label>
                                    <input type="number" name="quantity" value="{{ old('quantity', 0) }}" class="form-control" min="0" required>
                                    <small class="text-muted">الكتاب لن يكون قابلاً للشراء حتى تضع كمية > 0</small>
                                </div>

                                <div class="col-12">
                                    <label class="form-label">الفئات</label>
                                    @php
                                        $apiCategorySuggestions = [];
                                        foreach ($sources as $s) {
                                            foreach ((array) ($apiResults[$s]['categories'] ?? []) as $c) {
                                                $apiCategorySuggestions[] = $c;
                                            }
                                        }
                                        $apiCategorySuggestions = array_unique($apiCategorySuggestions);
                                    @endphp
                                    @if(!empty($apiCategorySuggestions))
                                        <p class="text-muted small mb-2">
                                            <i class="fas fa-info-circle me-1"></i>
                                            اقتراحات الـAPI: {{ implode(' • ', array_slice($apiCategorySuggestions, 0, 8)) }}
                                        </p>
                                    @endif
                                    @php $oldCats = (array) old('category_ids', []); @endphp
                                    <div class="accordion" id="categoryAccordion">
                                        @foreach($categories as $parent)
                                            @if($parent->children->isNotEmpty())
                                                @php
                                                    $childIds = $parent->children->pluck('id')->toArray();
                                                    $selectedInGroup = count(array_intersect(array_merge($childIds, [$parent->id]), $oldCats));
                                                @endphp
                                                <div class="accordion-item">
                                                    <h2 class="accordion-header" id="catHead-{{ $parent->id }}">
                                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#catBody-{{ $parent->id }}">
                                                            <span>{{ $parent->name }}</span>
                                                            <span class="badge bg-secondary ms-2 cat-count" data-parent="{{ $parent->id }}">{{ $selectedInGroup }}</span>
                                                        </button>
                                                    </h2>
                                                    <div id="catBody-{{ $parent->id }}" class="accordion-collapse collapse">
                                                        <div class="accordion-body">
                                                            <label class="form-check d-block mb-2 pb-2 border-bottom">
                                                                <input type="checkbox" name="category_ids[]" value="{{ $parent->id }}" class="form-check-input cat-cb" data-parent="{{ $parent->id }}"
                                                                    {{ in_array($parent->id, $oldCats) ? 'checked' : '' }}>
                                                                <span class="form-check-label fw-bold text-muted">{{ $parent->name }} (الفئة الأم نفسها)</span>
                                                            </label>
                                                            @php
                                                                // Children arrive pre-sorted by name from the controller, so
                                                                // grouping by first letter keeps the letter groups alphabetical.
                                                                $childrenByLetter = $parent->children->groupBy(
                                                                    fn($c) => mb_substr(trim($c->name), 0, 1, 'UTF-8')
                                                                );
                                                            @endphp
                                                            @foreach($childrenByLetter as $letter => $childGroup)
                                                                <div class="cat-letter-group mb-2">
                                                                    <div class="small fw-bold text-primary border-bottom pb-1 mb-2">{{ $letter }}</div>
                                                                    <div class="row g-2">
                                                                        @foreach($childGroup as $child)
                                                                            <div class="col-md-4 col-sm-6 col-12">
                                                                                <label class="form-check">
                                                                                    <input type="checkbox" name="category_ids[]" value="{{ $child->id }}" class="form-check-input cat-cb" data-parent="{{ $parent->id }}"
                                                                                        {{ in_array($child->id, $oldCats) ? 'checked' : '' }}>
                                                                                    <span class="form-check-label">{{ $child->name }}</span>
                                                                                </label>
                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="accordion-item">
                                                    <label class="form-check m-3">
                                                        <input type="checkbox" name="category_ids[]" value="{{ $parent->id }}" class="form-check-input"
                                                            {{ in_array($parent->id, $oldCats) ? 'checked' : '' }}>
                                                        <span class="form-check-label">{{ $parent->name }}</span>
                                                    </label>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 justify-content-end mb-4">
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fas fa-check me-1"></i> اعتماد وإنشاء الكتاب
                        </button>
                    </div>
                </form>
            @endif

            @if($pendingBook->isReviewable())
                <form method="POST" action="{{ route('admin.books.pending.discard', $pendingBook->id) }}" class="text-end mb-4" onsubmit="return confirm('تأكيد تجاهل هذا الإدخال؟ سيتم حذف كل الأغلفة المؤقتة.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger">
                        <i class="fas fa-trash me-1"></i> تجاهل الإدخال
                    </button>
                </form>
            @endif

            {{-- Raw API responses (debug) --}}
            @if($pendingBook->api_results)
                <details class="mb-5">
                    <summary class="text-muted small">عرض استجابات الـAPI الخام</summary>
                    <pre style="font-size:.75rem; background:#f8f9fa; padding:.8rem; border-radius:.4rem; max-height:400px; overflow:auto;">{{ json_encode($pendingBook->api_results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </details>
            @endif
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    // Cover picker — clicking a label updates the radio + visual state.
    var uploadRow     = document.getElementById('upload-file-row');
    var uploadFile    = document.getElementById('uploaded_cover');
    var uploadPreview = document.getElementById('upload-preview');
    var uploadPlace   = document.getElementById('upload-placeholder');

    function selectCoverTile(label) {
        document.querySelectorAll('[data-image-source]').forEach(function (l) { l.classList.remove('selected'); });
        label.classList.add('selected');
        var input = label.querySelector('input[type="radio"]');
        if (input) input.checked = true;

        // Show the file input only when the "Upload" tile is selected.
        if (uploadRow) {
            uploadRow.style.display = label.dataset.uploadTile !== undefined ? '' : 'none';
        }
    }

    document.querySelectorAll('[data-image-source]').forEach(function (label) {
        label.addEventListener('click', function (e) {
            // If the click is on the file input itself, don't toggle the selection.
            if (e.target === uploadFile) return;
            selectCoverTile(label);
        });
    });

    // When admin picks a file, render a preview AND select the Upload tile.
    if (uploadFile) {
        uploadFile.addEventListener('change', function () {
            var file = uploadFile.files && uploadFile.files[0];
            if (!file) {
                if (uploadPreview) { uploadPreview.style.display = 'none'; uploadPreview.src = ''; }
                if (uploadPlace)   { uploadPlace.style.display = ''; }
                return;
            }
            var url = URL.createObjectURL(file);
            if (uploadPreview) { uploadPreview.src = url; uploadPreview.style.display = ''; }
            if (uploadPlace)   { uploadPlace.style.display = 'none'; }

            var tile = document.querySelector('[data-upload-tile]');
            if (tile) selectCoverTile(tile);
        });
    }

    // Field comparison: clicking any cell selects its source. Custom cell shows
    // the text input and binds it to the hidden field. Source cells set the
    // hidden field to the cell's stored value.
    document.querySelectorAll('.field-row').forEach(function (row) {
        var fieldKey = row.dataset.field;
        var hidden   = document.getElementById('hidden-' + fieldKey);
        var customInput = row.querySelector('.custom-input');

        function selectCell(cell) {
            row.querySelectorAll('.compare-cell').forEach(function (c) { c.classList.remove('selected'); });
            cell.classList.add('selected');
            var radio = cell.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;

            if (cell.dataset.source === 'custom') {
                hidden.value = customInput.value;
            } else {
                hidden.value = cell.dataset.value || '';
            }
        }

        row.querySelectorAll('.compare-cell').forEach(function (cell) {
            if (cell.classList.contains('empty')) return;
            cell.addEventListener('click', function (e) {
                if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                    if (e.target.classList.contains('custom-input')) {
                        // Clicking inside the custom input selects the custom cell.
                        selectCell(cell);
                    }
                    return;
                }
                selectCell(cell);
            });
        });

        // When the admin types in the custom textbox, mirror the value into the hidden input
        // (and select the custom cell so the choice is unambiguous).
        if (customInput) {
            customInput.addEventListener('input', function () {
                var customCell = row.querySelector('.compare-cell.custom');
                selectCell(customCell);
            });
        }
    });

    // Live count of selected categories per parent group.
    function refreshCatCounts() {
        document.querySelectorAll('.cat-count').forEach(function (badge) {
            var pid = badge.dataset.parent;
            var n = document.querySelectorAll('.cat-cb[data-parent="' + pid + '"]:checked').length;
            badge.textContent = n;
            badge.classList.toggle('bg-success', n > 0);
            badge.classList.toggle('bg-secondary', n === 0);
        });
    }
    document.querySelectorAll('.cat-cb').forEach(function (cb) {
        cb.addEventListener('change', refreshCatCounts);
    });
    refreshCatCounts();
})();
</script>
</body>
</html>
