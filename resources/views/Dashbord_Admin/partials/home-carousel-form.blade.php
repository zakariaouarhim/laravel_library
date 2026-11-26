{{-- Reusable home-carousel form fields. $ctx = 'create' | 'edit'. Expects $categoryTree, $authors. --}}

<div class="mb-3">
    <label class="form-label">عنوان الكاروسيل <span class="text-danger">*</span></label>
    <input type="text" name="title" id="title_{{ $ctx }}" class="form-control" required
           value="{{ $ctx === 'create' ? old('title') : '' }}" placeholder="مثال: روايات مختارة">
</div>

<div id="sourceSection_{{ $ctx }}">
<div class="mb-3">
    <label class="form-label">مصدر الكتب <span class="text-danger">*</span></label>
    <select name="source_type" id="source_type_{{ $ctx }}" class="form-select" onchange="onSourceChange('{{ $ctx }}')">
        <option value="categories">من فئة أو عدة فئات</option>
        <option value="author">من مؤلف</option>
        <option value="manual">اختيار يدوي للكتب</option>
    </select>
</div>

{{-- Categories source --}}
<div class="mb-3 source-block" data-source="categories" id="block_categories_{{ $ctx }}">
    <label class="form-label">الفئات</label>
    <div class="category-tree" id="category_tree_{{ $ctx }}">
        @foreach($categoryTree as $parent)
            <div class="cat-parent">
                <div class="cat-parent-row">
                    @if($parent->children->count())
                        <button type="button" class="cat-toggle" aria-label="توسيع"><i class="fas fa-chevron-left"></i></button>
                    @else
                        <span class="cat-toggle-spacer"></span>
                    @endif
                    <label class="cat-label">
                        <input type="checkbox" class="cat-check cat-parent-check" name="category_ids[]" value="{{ $parent->id }}">
                        <strong>{{ $parent->name }}</strong>
                    </label>
                </div>
                @if($parent->children->count())
                    <div class="cat-children" hidden>
                        @foreach($parent->children as $child)
                            <label class="cat-label cat-child">
                                <input type="checkbox" class="cat-check" name="category_ids[]" value="{{ $child->id }}">
                                {{ $child->name }}
                            </label>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>
    <small class="text-muted d-block mt-1">اضغط على الفئة لعرض فئاتها الفرعية. اختيار فئة رئيسية يحدد فئاتها الفرعية تلقائياً.</small>
</div>

{{-- Author source --}}
<div class="mb-3 source-block" data-source="author" id="block_author_{{ $ctx }}" style="display:none;">
    <label class="form-label">المؤلف</label>
    <select name="author_id" id="author_id_{{ $ctx }}" class="form-select">
        <option value="">— اختر مؤلفاً —</option>
        @foreach($authors as $author)
            <option value="{{ $author->id }}">{{ $author->name }}</option>
        @endforeach
    </select>
</div>

{{-- Manual source --}}
<div class="mb-3 source-block" data-source="manual" id="block_manual_{{ $ctx }}" style="display:none;">
    <label class="form-label">الكتب المختارة</label>
    <div class="book-picker-wrap">
        <input type="text" id="bookSearch_{{ $ctx }}" class="form-control" autocomplete="off"
               placeholder="ابحث عن كتاب بالعنوان أو ISBN (3 أحرف على الأقل)...">
        <div id="bookSuggestions_{{ $ctx }}" class="list-group book-suggestions"></div>
    </div>
    <div id="selectedBooks_{{ $ctx }}" class="selected-books"></div>
</div>

{{-- Language filter — only for author / categories sources (a mix of languages is possible). --}}
<div class="mb-3" id="langFilter_{{ $ctx }}">
    <label class="form-label">لغة الكتب</label>
    <select name="language" id="language_{{ $ctx }}" class="form-select">
        <option value="">كل اللغات</option>
        <option value="arabic">العربية</option>
        <option value="english">الإنجليزية</option>
        <option value="french">الفرنسية</option>
    </select>
    <small class="text-muted d-block mt-1">اختر لغة محددة لعرض كتب تلك اللغة فقط من هذا المصدر.</small>
</div>
</div>{{-- /sourceSection --}}

{{-- Title link. Deliberately OUTSIDE sourceSection: that block is hidden for
     built-in carousels (their books are code-driven), but their title link is
     still the admin's to set — it is the only way to give وصل حديثا or
     الأكثر مبيعا a destination at all. --}}
