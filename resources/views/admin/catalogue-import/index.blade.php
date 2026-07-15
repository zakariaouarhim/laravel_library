<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>استيراد من الكتالوج المرجعي</title>
    <link rel="stylesheet" href="{{ asset('css/sidebardaschboard.css') }}">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f0f2f5; color: #1a1a2e; padding: 20px; }
        a { color: inherit; }

        .header { text-align: center; margin-bottom: 24px; }
        .header h1 { font-size: 1.6rem; margin-bottom: 10px; }
        .progress-bar { width: 100%; max-width: 700px; height: 8px; background: #e5e7eb; border-radius: 4px; margin: 0 auto 14px; overflow: hidden; }
        .progress-fill { height: 100%; background: #16a34a; transition: width 0.3s; }
        .stats { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; margin-bottom: 16px; }
        .stat { background: #fff; padding: 8px 16px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,.1); font-size: .9rem; }
        .stat strong { color: #2563eb; }

        .toolbar { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; margin-bottom: 12px; align-items: center; }
        .toolbar input[type=search] { padding: 8px 14px; border: 1px solid #d1d5db; border-radius: 6px; min-width: 240px; }
        .toolbar select { padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px; background: #fff; }
        .toolbar .chk { display: flex; align-items: center; gap: 5px; font-size: .85rem; background: #fff; padding: 7px 12px; border: 1px solid #d1d5db; border-radius: 6px; cursor: pointer; }
        .filters button { padding: 8px 16px; border: 1px solid #d1d5db; border-radius: 6px; cursor: pointer; background: #fff; margin: 0 3px; }
        .filters button.active { background: #2563eb; color: #fff; border-color: #2563eb; }

        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap: 20px; max-width: 1500px; margin: 0 auto; }
        .card { background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.08); overflow: hidden; }
        .card.done { opacity: .6; }
        .card.busy { opacity: .5; pointer-events: none; }
        .card-top { display: flex; gap: 14px; padding: 14px; }
        .cover { width: 110px; height: 155px; object-fit: cover; border-radius: 6px; background: #e5e7eb; flex-shrink: 0; }
        .cover.missing { display: flex; align-items: center; justify-content: center; font-size: .7rem; color: #b91c1c; text-align: center; padding: 6px; }
        .info { flex: 1; min-width: 0; }

        .badges { margin-bottom: 6px; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: .7rem; font-weight: 600; margin: 0 0 4px 4px; }
        .badge-comp { background: #dbeafe; color: #1d4ed8; }
        .badge-store { background: #fef3c7; color: #92400e; }
        .badge-seo { background: #ecfccb; color: #3f6212; }

        label { font-size: .75rem; color: #6b7280; font-weight: 600; display: block; margin-top: 8px; }
        .field { width: 100%; padding: 6px 8px; border: 1px solid #e5e7eb; border-radius: 4px; font-size: .88rem; margin-top: 3px; font-family: inherit; }
        .field:focus { border-color: #2563eb; outline: none; box-shadow: 0 0 0 2px rgba(37,99,235,.2); }
        textarea.field { resize: vertical; min-height: 56px; }
        .row2 { display: flex; gap: 8px; }
        .row2 > div { flex: 1; }

        .cat-summary { margin-top: 10px; font-size: .82rem; }
        .cat-summary b { color: #1d4ed8; }
        .edit-details { margin-top: 8px; width: 100%; padding: 7px; border: 1px solid #c7d2fe; background: #eef2ff; color: #1d4ed8; border-radius: 6px; font-weight: 700; cursor: pointer; }
        .edit-details:hover { background: #e0e7ff; }

        .actions { display: flex; gap: 8px; padding: 12px 14px; border-top: 1px solid #f3f4f6; }
        .btn { flex: 1; padding: 9px; border: none; border-radius: 6px; font-size: .9rem; font-weight: 700; cursor: pointer; }
        .btn-approve { background: #16a34a; color: #fff; }
        .btn-skip { background: #f3f4f6; color: #6b7280; }
        .status-line { text-align: center; padding: 10px; font-weight: 700; font-size: .9rem; }
        .status-line.imported { color: #16a34a; background: #f0fdf4; }
        .status-line.skipped { color: #dc2626; background: #fef2f2; }
        .status-line a.relink { font-weight: 600; text-decoration: underline; margin-right: 6px; }

        .center { text-align: center; margin: 26px 0; }
        .load-more { padding: 10px 28px; border: none; border-radius: 8px; background: #2563eb; color: #fff; font-weight: 700; cursor: pointer; }
        .empty { text-align: center; padding: 60px 20px; color: #6b7280; }
        .toast { position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); background: #1f2937; color: #fff; padding: 10px 18px; border-radius: 8px; opacity: 0; transition: opacity .25s; z-index: 200; }
        .toast.show { opacity: 1; }

        /* Detail modal */
        .overlay { position: fixed; inset: 0; background: rgba(15,23,42,.55); display: none; align-items: flex-start; justify-content: center; padding: 24px; z-index: 100; overflow-y: auto; }
        .overlay.open { display: flex; }
        .modal { background: #fff; border-radius: 14px; width: 100%; max-width: 880px; box-shadow: 0 20px 60px rgba(0,0,0,.3); }
        .modal-head { display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-bottom: 1px solid #eef0f3; }
        .modal-head h3 { font-size: 1.15rem; }
        .modal-head button { border: none; background: none; font-size: 1.6rem; cursor: pointer; color: #6b7280; line-height: 1; }
        .modal-body { padding: 18px 20px; }
        .mgrid { display: flex; gap: 20px; }
        .mleft { width: 200px; flex-shrink: 0; }
        .mright { flex: 1; min-width: 0; }
        .mcover { width: 100%; height: 270px; object-fit: contain; background: #f3f4f6; border-radius: 8px; border: 1px solid #e5e7eb; }
        .mleft .field { margin-top: 10px; }
        .mbtn { display: inline-block; width: 100%; margin-top: 8px; padding: 8px; border: 1px solid #d1d5db; background: #f9fafb; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: .85rem; }
        .mbtn:hover { background: #f1f5f9; }
        .mbtn.ai { background: #faf5ff; border-color: #e9d5ff; color: #7e22ce; }
        .mbtn.api { background: #eff6ff; border-color: #bfdbfe; color: #1d4ed8; }
        .desc-tools { display: flex; gap: 8px; margin-top: 6px; }
        .desc-tools .mbtn { margin-top: 0; }

        .enrich-panel { margin-top: 10px; border: 1px solid #bfdbfe; background: #f8fbff; border-radius: 8px; padding: 10px; display: none; }
        .enrich-panel.open { display: block; }
        .enrich-field { padding: 7px 0; border-bottom: 1px dashed #e5e7eb; font-size: .82rem; }
        .enrich-field:last-of-type { border-bottom: none; }
        .enrich-field > .ekey { font-weight: 700; color: #2563eb; margin-bottom: 4px; }
        .esrc { display: flex; gap: 6px; align-items: flex-start; cursor: pointer; margin: 0 0 3px; padding: 2px 4px; border-radius: 5px; color: #1a1a2e; }
        .esrc:hover { background: #eef4ff; }
        .esrc input { margin-top: 3px; }
        .esrc-label { min-width: 84px; font-weight: 600; color: #16a34a; flex-shrink: 0; }
        .esrc-val { color: #374151; overflow: hidden; }
        .esrc img { width: 46px; height: 64px; object-fit: cover; border-radius: 4px; }
        .esrc-ignore .esrc-label { color: #9ca3af; }

        .cat-box { max-height: 260px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 8px; padding: 6px 8px; margin-top: 4px; }
        .cat-row { display: flex; align-items: center; gap: 6px; padding: 1px 0; font-size: .85rem; }
        .cat-row.child { padding-right: 22px; }
        .cat-group { border-bottom: 1px solid #f3f4f6; }
        .cat-group:last-child { border-bottom: none; }
        .cat-parent-row { display: flex; align-items: center; gap: 6px; padding: 6px 0; font-weight: 700; font-size: .85rem; }
        .cat-toggle { width: 14px; text-align: center; color: #6b7280; transition: transform .15s; user-select: none; }
        .cat-parent-row.open .cat-toggle { transform: rotate(90deg); }
        .cat-parent-row.has-kids .cat-toggle, .cat-parent-row.has-kids .cat-pname { cursor: pointer; }
        .cat-pname { flex: 1; }
        .cat-count { color: #16a34a; font-weight: 600; font-size: .76rem; }
        .cat-children { padding: 2px 0 6px; }
        .cat-star { cursor: pointer; color: #d1d5db; font-size: 1rem; width: 18px; text-align: center; }
        .cat-star.primary { color: #f59e0b; }
        .cat-hint { font-size: .72rem; color: #6b7280; margin-top: 4px; }

        .ac-wrap { position: relative; }
        .ac-list { position: absolute; top: 100%; left: 0; right: 0; z-index: 300; background: #fff;
                   border: 1px solid #d1d5db; border-top: none; border-radius: 0 0 6px 6px;
                   max-height: 180px; overflow-y: auto; box-shadow: 0 6px 16px rgba(0,0,0,.12); display: none; }
        .ac-list.open { display: block; }
        .ac-item { padding: 6px 10px; font-size: .85rem; cursor: pointer; }
        .ac-item:hover, .ac-item.active { background: #eef2ff; }
        .ac-item small { color: #6b7280; }

        .modal-foot { display: flex; gap: 10px; padding: 14px 20px; border-top: 1px solid #eef0f3; }
        .modal-foot button { flex: 1; padding: 10px; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; }
        .mf-save { background: #2563eb; color: #fff; }
        .mf-approve { background: #16a34a; color: #fff; }
        .mf-cancel { background: #f3f4f6; color: #6b7280; }

        .main-content {
            margin-right: calc(var(--sidebar-width-expanded) + 12px);
            width: auto;
            transition: margin-right 0.3s ease;
        }
        .main-content.sidebar-collapsed {
            margin-right: calc(var(--sidebar-width-collapsed) + 12px);
        }
        @media (max-width: 768px) {
            .main-content { margin-right: 0; }
        }
    </style>
</head>
<body>
    @include('Dashbord_Admin.Sidebar')
    <div class="main-content">
    <div class="header">
        <h1>استيراد من الكتالوج المرجعي</h1>
        <div class="progress-bar"><div class="progress-fill" id="progressFill" style="width:0%"></div></div>
        <div class="stats" id="stats"></div>
        <div class="toolbar">
            <input type="search" id="searchBox" placeholder="ابحث بالعنوان أو المؤلف أو ISBN…">
            <select id="fLang">
                <option value="">كل اللغات</option>
                <option value="arabic">العربية</option>
                <option value="french">الفرنسية</option>
                <option value="english">الإنجليزية</option>
                <option value="spanish">الإسبانية</option>
                <option value="german">الألمانية</option>
            </select>
            <select id="fComp">
                <option value="8">الجودة = 8</option>
                <option value="7" selected>الجودة ≥ 7</option>
                <option value="5">الجودة ≥ 5</option>
                <option value="0">كل الجودات</option>
            </select>
            <select id="fSource">
                <option value="">كل المصادر</option>
                <option value="almouggar">Almouggar</option>
                <option value="bod">BooksOnDemand</option>
            </select>
            <label class="chk"><input type="checkbox" id="fDesc"> بوصف فقط</label>
            <label class="chk"><input type="checkbox" id="fStore" checked> إخفاء المتوفر بالمتجر</label>
        </div>
        <div class="toolbar">
            <div class="filters">
                <button class="active" data-status="pending">قيد المراجعة</button>
                <button data-status="imported">مستوردة</button>
                <button data-status="skipped">متخطّاة</button>
                <button data-status="all">الكل</button>
            </div>
        </div>
    </div>

    <div class="grid" id="grid"></div>
    <div class="empty" id="empty" style="display:none;">لا توجد عناصر لعرضها.</div>
    <div class="center" id="moreWrap" style="display:none;">
        <button class="load-more" id="loadMore">تحميل المزيد</button>
    </div>
    </div><!-- /.main-content -->
    <div class="toast" id="toast"></div>

    <!-- Detail modal -->
    <div class="overlay" id="overlay">
      <div class="modal">
        <div class="modal-head">
            <h3>تحرير التفاصيل</h3>
            <button type="button" id="mClose">&times;</button>
        </div>
        <div class="modal-body">
          <div class="mgrid">
            <div class="mleft">
                <img class="mcover" id="mCover" alt="">
                <label>تغيير الصورة</label>
                <input type="file" class="field" id="mFile" accept="image/*">
            </div>
            <div class="mright">
                <label>العنوان</label>
                <input class="field" id="mName">
                <div class="row2">
                    <div><label>المؤلف</label>
                        <div class="ac-wrap"><input class="field" id="mAuthor" placeholder="غير معروف" autocomplete="off"><div class="ac-list" id="mAuthorAC"></div></div>
                    </div>
                    <div><label>ISBN</label><input class="field" id="mIsbn" placeholder="—"></div>
                </div>
                <div class="row2">
                    <div><label>اللغة</label><select class="field" id="mLang"></select></div>
                    <div><label>السعر</label><input class="field" id="mPrice" type="number" step="0.01"></div>
                    <div><label>الكمية</label><input class="field" id="mQty" type="number" min="0" step="1"></div>
                </div>
                <div class="row2">
                    <div><label>دار النشر</label>
                        <div class="ac-wrap"><input class="field" id="mPublisher" placeholder="—" autocomplete="off"><div class="ac-list" id="mPublisherAC"></div></div>
                    </div>
                    <div><label>عدد الصفحات</label><input class="field" id="mPages" type="number" min="0"></div>
                </div>

                <label>الوصف</label>
                <textarea class="field" id="mDesc" rows="5"></textarea>
                <div class="desc-tools">
                    <button type="button" class="mbtn api" id="mEnrich">إثراء من API (معاينة)</button>
                    <button type="button" class="mbtn ai" id="mRewrite">إعادة صياغة (SEO)</button>
                </div>
                <div class="enrich-panel" id="mEnrichPanel"></div>

                <label>التصنيفات <small style="font-weight:400">(اختر واحدة أو أكثر؛ النجمة = الفئة الرئيسية)</small></label>
                <div class="cat-box" id="mCats"></div>
                <div class="cat-hint">اضغط اسم الفئة الأمّ لعرض الفئات الفرعية. ⭐ أول فئة تحددها تصبح الرئيسية؛ اضغط النجمة لتغييرها. (اقتراح تلقائي حسب العنوان واللغة)</div>
            </div>
          </div>
        </div>
        <div class="modal-foot">
            <button type="button" class="mf-save" id="mSave">حفظ</button>
            <button type="button" class="mf-approve" id="mApprove">اعتماد وإنشاء</button>
            <button type="button" class="mf-cancel" id="mCancel">إلغاء</button>
        </div>
      </div>
    </div>

    <script>
        const CATEGORIES = @json($categories);
        const CAT_BY_ID = {};
        CATEGORIES.forEach(c => CAT_BY_ID[c.id] = c);
        const CSRF = document.querySelector('meta[name="csrf-token"]').content;
        const BASE = '{{ url('admin/catalogue-import') }}';
        const LIST_URL = '{{ route('admin.catalogue-import.list') }}';
        const SEARCH_AUTHORS = '{{ route('admin.search.authors') }}';
        const SEARCH_PUBLISHERS = '{{ route('admin.search.publishers') }}';
        const LANGS = { arabic: 'العربية', english: 'الإنجليزية', french: 'الفرنسية' };

        let status = 'pending';
        let page = 1;
        let q = '';
        let filters = { language: '', min_completeness: '7', has_description: false, hide_in_store: true, source: '' };
        let loading = false;
        const STATE = {};            // id -> item object (mutable working copy)

        const grid = document.getElementById('grid');
        const empty = document.getElementById('empty');
        const moreWrap = document.getElementById('moreWrap');

        function esc(s) {
            return (s ?? '').toString().replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
        }
        function langOptions(selected) {
            return Object.entries(LANGS).map(([v, l]) =>
                `<option value="${v}" ${v === selected ? 'selected' : ''}>${l}</option>`
            ).join('');
        }
        // Cover: an admin-replaced webp lives under public/; otherwise the remote catalogue cover.
        function imgSrc(b) {
            if (b._customImage) return `/${b._customImage}${b._imgv ? '?v=' + b._imgv : ''}`;
            return b.cover_url || '';
        }
        function catSummary(b) {
            const primary = CAT_BY_ID[b.primary_category_id];
            const n = (b.category_ids || []).length;
            const extra = n > 1 ? ` <span style="color:#6b7280">(+${n - 1})</span>` : '';
            return primary
                ? `الفئة الرئيسية: <b>${esc(primary.name)}</b>${extra}`
                : '<span style="color:#b91c1c">— غير مصنّف —</span>';
        }

        function cardHtml(b) {
            const src = imgSrc(b);
            const cover = src
                ? `<img class="cover" src="${esc(src)}" loading="lazy" alt="" onerror="this.classList.add('missing');this.replaceWith(Object.assign(document.createElement('div'),{className:'cover missing',textContent:'لا توجد صورة'}))">`
                : `<div class="cover missing">لا توجد صورة</div>`;

            let footer;
            if (b.status === 'imported') {
                footer = `<div class="status-line imported">✔ تم الاستيراد</div>`;
            } else if (b.status === 'skipped') {
                footer = `<div class="status-line skipped">✕ تم التخطّي <a href="#" class="relink" data-act="unskip">إرجاع</a></div>`;
            } else {
                footer = `<div class="actions">
                    <button class="btn btn-approve" data-act="approve">اعتماد وإنشاء</button>
                    <button class="btn btn-skip" data-act="skip">تخطّي</button>
                </div>`;
            }

            return `<div class="card ${b.status !== 'pending' ? 'done' : ''}" data-id="${b.id}">
                <div class="card-top">
                    ${cover}
                    <div class="info">
                        <div class="badges">
                            <span class="badge badge-comp">الجودة: ${b.completeness}</span>
                            ${b.in_store ? '<span class="badge badge-store">متوفر بالمتجر</span>' : ''}
                            ${b.description_rewritten ? '<span class="badge badge-seo">SEO</span>' : ''}
                        </div>
                        <label>العنوان</label>
                        <input class="field" data-f="name" value="${esc(b.name)}">
                        <label>المؤلف</label>
                        <input class="field" data-f="author" value="${esc(b.author)}" placeholder="غير معروف">
                        <div class="row2">
                            <div>
                                <label>اللغة</label>
                                <select class="field" data-f="language">${langOptions(b.language)}</select>
                            </div>
                            <div>
                                <label>السعر</label>
                                <input class="field" data-f="price" type="number" step="0.01" value="${b.price}">
                            </div>
                            <div>
                                <label>الكمية</label>
                                <input class="field" data-f="quantity" type="number" min="0" step="1" value="${b.quantity}">
                            </div>
                        </div>
                        <div class="cat-summary" data-role="cat-summary">${catSummary(b)}</div>
                        <button type="button" class="edit-details" data-act="details">✎ تحرير التفاصيل</button>
                    </div>
                </div>
                ${footer}
            </div>`;
        }

        function renderCard(id) {
            const card = grid.querySelector(`.card[data-id="${id}"]`);
            if (card) card.outerHTML = cardHtml(STATE[id]);
        }

        function listUrl(pageNum) {
            const p = new URLSearchParams({
                status, page: pageNum, q,
                language: filters.language,
                source: filters.source,
                min_completeness: filters.min_completeness,
                has_description: filters.has_description ? 1 : 0,
                hide_in_store: filters.hide_in_store ? 1 : 0,
            });
            return `${LIST_URL}?${p.toString()}`;
        }

        async function fetchPage(replace) {
            if (loading) return;
            loading = true;
            try {
                const resp = await fetch(listUrl(page), { headers: { 'Accept': 'application/json' } });
                const data = await resp.json();

                if (replace) grid.innerHTML = '';
                data.books.forEach(b => {
                    b.quantity = b.quantity ?? 1;
                    b.category_ids = b.category_ids || [];
                    b._customImage = null;
                    STATE[b.id] = b;
                    grid.insertAdjacentHTML('beforeend', cardHtml(b));
                });

                empty.style.display = (replace && data.books.length === 0) ? 'block' : 'none';
                moreWrap.style.display = data.has_more ? 'block' : 'none';
                page = data.next_page;
                renderStats(data.counts);
            } catch (e) {
                toast('تعذّر تحميل البيانات');
            }
            loading = false;
        }

        function renderStats(c) {
            document.getElementById('stats').innerHTML = `
                <div class="stat">الإجمالي: <strong>${c.total}</strong></div>
                <div class="stat">قيد المراجعة: <strong>${c.pending}</strong></div>
                <div class="stat">مستوردة: <strong style="color:#16a34a">${c.imported}</strong></div>
                <div class="stat">متخطّاة: <strong style="color:#dc2626">${c.skipped}</strong></div>`;
            const done = c.imported + c.skipped;
            document.getElementById('progressFill').style.width = c.total ? `${(done / c.total * 100).toFixed(1)}%` : '0%';
        }

        function reload() { page = 1; fetchPage(true); }

        let toastTimer;
        function toast(msg) {
            const t = document.getElementById('toast');
            t.textContent = msg; t.classList.add('show');
            clearTimeout(toastTimer);
            toastTimer = setTimeout(() => t.classList.remove('show'), 2500);
        }

        async function post(url, body) {
            const resp = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify(body || {}),
            });
            return { ok: resp.ok, status: resp.status, data: await resp.json().catch(() => ({})) };
        }
        async function postForm(url, formData) {
            const resp = await fetch(url, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: formData,
            });
            return { ok: resp.ok, status: resp.status, data: await resp.json().catch(() => ({})) };
        }

        function approvePayload(b) {
            return {
                name: b.name, author: b.author, isbn: b.isbn, page_num: b.page_num,
                publisher: b.publisher, language: b.language, price: b.price,
                quantity: b.quantity, description: b.description,
                category_ids: b.category_ids, primary_category_id: b.primary_category_id,
                custom_image: b._customImage || null,
                rewritten: !!b.description_rewritten,
                original_description: b._originalDescription || null,
            };
        }

        async function approveBook(id, card) {
            const b = STATE[id];
            if (!String(b.name || '').trim()) return toast('العنوان مطلوب');
            if (!b.category_ids || !b.category_ids.length) return toast('اختر تصنيفًا واحدًا على الأقل');
            if (!b.primary_category_id) return toast('حدد الفئة الرئيسية');
            if (b.quantity === '' || isNaN(b.quantity) || Number(b.quantity) < 0) return toast('الكمية غير صحيحة');

            card.classList.add('busy');
            let res = await post(`${BASE}/${id}/approve`, approvePayload(b));
            if (res.status === 409 && res.data.duplicate) {
                card.classList.remove('busy');
                if (!confirm('يوجد كتاب بنفس العنوان أو ISBN. هل تريد الاستيراد رغم ذلك؟')) return;
                card.classList.add('busy');
                res = await post(`${BASE}/${id}/approve`, { ...approvePayload(b), force: true });
            }
            card.classList.remove('busy');
            if (res.data.success) { toast('تم إنشاء الكتاب'); b.status = 'imported'; refreshAfterAction(card); }
            else {
                const errs = res.data.errors ? Object.values(res.data.errors).flat().join(' • ') : '';
                toast(errs || res.data.message || 'فشل الاستيراد');
            }
        }

        // ---- card-level interactions ----
        grid.addEventListener('input', (e) => {
            const el = e.target.closest('[data-f]');
            if (!el) return;
            const card = el.closest('.card');
            STATE[card.dataset.id][el.dataset.f] = el.value;
        });

        grid.addEventListener('click', async (e) => {
            const btn = e.target.closest('[data-act]');
            if (!btn) return;
            e.preventDefault();
            const card = btn.closest('.card');
            const id = card.dataset.id;
            const act = btn.dataset.act;

            if (act === 'details') return openModal(id);
            if (act === 'approve') return approveBook(id, card);

            if (act === 'skip') {
                card.classList.add('busy');
                const res = await post(`${BASE}/${id}/skip`);
                card.classList.remove('busy');
                if (res.data.success) { toast('تم التخطّي'); STATE[id].status = 'skipped'; refreshAfterAction(card); }
            }
            if (act === 'unskip') {
                const res = await post(`${BASE}/${id}/unskip`);
                if (res.data.success) { toast('تمت الإعادة'); STATE[id].status = 'pending'; refreshAfterAction(card); }
            }
        });

        function refreshAfterAction(card) {
            if (status === 'pending') {
                card.remove();
                if (!grid.children.length) reload();
            } else {
                reload();
            }
            fetchCountsOnly();
        }

        async function fetchCountsOnly() {
            try {
                const resp = await fetch(listUrl(1), { headers: { 'Accept': 'application/json' } });
                const data = await resp.json();
                renderStats(data.counts);
            } catch (e) {}
        }

        // ========================= Detail modal =========================
        const overlay = document.getElementById('overlay');
        let modalId = null;
        let modalCats = { ids: new Set(), primary: null };

        const $ = id => document.getElementById(id);

        // CATEGORIES is a flat, ordered list (each parent followed by its children).
        // Group it into parent → children for the collapsible accordion.
        function groupCategories() {
            const groups = [];
            let current = null;
            CATEGORIES.forEach(c => {
                if (c.parent) { current = { parent: c, children: [] }; groups.push(current); }
                else { if (!current) { current = { parent: null, children: [] }; groups.push(current); } current.children.push(c); }
            });
            return groups;
        }
        function catRow(c, cls) {
            const primary = modalCats.primary === c.id;
            return `<div class="cat-row ${cls}">
                <input type="checkbox" class="cat-cb" value="${c.id}" ${modalCats.ids.has(c.id) ? 'checked' : ''}>
                <span class="cat-star ${primary ? 'primary' : ''}" data-id="${c.id}">${primary ? '★' : '☆'}</span>
                <span>${esc(c.name)}</span>
            </div>`;
        }
        function buildCatBox() {
            $('mCats').innerHTML = groupCategories().map(g => {
                const p = g.parent;
                const selCount = (p && modalCats.ids.has(p.id) ? 1 : 0) + g.children.filter(c => modalCats.ids.has(c.id)).length;
                const open = selCount > 0; // reveal groups that already have a selection
                const primary = p && modalCats.primary === p.id;
                const hasKids = g.children.length > 0;
                const parentRow = p ? `<div class="cat-parent-row ${open ? 'open' : ''} ${hasKids ? 'has-kids' : ''}">
                    <span class="cat-toggle">${hasKids ? '▸' : ''}</span>
                    <input type="checkbox" class="cat-cb" value="${p.id}" ${modalCats.ids.has(p.id) ? 'checked' : ''}>
                    <span class="cat-star ${primary ? 'primary' : ''}" data-id="${p.id}">${primary ? '★' : '☆'}</span>
                    <span class="cat-pname">${esc(p.name)}</span>
                    <span class="cat-count">${selCount ? '(' + selCount + ')' : ''}</span>
                </div>` : '';
                const children = g.children.map(c => catRow(c, 'child')).join('');
                return `<div class="cat-group">${parentRow}<div class="cat-children" ${open ? '' : 'style="display:none"'}>${children}</div></div>`;
            }).join('');
        }
        function updateGroupCount(el) {
            const group = el.closest('.cat-group');
            if (!group) return;
            const n = group.querySelectorAll('.cat-cb:checked').length;
            const badge = group.querySelector('.cat-count');
            if (badge) badge.textContent = n ? '(' + n + ')' : '';
        }
        function refreshStars() {
            $('mCats').querySelectorAll('.cat-star').forEach(s => {
                const isP = Number(s.dataset.id) === modalCats.primary;
                s.classList.toggle('primary', isP);
                s.textContent = isP ? '★' : '☆';
            });
        }

        function openModal(id) {
            modalId = id;
            const b = STATE[id];
            $('mCover').src = imgSrc(b);
            $('mName').value = b.name || '';
            $('mAuthor').value = b.author || '';
            $('mIsbn').value = b.isbn || '';
            $('mLang').innerHTML = langOptions(b.language);
            $('mPrice').value = b.price;
            $('mQty').value = b.quantity;
            $('mPublisher').value = b.publisher || '';
            $('mPages').value = b.page_num || '';
            $('mDesc').value = b.description || '';
            $('mEnrichPanel').classList.remove('open');
            $('mEnrichPanel').innerHTML = '';
            $('mFile').value = '';

            modalCats.ids = new Set((b.category_ids || []).map(Number));
            modalCats.primary = b.primary_category_id || null;
            buildCatBox();

            overlay.classList.add('open');
        }
        function closeModal() { overlay.classList.remove('open'); modalId = null; }

        function harvestModalIntoState() {
            const b = STATE[modalId];
            b.name = $('mName').value;
            b.author = $('mAuthor').value;
            b.isbn = $('mIsbn').value;
            b.language = $('mLang').value;
            b.price = $('mPrice').value;
            b.quantity = $('mQty').value;
            b.publisher = $('mPublisher').value;
            b.page_num = $('mPages').value;
            b.description = $('mDesc').value;
            b.category_ids = Array.from(modalCats.ids);
            b.primary_category_id = modalCats.primary;
        }

        $('mCats').addEventListener('change', (e) => {
            const cb = e.target.closest('.cat-cb');
            if (!cb) return;
            const id = Number(cb.value);
            if (cb.checked) {
                modalCats.ids.add(id);
                if (!modalCats.primary) modalCats.primary = id;
            } else {
                modalCats.ids.delete(id);
                if (modalCats.primary === id) {
                    modalCats.primary = modalCats.ids.size ? modalCats.ids.values().next().value : null;
                }
            }
            refreshStars();
            updateGroupCount(cb);
        });
        $('mCats').addEventListener('click', (e) => {
            // Expand / collapse a parent's children.
            const toggle = e.target.closest('.cat-toggle, .cat-pname');
            if (toggle) {
                const group = toggle.closest('.cat-group');
                const kids = group.querySelector('.cat-children');
                if (!kids || !kids.children.length) return; // childless parent
                const row = group.querySelector('.cat-parent-row');
                const open = kids.style.display === 'none';
                kids.style.display = open ? 'block' : 'none';
                row.classList.toggle('open', open);
                return;
            }
            const star = e.target.closest('.cat-star');
            if (!star) return;
            const id = Number(star.dataset.id);
            if (!modalCats.ids.has(id)) { toast('فعّل الفئة أولاً'); return; }
            modalCats.primary = id;
            refreshStars();
        });

        // image upload -> returns a public webp path (not persisted; sent on approve)
        $('mFile').addEventListener('change', async (e) => {
            const file = e.target.files[0];
            if (!file) return;
            const fd = new FormData();
            fd.append('image', file);
            const res = await postForm(`${BASE}/${modalId}/image`, fd);
            if (res.data.success) {
                const b = STATE[modalId];
                b._customImage = res.data.path; b._imgv = res.data.image_version;
                $('mCover').src = imgSrc(b);
                toast('تم تحديث الصورة');
            } else toast(res.data.message || 'فشل رفع الصورة');
        });

        // SEO rewrite
        $('mRewrite').addEventListener('click', async () => {
            const btn = $('mRewrite');
            btn.disabled = true; btn.textContent = '... جارٍ';
            const res = await post(`${BASE}/${modalId}/rewrite`, {
                name: $('mName').value, author: $('mAuthor').value,
                description: $('mDesc').value, language: $('mLang').value,
            });
            btn.disabled = false; btn.textContent = 'إعادة صياغة (SEO)';
            if (res.data.success) {
                const b = STATE[modalId];
                if (!b._originalDescription) b._originalDescription = $('mDesc').value; // keep pre-rewrite text
                $('mDesc').value = res.data.description;
                b.description = res.data.description;
                b.description_rewritten = true;
                toast('تمت إعادة الصياغة');
            } else toast(res.data.message || 'فشلت إعادة الصياغة');
        });

        // API enrich preview (all sources per field)
        $('mEnrich').addEventListener('click', async () => {
            const btn = $('mEnrich');
            btn.disabled = true; btn.textContent = '... جارٍ البحث';
            const res = await post(`${BASE}/${modalId}/enrich-preview`, {
                name: $('mName').value, author: $('mAuthor').value, isbn: $('mIsbn').value, language: $('mLang').value,
            });
            btn.disabled = false; btn.textContent = 'إثراء من API (معاينة)';
            const panel = $('mEnrichPanel');

            if (!res.data.success) { toast(res.data.message || 'فشل الإثراء'); return; }
            if (!res.data.found) { toast('لم يتم العثور على بيانات'); return; }

            const f = res.data.fields;
            const labels = { description: 'الوصف', image: 'الصورة', page_num: 'الصفحات', publisher: 'الناشر', language: 'اللغة', isbn: 'ISBN' };
            let rows = '';
            for (const key in f) {
                const opts = f[key];
                let optsHtml = '';
                opts.forEach((o, i) => {
                    const preview = key === 'image'
                        ? `<img src="${esc(o.value)}" alt="">`
                        : esc(String(key === 'language' && LANGS[o.value] ? LANGS[o.value] : o.value).slice(0, 140));
                    optsHtml += `<label class="esrc">
                        <input type="radio" name="ef_${key}" data-key="${key}" data-idx="${i}" ${i === 0 ? 'checked' : ''}>
                        <span class="esrc-label">${esc(o.label)}</span><span class="esrc-val">${preview}</span></label>`;
                });
                optsHtml += `<label class="esrc esrc-ignore">
                    <input type="radio" name="ef_${key}" data-key="${key}" data-idx="-1">
                    <span class="esrc-label">تجاهل</span></label>`;
                rows += `<div class="enrich-field"><div class="ekey">${labels[key] || key}</div>${optsHtml}</div>`;
            }
            panel.dataset.fields = JSON.stringify(f);
            const srcList = (res.data.sources || []).join('، ');
            panel.innerHTML = rows + `<button type="button" class="mbtn api" id="mApplyEnrich" style="margin-top:10px">تطبيق المحدد</button>
                <div style="font-size:.72rem;color:#6b7280;margin-top:4px">المصادر: ${esc(srcList) || '—'}</div>`;
            panel.classList.add('open');
        });

        // apply selected enriched fields
        $('mEnrichPanel').addEventListener('click', async (e) => {
            if (!e.target.closest('#mApplyEnrich')) return;
            const panel = $('mEnrichPanel');
            const f = JSON.parse(panel.dataset.fields || '{}');

            for (const key in f) {
                const checked = panel.querySelector(`input[name="ef_${key}"]:checked`);
                if (!checked) continue;
                const idx = parseInt(checked.dataset.idx, 10);
                if (isNaN(idx) || idx < 0 || !f[key][idx]) continue; // "تجاهل"
                const val = f[key][idx].value;
                if (key === 'description') $('mDesc').value = val;
                else if (key === 'page_num') $('mPages').value = val;
                else if (key === 'publisher') $('mPublisher').value = val;
                else if (key === 'isbn') $('mIsbn').value = val;
                else if (key === 'language' && LANGS[val]) $('mLang').value = val;
                else if (key === 'image') {
                    const res = await post(`${BASE}/${modalId}/image-from-url`, { url: val });
                    if (res.data.success) {
                        const b = STATE[modalId];
                        b._customImage = res.data.path; b._imgv = res.data.image_version;
                        $('mCover').src = imgSrc(b);
                    }
                }
            }
            panel.classList.remove('open');
            toast('تم تطبيق المحدد');
        });

        $('mClose').addEventListener('click', closeModal);
        $('mCancel').addEventListener('click', closeModal);
        overlay.addEventListener('click', (e) => { if (e.target === overlay) closeModal(); });

        $('mSave').addEventListener('click', () => {
            harvestModalIntoState();
            renderCard(modalId);
            closeModal();
            toast('تم الحفظ');
        });
        $('mApprove').addEventListener('click', () => {
            harvestModalIntoState();
            renderCard(modalId);
            const card = grid.querySelector(`.card[data-id="${modalId}"]`);
            const id = modalId;
            closeModal();
            approveBook(id, card);
        });

        // ---- toolbar ----
        document.querySelectorAll('.filters button').forEach(b => {
            b.addEventListener('click', () => {
                document.querySelectorAll('.filters button').forEach(x => x.classList.remove('active'));
                b.classList.add('active');
                status = b.dataset.status;
                reload();
            });
        });

        $('fLang').addEventListener('change', e => { filters.language = e.target.value; reload(); });
        $('fComp').addEventListener('change', e => { filters.min_completeness = e.target.value; reload(); });
        $('fSource').addEventListener('change', e => {
            filters.source = e.target.value;
            // bod rows have no ISBN so they honestly score ~5-7: the default
            // "quality >= 7" filter would hide most of them. Relax it.
            if (filters.source === 'bod' && Number(filters.min_completeness) > 5) {
                filters.min_completeness = '0';
                $('fComp').value = '0';
            }
            reload();
        });
        $('fDesc').addEventListener('change', e => { filters.has_description = e.target.checked; reload(); });
        $('fStore').addEventListener('change', e => { filters.hide_in_store = e.target.checked; reload(); });

        let searchTimer;
        document.getElementById('searchBox').addEventListener('input', (e) => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => { q = e.target.value.trim(); reload(); }, 350);
        });

        document.getElementById('loadMore').addEventListener('click', () => fetchPage(false));

        // ---- author / publisher autocomplete ----
        function attachAutocomplete(input, list, url) {
            let timer, items = [], active = -1;

            const close = () => { list.classList.remove('open'); active = -1; };
            const render = () => {
                list.innerHTML = items.map((it, i) =>
                    `<div class="ac-item${i === active ? ' active' : ''}" data-i="${i}">${esc(it.name)}` +
                    ((it.nationality || it.country) ? ` <small>${esc(it.nationality || it.country)}</small>` : '') +
                    `</div>`
                ).join('');
            };
            const choose = (i) => { if (items[i]) input.value = items[i].name; close(); };

            input.addEventListener('input', () => {
                clearTimeout(timer);
                const term = input.value.trim();
                if (term.length < 1) { close(); return; }
                timer = setTimeout(async () => {
                    try {
                        const resp = await fetch(`${url}?q=${encodeURIComponent(term)}`, { headers: { 'Accept': 'application/json' } });
                        items = await resp.json();
                        if (!Array.isArray(items) || !items.length) { close(); return; }
                        active = -1; render(); list.classList.add('open');
                    } catch (e) { close(); }
                }, 250);
            });

            input.addEventListener('keydown', (e) => {
                if (!list.classList.contains('open')) return;
                if (e.key === 'ArrowDown') { e.preventDefault(); active = Math.min(active + 1, items.length - 1); render(); }
                else if (e.key === 'ArrowUp') { e.preventDefault(); active = Math.max(active - 1, 0); render(); }
                else if (e.key === 'Enter' && active >= 0) { e.preventDefault(); choose(active); }
                else if (e.key === 'Escape') { close(); }
            });

            list.addEventListener('mousedown', (e) => {
                const item = e.target.closest('.ac-item');
                if (!item) return;
                e.preventDefault();
                choose(Number(item.dataset.i));
            });

            input.addEventListener('blur', () => setTimeout(close, 150));
        }
        attachAutocomplete($('mAuthor'), $('mAuthorAC'), SEARCH_AUTHORS);
        attachAutocomplete($('mPublisher'), $('mPublisherAC'), SEARCH_PUBLISHERS);

        reload();
    </script>
</body>
</html>
