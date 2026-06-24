{{-- Reusable offer form fields. $ctx = 'create' | 'edit' (used to namespace element ids). --}}
<input type="hidden" name="type" value="pick_n_for_price">

<div class="mb-3">
    <label class="form-label">عنوان العرض <span class="text-danger">*</span></label>
    <input type="text" name="title" id="title_{{ $ctx }}" class="form-control" required
           value="{{ $ctx === 'create' ? old('title') : '' }}" placeholder="مثال: 10 كتب بـ 350 درهم">
</div>

<div class="mb-3">
    <label class="form-label">الوصف</label>
    <textarea name="description" id="description_{{ $ctx }}" class="form-control" rows="2"
              placeholder="وصف مختصر يظهر للزبون">{{ $ctx === 'create' ? old('description') : '' }}</textarea>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">عدد الكتب (N) <span class="text-danger">*</span></label>
        <input type="number" name="quantity" id="quantity_{{ $ctx }}" class="form-control" min="1" required
               value="{{ $ctx === 'create' ? old('quantity', 10) : '' }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">السعر الثابت (د.م) <span class="text-danger">*</span></label>
        <input type="number" name="fixed_price" id="fixed_price_{{ $ctx }}" class="form-control" step="0.01" min="0" required
               value="{{ $ctx === 'create' ? old('fixed_price', 350) : '' }}">
    </div>
</div>

<div class="row g-3 mt-0">
    <div class="col-md-6">
        <label class="form-label">تاريخ البداية</label>
        <input type="datetime-local" name="starts_at" id="starts_at_{{ $ctx }}" class="form-control"
               value="{{ $ctx === 'create' ? old('starts_at') : '' }}">
        <small class="text-muted">اتركه فارغاً ليبدأ فوراً</small>
    </div>
    <div class="col-md-6">
        <label class="form-label">تاريخ الانتهاء</label>
        <input type="datetime-local" name="ends_at" id="ends_at_{{ $ctx }}" class="form-control"
               value="{{ $ctx === 'create' ? old('ends_at') : '' }}">
        <small class="text-muted">اتركه فارغاً لعرض بلا نهاية</small>
    </div>
</div>

<div class="mb-3 mt-3">
    <label class="form-label">صورة العرض (بانر)</label>
    <input type="file" name="banner_image" id="banner_image_{{ $ctx }}" class="form-control" accept="image/jpeg,image/png,image/webp">
    <div id="currentBanner_{{ $ctx }}" class="mt-2"></div>
    <small class="text-muted">JPG · PNG · WEBP — بحد أقصى 5 ميجابايت</small>
</div>