<hr class="my-3">
<div class="form-check form-switch">
    <input class="form-check-input" type="checkbox" name="link_enabled" value="1" id="link_enabled_{{ $ctx }}"
           {{ $ctx === 'create' ? 'checked' : '' }} onchange="onLinkChange('{{ $ctx }}')">
    <label class="form-check-label fw-semibold" for="link_enabled_{{ $ctx }}">
        <i class="fas fa-link me-1 text-primary"></i>اجعل عنوان الكاروسيل رابطاً
    </label>
    <small class="text-muted d-block">عند التفعيل يصبح العنوان في الصفحة الرئيسية قابلاً للنقر ويوجّه الزائر إلى القائمة الكاملة.</small>
</div>

<div id="linkTarget_{{ $ctx }}" class="mt-3">
    <label class="form-label">يوجّه العنوان إلى</label>
    <select name="link_type" id="link_type_{{ $ctx }}" class="form-select" onchange="onLinkChange('{{ $ctx }}')">
        <option value="auto">تلقائي (حسب مصدر الكتب)</option>
        <option value="category">فئة محددة</option>
        <option value="path">مسار داخلي</option>
    </select>

    {{-- auto --}}
    <div class="link-block" data-link="auto" id="linkBlock_auto_{{ $ctx }}">
        <small class="text-muted d-block mt-1">
            يُحدَّد الرابط تلقائياً: كاروسيل الفئات يوجّه إلى صفحة الفئة الرئيسية، وكاروسيل المؤلف إلى صفحة المؤلف.
            إذا لم يكن هناك وجهة مناسبة يبقى العنوان نصاً عادياً.
        </small>
    </div>

    {{-- category --}}
    <div class="link-block mt-2" data-link="category" id="linkBlock_category_{{ $ctx }}" style="display:none;">
        <select name="link_category_id" id="link_category_id_{{ $ctx }}" class="form-select">
            <option value="">— اختر فئة —</option>
            @foreach($categoryTree as $parent)
                <option value="{{ $parent->id }}">{{ $parent->name }}</option>
                @foreach($parent->children as $child)
                    <option value="{{ $child->id }}">&nbsp;&nbsp;&nbsp;— {{ $child->name }}</option>
                @endforeach
            @endforeach
        </select>
        <small class="text-muted d-block mt-1">صفحة الفئة تعرض كتب الفئة وفئاتها الفرعية معاً.</small>
    </div>

    {{-- internal path --}}
    <div class="link-block mt-2" data-link="path" id="linkBlock_path_{{ $ctx }}" style="display:none;">
        <input type="text" name="link_path" id="link_path_{{ $ctx }}" class="form-control" dir="ltr"
               placeholder="/offers">
        <small class="text-muted d-block mt-1">مسار داخل الموقع يبدأ بشرطة مائلة واحدة، مثال: <code>/offers</code> أو <code>/series?language=arabic</code>. الروابط الخارجية غير مسموحة.</small>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">عدد الكتب المعروضة <span class="text-danger">*</span></label>
        <input type="number" name="book_limit" id="book_limit_{{ $ctx }}" class="form-control" min="1" max="50" required
               value="{{ $ctx === 'create' ? old('book_limit', 12) : '' }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">ترتيب الظهور</label>
        <input type="number" name="sort_order" id="sort_order_{{ $ctx }}" class="form-control"
               value="{{ $ctx === 'create' ? old('sort_order', 0) : '' }}">
        <small class="text-muted">الأصغر يظهر أولاً.</small>
    </div>
</div>

<div class="form-check form-switch mt-3">
    <input class="form-check-input" type="checkbox" name="show_unavailable" value="1" id="show_unavailable_{{ $ctx }}"
           {{ $ctx === 'create' ? 'checked' : '' }}>
    <label class="form-check-label" for="show_unavailable_{{ $ctx }}">عرض كل الكتب (بما فيها غير المتوفرة)</label>
    <small class="text-muted d-block">أوقفه لعرض الكتب المتوفرة في المخزون فقط. (لا يؤثر على السلاسل وشريط الفئات)</small>
</div>

<div class="form-check form-switch mt-3">
    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active_{{ $ctx }}"
           {{ $ctx === 'create' ? 'checked' : '' }}>
    <label class="form-check-label" for="is_active_{{ $ctx }}">مفعّل</label>
</div>
