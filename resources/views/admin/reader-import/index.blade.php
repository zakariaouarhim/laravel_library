<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>مراجعة استيراد الكتب</title>
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

        .toolbar { display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; margin-bottom: 20px; }
        .toolbar input { padding: 8px 14px; border: 1px solid #d1d5db; border-radius: 6px; min-width: 220px; }
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
        .badge-stock { background: #dbeafe; color: #1d4ed8; }
        .badge-dupe { background: #fef2f2; color: #b91c1c; }
        .badge-seo { background: #ecfccb; color: #3f6212; }
        .badge-src { background: #f3f4f6; color: #555; }

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
        .enrich-row { display: flex; gap: 8px; padding: 5px 0; border-bottom: 1px dashed #e5e7eb; font-size: .82rem; }
        .enrich-row:last-child { border-bottom: none; }
        .enrich-row label { margin: 0; display: flex; gap: 6px; align-items: flex-start; cursor: pointer; flex: 1; color: #1a1a2e; font-weight: 500; }
        .enrich-row .ekey { font-weight: 700; color: #2563eb; min-width: 64px; }
        .enrich-row img { width: 46px; height: 64px; object-fit: cover; border-radius: 4px; }

        .cat-box { max-height: 230px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 8px; padding: 8px; margin-top: 4px; }
        .cat-row { display: flex; align-items: center; gap: 6px; padding: 1px 0; font-size: .85rem; }
        .cat-row.parent { font-weight: bold; margin-top: 3px; }
        .cat-row.child { padding-right: 18px; }
        .cat-star { cursor: pointer; color: #d1d5db; font-size: 1rem; width: 18px; text-align: center; }
        .cat-star.primary { color: #f59e0b; }
        .cat-hint { font-size: .72rem; color: #6b7280; margin-top: 4px; }

        /* Author / publisher autocomplete */
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

        /* Offset content beside the fixed dashboard sidebar. Overrides the shared
           rule (which only reserves the collapsed width) so the expanded 280px
           sidebar never overlaps the cards; +12px keeps a gap. The sidebar JS
           toggles .sidebar-collapsed on .main-content. */
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
        <h1>مراجعة استيراد الكتب (Reader)</h1>
        <div class="progress-bar"><div class="progress-fill" id="progressFill" style="width:0%"></div></div>
        <div class="stats" id="stats"></div>
        <div class="toolbar">
            <input type="search" id="searchBox" placeholder="ابحث بالعنوان أو المؤلف…">
            <div class="filters">
                <button class="active" data-status="pending">قيد المراجعة</button>
                <button data-status="imported">مستوردة</button>
                <button data-status="skipped">متخطّاة</button>
                <button data-status="all">الكل</button>
            </div>
        </div>
    </div>

    <div class="grid" id="grid"></div>
    <div class="empty" id="empty" style="display:none;">لا توجد كتب لعرضها.</div>
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
                <div class="cat-hint">⭐ أول فئة تحددها تصبح الرئيسية. اضغط النجمة لتغييرها.</div>
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
        const BASE = '{{ url('admin/reader-import') }}';
        const LIST_URL = '{{ route('admin.reader-import.list') }}';
        const SEARCH_AUTHORS = '{{ route('admin.search.authors') }}';
        const SEARCH_PUBLISHERS = '{{ route('admin.search.publishers') }}';
        const LANGS = { arabic: 'العربية', english: 'الإنجليزية', french: 'الفرنسية' };

        let status = 'pending';
        let page = 1;
        let q = '';
        let loading = false;
        const STATE = {};            // id -> book object (mutable working copy)

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
        function imgSrc(b) {
            return `${BASE}/${b.id}/image${b._imgv ? '?v=' + b._imgv : ''}`;
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
            const cover = b.image_exists
                ? `<img class="cover" src="${imgSrc(b)}" loading="lazy" alt="">`
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
                            <span class="badge badge-stock">مخزون: ${b.stock}</span>
                            ${b.duplicate ? '<span class="badge badge-dupe">عنوان مكرر</span>' : ''}
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

        async function fetchPage(replace) {
            if (loading) return;
            loading = true;
            try {
                const url = `${LIST_URL}?status=${status}&page=${page}&q=${encodeURIComponent(q)}`;
                const resp = await fetch(url, { headers: { 'Accept': 'application/json' } });
                const data = await resp.json();

                if (replace) grid.innerHTML = '';
                data.books.forEach(b => {
                    b.quantity = b.quantity ?? b.stock;
                    b.category_ids = b.category_ids || [];
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
                if (!confirm('يوجد كتاب بنفس العنوان. هل تريد الاستيراد رغم ذلك؟')) return;
                card.classList.add('busy');
                res = await post(`${BASE}/${id}/approve`, { ...approvePayload(b), force: true });
            }
            card.classList.remove('busy');
            if (res.data.success) { toast('تم إنشاء الكتاب'); b.status = 'imported'; refreshAfterAction(card); }
            else toast(res.data.message || 'فشل الاستيراد');
        }

        // ---- card-level interactions (edit fields, approve, skip, open modal) ----
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
                const resp = await fetch(`${LIST_URL}?status=${status}&page=1&q=${encodeURIComponent(q)}`, { headers: { 'Accept': 'application/json' } });
                const data = await resp.json();
                renderStats(data.counts);
            } catch (e) {}
        }

        // ========================= Detail modal =========================
        const overlay = document.getElementById('overlay');
        let modalId = null;
        let modalCats = { ids: new Set(), primary: null };

        const $ = id => document.getElementById(id);

        function buildCatBox() {
            $('mCats').innerHTML = CATEGORIES.map(c => {
                const checked = modalCats.ids.has(c.id);
                const primary = modalCats.primary === c.id;
                return `<div class="cat-row ${c.parent ? 'parent' : 'child'}">
                    <input type="checkbox" class="cat-cb" value="${c.id}" ${checked ? 'checked' : ''}>
                    <span class="cat-star ${primary ? 'primary' : ''}" data-id="${c.id}">${primary ? '★' : '☆'}</span>
                    <span>${c.parent ? '' : '── '}${esc(c.name)}</span>
                </div>`;
            }).join('');
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
            $('mCover').src = b.image_exists ? imgSrc(b) : '';
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

        // category checkbox + primary-star handling
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
        });
        $('mCats').addEventListener('click', (e) => {
            const star = e.target.closest('.cat-star');
            if (!star) return;
            const id = Number(star.dataset.id);
            if (!modalCats.ids.has(id)) { toast('فعّل الفئة أولاً'); return; }
            modalCats.primary = id;
            refreshStars();
        });

        // image upload
        $('mFile').addEventListener('change', async (e) => {
            const file = e.target.files[0];
            if (!file) return;
            const fd = new FormData();
            fd.append('image', file);
            const res = await postForm(`${BASE}/${modalId}/image`, fd);
            if (res.data.success) {
                const b = STATE[modalId];
                b.image_exists = true; b._imgv = res.data.image_version;
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
                $('mDesc').value = res.data.description;
                STATE[modalId].description = res.data.description;
                STATE[modalId].description_rewritten = true;
                toast('تمت إعادة الصياغة');
            } else toast(res.data.message || 'فشلت إعادة الصياغة');
        });

        // API enrich preview
        $('mEnrich').addEventListener('click', async () => {
            const btn = $('mEnrich');
            btn.disabled = true; btn.textContent = '... جارٍ البحث';
            const res = await post(`${BASE}/${modalId}/enrich-preview`, {
                name: $('mName').value, author: $('mAuthor').value, isbn: $('mIsbn').value,
            });
            btn.disabled = false; btn.textContent = 'إثراء من API (معاينة)';
            const panel = $('mEnrichPanel');

            if (!res.data.success) { toast(res.data.message || 'فشل الإثراء'); return; }
            if (!res.data.found) { toast('لم يتم العثور على بيانات'); return; }

            const f = res.data.fields;
            const labels = { description: 'الوصف', image: 'الصورة', page_num: 'الصفحات', publisher: 'الناشر', language: 'اللغة', isbn: 'ISBN' };
            let rows = '';
            for (const key in f) {
                const val = f[key].api;
                const display = key === 'image'
                    ? `<img src="${esc(val)}" alt=""> صورة من Google`
                    : esc(String(val).slice(0, 160));
                rows += `<div class="enrich-row">
                    <label><input type="checkbox" class="echk" data-key="${key}" checked>
                    <span class="ekey">${labels[key] || key}</span><span>${display}</span></label>
                </div>`;
            }
            panel.dataset.fields = JSON.stringify(f);
            panel.innerHTML = rows + `<button type="button" class="mbtn api" id="mApplyEnrich" style="margin-top:10px">تطبيق المحدد</button>
                <div style="font-size:.72rem;color:#6b7280;margin-top:4px">المصدر: ${res.data.search_method || '—'}</div>`;
            panel.classList.add('open');
        });

        // apply selected enriched fields
        $('mEnrichPanel').addEventListener('click', async (e) => {
            if (!e.target.closest('#mApplyEnrich')) return;
            const panel = $('mEnrichPanel');
            const f = JSON.parse(panel.dataset.fields || '{}');
            const picked = Array.from(panel.querySelectorAll('.echk:checked')).map(c => c.dataset.key);

            for (const key of picked) {
                const val = f[key].api;
                if (key === 'description') $('mDesc').value = val;
                else if (key === 'page_num') $('mPages').value = val;
                else if (key === 'publisher') $('mPublisher').value = val;
                else if (key === 'isbn') $('mIsbn').value = val;
                else if (key === 'language' && LANGS[val]) $('mLang').value = val;
                else if (key === 'image') {
                    const res = await post(`${BASE}/${modalId}/image-from-url`, { url: val });
                    if (res.data.success) {
                        const b = STATE[modalId];
                        b.image_exists = true; b._imgv = res.data.image_version;
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

        let searchTimer;
        document.getElementById('searchBox').addEventListener('input', (e) => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => { q = e.target.value.trim(); reload(); }, 350);
        });

        document.getElementById('loadMore').addEventListener('click', () => fetchPage(false));

        // ---- author / publisher autocomplete (reuses the admin search endpoints) ----
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
                e.preventDefault();   // keep focus so blur doesn't close before the pick registers
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