<div class="mb-3">
    <label class="form-label">الكتب المؤهلة للعرض</label>

    {{-- Live price-range rule: matching books are auto-included (and update over time) --}}
    <div class="price-rule-box" style="border:1px solid #cdddff;background:#f5f9ff;border-radius:10px;padding:.8rem;margin-bottom:.8rem;">
        <label class="form-label small mb-1"><i class="fas fa-coins me-1 text-primary"></i> إضافة تلقائية حسب نطاق السعر</label>
        <div class="row g-2">
            <div class="col-6">
                <input type="number" name="min_price" id="min_price_{{ $ctx }}" class="form-control form-control-sm"
                       step="0.01" min="0" placeholder="من (د.م)" value="{{ $ctx === 'create' ? old('min_price') : '' }}">
            </div>
            <div class="col-6">
                <input type="number" name="max_price" id="max_price_{{ $ctx }}" class="form-control form-control-sm"
                       step="0.01" min="0" placeholder="إلى (د.م)" value="{{ $ctx === 'create' ? old('max_price') : '' }}">
            </div>
        </div>
        <small class="text-muted d-block mt-1">كل الكتب ضمن هذا النطاق تُضاف للعرض تلقائياً وتتحدّث مع تغيّر الأسعار. اتركه فارغاً لتجاهله. (مثال: «إلى 45» = كل الكتب تحت 45 د.م)</small>
        <div id="priceRulePreview_{{ $ctx }}" class="small mt-1"></div>

        {{-- Exclude specific books from the price range --}}
        <div id="excludeSection_{{ $ctx }}" style="display:none;margin-top:.7rem;border-top:1px dashed #cdddff;padding-top:.6rem;">
            <label class="form-label small mb-1"><i class="fas fa-ban me-1 text-danger"></i> استثناء كتب من هذا النطاق</label>
            <div class="book-picker-wrap">
                <input type="text" id="excludeSearch_{{ $ctx }}" class="form-control form-control-sm" autocomplete="off"
                       placeholder="ابحث عن كتاب لاستثنائه من النطاق...">
                <div id="excludeSuggestions_{{ $ctx }}" class="list-group book-suggestions"></div>
            </div>

            {{-- Bulk-exclude an entire series --}}
            <div class="d-flex gap-2 mt-2">
                <select id="excludeSeries_{{ $ctx }}" class="form-select form-select-sm">
                    <option value="">— استثناء سلسلة كاملة —</option>
                    @foreach($series as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
                <button type="button" id="excludeSeriesBtn_{{ $ctx }}" class="btn btn-sm btn-outline-danger" style="white-space:nowrap;">
                    استثناء السلسلة
                </button>
            </div>

            <div id="excludedBooks_{{ $ctx }}" class="selected-books mt-2"></div>
            <small class="text-muted d-block mt-1">الكتب المستثناة لن تظهر في العرض رغم مطابقتها للنطاق.</small>
        </div>
    </div>

    <label class="form-label small text-muted mb-1">أو أضف كتباً محددة:</label>

    {{-- Browse to bulk-add books — by category, series, or bundle --}}
    <div class="cat-browser">
        <div class="browse-tabs" id="browseTabs_{{ $ctx }}">
            <button type="button" class="browse-tab active" data-source="category">التصنيفات</button>
            <button type="button" class="browse-tab" data-source="series">السلاسل</button>
            <button type="button" class="browse-tab" data-source="bundle">الباقات</button>
        </div>

        {{-- Categories panel (expandable tree) --}}
        <div class="browse-panel" data-source="category" id="panelCategory_{{ $ctx }}">
            <div class="cat-tree" id="catTree_{{ $ctx }}">
                @foreach($categories as $parent)
                    <div class="cat-node">
                        <div class="cat-node-row">
                            @if($parent->children->count())
                                <button type="button" class="cat-exp" aria-label="توسيع"><i class="fas fa-chevron-left"></i></button>
                            @else
                                <span class="cat-exp-spacer"></span>
                            @endif
                            <button type="button" class="cat-pick" data-source="category" data-id="{{ $parent->id }}">{{ $parent->name }}</button>
                        </div>
                        @if($parent->children->count())
                            <div class="cat-children" hidden>
                                @foreach($parent->children as $child)
                                    <button type="button" class="cat-pick cat-child" data-source="category" data-id="{{ $child->id }}">{{ $child->name }}</button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
            <div class="form-check form-switch mt-2">
                <input class="form-check-input" type="checkbox" id="includeChildren_{{ $ctx }}" checked>
                <label class="form-check-label small" for="includeChildren_{{ $ctx }}">يشمل التصنيفات الفرعية</label>
            </div>
        </div>

        {{-- Series panel — adds the whole series as a unit --}}
        <div class="browse-panel" data-source="series" id="panelSeries_{{ $ctx }}" style="display:none;">
            <small class="text-muted d-block mb-1">اختر سلسلة لإضافتها كوحدة (تُحتسب بعدد كتبها).</small>
            <div class="cat-tree">
                @forelse($series as $s)
                    <div class="cat-node"><div class="cat-node-row">
                        <span class="cat-exp-spacer"></span>
                        <button type="button" class="cat-pick" data-source="series" data-id="{{ $s->id }}" data-count="{{ $s->books_count }}">
                            {{ $s->name }} <span class="text-muted small">({{ $s->books_count }} كتب)</span>
                        </button>
                    </div></div>
                @empty
                    <div class="cat-empty">لا توجد سلاسل</div>
                @endforelse
            </div>
        </div>

        {{-- Bundles panel — adds the whole bundle as a unit --}}
        <div class="browse-panel" data-source="bundle" id="panelBundle_{{ $ctx }}" style="display:none;">
            <small class="text-muted d-block mb-1">اختر باقة لإضافتها كوحدة (تُحتسب بعدد كتبها).</small>
            <div class="cat-tree">
                @forelse($bundles as $bnd)
                    <div class="cat-node"><div class="cat-node-row">
                        <span class="cat-exp-spacer"></span>
                        <button type="button" class="cat-pick" data-source="bundle" data-id="{{ $bnd->id }}" data-count="{{ $bnd->items_count }}">
                            {{ $bnd->title }} <span class="text-muted small">({{ $bnd->items_count }} كتب)</span>
                        </button>
                    </div></div>
                @empty
                    <div class="cat-empty">لا توجد باقات</div>
                @endforelse
            </div>
        </div>

        <input type="text" id="catFilter_{{ $ctx }}" class="form-control form-control-sm mt-2"
               placeholder="تصفية القائمة بالعنوان..." autocomplete="off" style="display:none;">

        <div class="cat-list-toolbar" id="catToolbar_{{ $ctx }}" style="display:none;">
            <span class="text-muted small" id="catCount_{{ $ctx }}"></span>
            <span>
                <button type="button" class="btn btn-link btn-sm p-0 me-2" id="catSelectAll_{{ $ctx }}">تحديد الكل</button>
                <button type="button" class="btn btn-link btn-sm p-0 text-secondary" id="catDeselectAll_{{ $ctx }}">إلغاء التحديد</button>
            </span>
        </div>
        <div class="cat-book-list" id="catBookList_{{ $ctx }}" style="display:none;"></div>
    </div>

    {{-- Series/bundle units added to this offer --}}
    <div class="selected-units mt-2" id="selectedUnitsWrap_{{ $ctx }}" style="display:none;">
        <strong class="small d-block mb-1"><i class="fas fa-layer-group me-1 text-primary"></i> وحدات مضافة (سلاسل / باقات):</strong>
        <div id="selectedUnits_{{ $ctx }}" class="selected-books"></div>
    </div>

    {{-- Or search by title --}}
    <div class="book-picker-wrap mt-3">
        <label class="form-label small mb-1">أو أضف كتاباً بالبحث بالعنوان</label>
        <input type="text" id="bookSearch_{{ $ctx }}" class="form-control" autocomplete="off"
               placeholder="ابحث عن كتاب بالعنوان أو ISBN (3 أحرف على الأقل)...">
        <div id="bookSuggestions_{{ $ctx }}" class="list-group book-suggestions"></div>
    </div>

    {{-- Shared selection --}}
    <div class="selected-head mt-3">
        <strong class="small">الكتب المختارة (<span id="selectedCount_{{ $ctx }}">0</span>)</strong>
        <button type="button" class="btn btn-link btn-sm p-0 text-danger" id="clearAll_{{ $ctx }}" style="display:none;">مسح الكل</button>
    </div>
    <div id="selectedBooks_{{ $ctx }}" class="selected-books"></div>
    <small class="text-muted d-block mt-1">هذه الكتب التي يمكن للزبون الاختيار من بينها ضمن العرض.</small>
</div>

{{-- SEO --}}
<div class="accordion mt-3" id="offerSeoAccordion_{{ $ctx }}">
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#offerSeoCollapse_{{ $ctx }}">
                <i class="fas fa-search me-2"></i>إعدادات SEO <span class="text-muted ms-2 small">(اختياري)</span>
            </button>
        </h2>
        <div id="offerSeoCollapse_{{ $ctx }}" class="accordion-collapse collapse" data-bs-parent="#offerSeoAccordion_{{ $ctx }}">
            <div class="accordion-body">
                <div class="mb-3">
                    <label class="form-label">عنوان SEO <small class="text-muted">(≤ 70 حرف)</small></label>
                    <input type="text" name="meta_title" id="meta_title_{{ $ctx }}" class="form-control" maxlength="70"
                           value="{{ $ctx === 'create' ? old('meta_title') : '' }}">
                </div>
                <div class="mb-0">
                    <label class="form-label">وصف SEO <small class="text-muted">(≤ 160 حرف)</small></label>
                    <textarea name="meta_description" id="meta_description_{{ $ctx }}" class="form-control" rows="2" maxlength="160">{{ $ctx === 'create' ? old('meta_description') : '' }}</textarea>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="form-check form-switch mt-3">
    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active_{{ $ctx }}"
           {{ $ctx === 'create' ? 'checked' : '' }}>
    <label class="form-check-label" for="is_active_{{ $ctx }}">مفعّل</label>
</div>
