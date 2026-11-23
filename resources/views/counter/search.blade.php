@extends('counter.layout')

@section('title', 'بحث عن طلب')

@section('content')
    {{-- Big auto-focused search box: staff types the phone number (or name) --}}
    <div class="mb-3">
        <input type="search" id="searchBox" class="form-control form-control-lg shadow-sm"
               style="font-size: 1.6rem; padding: .9rem 1.2rem; border-radius: 1rem;"
               placeholder="🔍 رقم الهاتف أو اسم الزبون..."
               autofocus autocomplete="off">
    </div>

    <div class="form-check form-switch mb-3 fs-5">
        <input class="form-check-input" type="checkbox" id="showAll" style="transform: scale(1.4);">
        <label class="form-check-label me-2" for="showAll">إظهار الطلبات المستلمة أيضا</label>
    </div>

    <div id="results"></div>
    <div id="emptyMsg" class="text-center text-muted py-5 fs-4 d-none">لا توجد نتائج</div>

    {{-- Undo toast shown for a few seconds after marking an order collected --}}
    <div id="undoToast" class="position-fixed bottom-0 start-50 translate-middle-x mb-4 d-none"
         style="z-index: 1080;">
        <div class="bg-dark text-white rounded-4 shadow-lg d-flex align-items-center gap-3 px-4 py-3">
            <span id="undoText" class="fs-5"></span>
            <button id="undoBtn" class="btn btn-warning btn-lg fw-bold">تراجع</button>
        </div>
    </div>
@endsection

@section('scripts')
<script>
(function () {
    const searchBox = document.getElementById('searchBox');
    const showAll   = document.getElementById('showAll');
    const results   = document.getElementById('results');
    const emptyMsg  = document.getElementById('emptyMsg');
    const undoToast = document.getElementById('undoToast');
    const undoText  = document.getElementById('undoText');
    const undoBtn   = document.getElementById('undoBtn');
    const csrf      = document.querySelector('meta[name="csrf-token"]').content;
    const searchUrl = @json(route('counter.search'));
    const baseUrl   = @json(url('/counter'));

    let debounceTimer = null;
    let controller    = null; // aborts the previous fetch when typing fast
    let undoTimer     = null;
    let undoOrderId   = null;

    // Focus again via JS: some tablets ignore the autofocus attribute
    window.addEventListener('load', () => searchBox.focus());

    searchBox.addEventListener('input', scheduleSearch);
    showAll.addEventListener('change', () => runSearch());

    function scheduleSearch() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(runSearch, 300);
    }

    async function runSearch() {
        const q = searchBox.value.trim();
        if (controller) controller.abort();

        if (q === '') {
            results.innerHTML = '';
            emptyMsg.classList.add('d-none');
            return;
        }

        controller = new AbortController();
        try {
            const url = searchUrl + '?q=' + encodeURIComponent(q) + (showAll.checked ? '&all=1' : '');
            const res = await fetch(url, { signal: controller.signal, headers: { 'Accept': 'application/json' } });
            if (!res.ok) return;
            render(await res.json());
        } catch (e) {
            if (e.name !== 'AbortError') console.error(e);
        }
    }

    function render(orders) {
        results.innerHTML = '';
        emptyMsg.classList.toggle('d-none', orders.length > 0);
        orders.forEach(o => results.appendChild(card(o)));
    }

    // Build the card with DOM APIs (textContent) so names/phones are never
    // interpreted as HTML.
    function card(o) {
        const collected = o.status === 'collected';
        const el = document.createElement('div');
        el.className = 'card shadow-sm mb-3 rounded-4' + (collected ? ' opacity-50' : '');
        el.dataset.orderId = o.id;

        const body = document.createElement('div');
        body.className = 'card-body d-flex align-items-center gap-3 flex-wrap';

        // Order number — the most prominent element
        const numBox = document.createElement('div');
        numBox.className = 'text-center px-3';
        numBox.innerHTML = '<div class="text-muted fs-6">رقم الطلب</div>';
        const num = document.createElement('div');
        num.className = 'num-huge text-primary';
        num.textContent = o.id;
        numBox.appendChild(num);

        // Shelf
        const shelfBox = document.createElement('div');
        shelfBox.className = 'text-center px-3 border-end';
        shelfBox.innerHTML = '<div class="text-muted fs-6">الرف</div>';
        const shelf = document.createElement('div');
        shelf.className = 'num-big text-success';
        shelf.textContent = o.shelf || '—';
        shelfBox.appendChild(shelf);

        // Customer identity (to confirm before handing over)
        const info = document.createElement('div');
        info.className = 'flex-grow-1';
        const name = document.createElement('div');
        name.className = 'fs-4 fw-bold';
        name.textContent = o.customer_name;
        const phone = document.createElement('div');
        phone.className = 'fs-5 text-muted';
        phone.setAttribute('dir', 'ltr');
        phone.textContent = o.phone;
        info.append(name, phone);

        body.append(numBox, shelfBox, info);

        if (!collected) {
            const btn = document.createElement('button');
            btn.className = 'btn btn-success btn-xl';
            btn.textContent = '✓ تم الاستلام';
            btn.addEventListener('click', () => collect(o, el, btn));
            body.appendChild(btn);
        } else {
            const badge = document.createElement('span');
            badge.className = 'badge bg-secondary fs-6 p-3 rounded-3';
            badge.textContent = 'تم الاستلام';
            body.appendChild(badge);
        }

        el.appendChild(body);
        return el;
    }

    async function collect(o, cardEl, btn) {
        btn.disabled = true;
        try {
            const res = await fetch(baseUrl + '/' + o.id + '/collect', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            });
            if (!res.ok) { btn.disabled = false; return; }
            cardEl.remove();
            showUndo(o);
        } catch (e) {
            btn.disabled = false;
        }
    }

    function showUndo(o) {
        undoOrderId = o.id;
        undoText.textContent = 'تم استلام الطلب رقم ' + o.id;
        undoToast.classList.remove('d-none');
        clearTimeout(undoTimer);
        undoTimer = setTimeout(hideUndo, 6000);
    }

    function hideUndo() {
        undoToast.classList.add('d-none');
        undoOrderId = null;
    }

    undoBtn.addEventListener('click', async () => {
        if (!undoOrderId) return;
        const id = undoOrderId;
        hideUndo();
        try {
            await fetch(baseUrl + '/' + id + '/uncollect', {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            });
        } finally {
            runSearch(); // bring the card back
        }
    });
})();
</script>
@endsection
