<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Book Import Review</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background: #f0f2f5; color: #1a1a2e; padding: 20px; }

        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { font-size: 1.8rem; margin-bottom: 8px; }
        .stats { display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; margin-bottom: 20px; }
        .stat { background: #fff; padding: 10px 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .stat strong { color: #2563eb; }

        .filters { display: flex; gap: 10px; justify-content: center; margin-bottom: 20px; flex-wrap: wrap; }
        .filters button { padding: 8px 16px; border: 1px solid #d1d5db; border-radius: 6px; cursor: pointer; background: #fff; transition: all 0.2s; }
        .filters button.active { background: #2563eb; color: #fff; border-color: #2563eb; }
        .filters button:hover { background: #e5e7eb; }
        .filters button.active:hover { background: #1d4ed8; }

        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(340px, 1fr)); gap: 20px; }

        .card { background: #fff; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); overflow: hidden; transition: box-shadow 0.2s; }
        .card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.12); }
        .card.approved { border: 2px solid #16a34a; opacity: 0.7; }
        .card.skipped { border: 2px solid #dc2626; opacity: 0.5; }
        .card.processing { opacity: 0.5; pointer-events: none; }

        .card-top { display: flex; gap: 15px; padding: 15px; }
        .cover-img { width: 100px; min-height: 140px; object-fit: cover; border-radius: 6px; background: #e5e7eb; flex-shrink: 0; }
        .card-info { flex: 1; min-width: 0; }

        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; margin-bottom: 6px; }
        .badge-confidence { background: #dbeafe; color: #1d4ed8; }
        .badge-source { background: #f0fdf4; color: #166534; }
        .badge-folder { background: #fef3c7; color: #92400e; }

        .editable { width: 100%; padding: 6px 8px; border: 1px solid #e5e7eb; border-radius: 4px; font-size: 0.9rem; margin-top: 4px; direction: auto; }
        .editable:focus { border-color: #2563eb; outline: none; box-shadow: 0 0 0 2px rgba(37,99,235,0.2); }
        textarea.editable { resize: vertical; min-height: 60px; font-family: inherit; }

        label { font-size: 0.8rem; color: #6b7280; font-weight: 500; display: block; margin-top: 8px; }
        label:first-child { margin-top: 0; }

        .card-actions { display: flex; gap: 8px; padding: 12px 15px; border-top: 1px solid #f3f4f6; }
        .btn { flex: 1; padding: 8px; border: none; border-radius: 6px; font-size: 0.9rem; font-weight: 600; cursor: pointer; transition: all 0.2s; }
        .btn-approve { background: #16a34a; color: #fff; }
        .btn-approve:hover { background: #15803d; }
        .btn-skip { background: #f3f4f6; color: #6b7280; }
        .btn-skip:hover { background: #e5e7eb; }

        .status-badge { text-align: center; padding: 8px; font-weight: 600; font-size: 0.9rem; }
        .status-badge.approved { color: #16a34a; background: #f0fdf4; }
        .status-badge.skipped { color: #dc2626; background: #fef2f2; }
        .status-badge.error { color: #dc2626; background: #fef2f2; }

        .bulk-actions { text-align: center; margin: 20px 0; }
        .bulk-actions button { padding: 10px 24px; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; margin: 0 5px; }
        .btn-approve-all { background: #16a34a; color: #fff; }
        .btn-approve-all:hover { background: #15803d; }

        .progress-bar { width: 100%; height: 6px; background: #e5e7eb; border-radius: 3px; margin: 10px 0; overflow: hidden; }
        .progress-fill { height: 100%; background: #16a34a; transition: width 0.3s; border-radius: 3px; }

        .empty { text-align: center; padding: 60px 20px; color: #6b7280; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Book Import Review</h1>
        <div class="progress-bar"><div class="progress-fill" id="progressFill" style="width: 0%"></div></div>
        <div class="stats" id="stats"></div>
        <div class="filters">
            <button class="active" data-filter="pending" onclick="filterBooks('pending')">Pending</button>
            <button data-filter="approved" onclick="filterBooks('approved')">Approved</button>
            <button data-filter="skipped" onclick="filterBooks('skipped')">Skipped</button>
            <button data-filter="all" onclick="filterBooks('all')">All</button>
        </div>
        <div class="bulk-actions">
            <button class="btn-approve-all" onclick="approveAllVisible()">Approve All Visible</button>
        </div>
    </div>

    <div class="grid" id="booksGrid"></div>
    <div class="empty" id="emptyState" style="display:none;">No books to show.</div>

    <script>
        let books = [];
        let reviewState = {}; // filename -> 'approved' | 'skipped' | 'pending'
        let currentFilter = 'pending';

        async function loadBooks() {
            try {
                const resp = await fetch('/api/import/staged', {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await resp.json();
                books = data.books || [];

                // Load saved review state from localStorage
                const saved = localStorage.getItem('bookReviewState');
                if (saved) reviewState = JSON.parse(saved);

                // Initialize pending state for new books
                books.forEach(b => {
                    if (!reviewState[b.file]) reviewState[b.file] = 'pending';
                });

                renderBooks();
                updateStats();
            } catch (e) {
                document.getElementById('booksGrid').innerHTML = '<div class="empty">Failed to load staged books. Make sure Laravel is running and staging data exists.</div>';
            }
        }

        function renderBooks() {
            const grid = document.getElementById('booksGrid');
            const empty = document.getElementById('emptyState');

            const filtered = books.filter(b => {
                if (currentFilter === 'all') return true;
                return reviewState[b.file] === currentFilter;
            });

            if (filtered.length === 0) {
                grid.innerHTML = '';
                empty.style.display = 'block';
                return;
            }
            empty.style.display = 'none';

            grid.innerHTML = filtered.map((book, i) => {
                const state = reviewState[book.file] || 'pending';
                const globalIdx = books.indexOf(book);
                return `
                <div class="card ${state}" id="card-${globalIdx}" data-file="${book.file}">
                    <div class="card-top">
                        <img class="cover-img" src="" data-filepath="${book.file_path}" id="img-${globalIdx}" alt="cover" loading="lazy">
                        <div class="card-info">
                            <span class="badge badge-folder">${book.folder}</span>
                            <span class="badge badge-confidence">Confidence: ${(book.confidence * 100).toFixed(0)}%</span>
                            <span class="badge badge-source">${book.source || 'none'}</span>

                            <label>Title</label>
                            <input class="editable" id="title-${globalIdx}" value="${escHtml(book.title || '')}" ${state !== 'pending' ? 'disabled' : ''}>

                            <label>Author</label>
                            <input class="editable" id="author-${globalIdx}" value="${escHtml(book.author || '')}" ${state !== 'pending' ? 'disabled' : ''}>

                            <label>Description</label>
                            <textarea class="editable" id="desc-${globalIdx}" rows="3" ${state !== 'pending' ? 'disabled' : ''}>${escHtml(book.description || '')}</textarea>
                        </div>
                    </div>
                    ${state === 'pending' ? `
                    <div class="card-actions">
                        <button class="btn btn-approve" onclick="approveBook(${globalIdx})">Approve</button>
                        <button class="btn btn-skip" onclick="skipBook(${globalIdx})">Skip</button>
                    </div>
                    ` : `
                    <div class="status-badge ${state}">${state === 'approved' ? 'Approved' : state === 'skipped' ? 'Skipped' : state}</div>
                    `}
                </div>`;
            }).join('');

            // Load cover images lazily
            filtered.forEach((book, i) => {
                const globalIdx = books.indexOf(book);
                loadCoverImage(globalIdx, book.file_path);
            });
        }

        async function loadCoverImage(idx, filePath) {
            try {
                const resp = await fetch('/api/import/book/image', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ file_path: filePath })
                });
                const data = await resp.json();
                const img = document.getElementById(`img-${idx}`);
                if (img && data.base64) {
                    img.src = `data:${data.mime_type};base64,${data.base64}`;
                }
            } catch (e) { /* ignore */ }
        }

        async function approveBook(idx) {
            const book = books[idx];
            const card = document.getElementById(`card-${idx}`);
            card.classList.add('processing');

            const payload = {
                title: document.getElementById(`title-${idx}`).value,
                author: document.getElementById(`author-${idx}`).value,
                description: document.getElementById(`desc-${idx}`).value,
                isbn: book.isbn || '',
                publisher: book.publisher || '',
                page_num: book.page_num || 0,
                language: book.language,
                price: book.price,
                category_name: book.category_name,
                file_path: book.file_path,
                source: book.source || ''
            };

            try {
                const resp = await fetch('/api/import/book', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const result = await resp.json();

                if (resp.ok || resp.status === 409) {
                    reviewState[book.file] = 'approved';
                } else {
                    reviewState[book.file] = 'error';
                    alert('Error: ' + (result.message || 'Unknown error'));
                }
            } catch (e) {
                alert('Network error: ' + e.message);
            }

            saveState();
            card.classList.remove('processing');
            renderBooks();
            updateStats();
        }

        function skipBook(idx) {
            const book = books[idx];
            reviewState[book.file] = 'skipped';
            saveState();
            renderBooks();
            updateStats();
        }

        async function approveAllVisible() {
            const pending = books.filter(b => reviewState[b.file] === 'pending');
            if (!confirm(`Approve all ${pending.length} pending books without edits?`)) return;

            for (let i = 0; i < pending.length; i++) {
                const globalIdx = books.indexOf(pending[i]);
                await approveBook(globalIdx);
                // Small delay to avoid overwhelming the server
                await new Promise(r => setTimeout(r, 200));
            }
        }

        function updateStats() {
            const total = books.length;
            const approved = books.filter(b => reviewState[b.file] === 'approved').length;
            const skipped = books.filter(b => reviewState[b.file] === 'skipped').length;
            const pending = total - approved - skipped;
            const pct = total > 0 ? ((approved + skipped) / total * 100).toFixed(0) : 0;

            document.getElementById('stats').innerHTML = `
                <div class="stat">Total: <strong>${total}</strong></div>
                <div class="stat">Pending: <strong>${pending}</strong></div>
                <div class="stat">Approved: <strong style="color:#16a34a">${approved}</strong></div>
                <div class="stat">Skipped: <strong style="color:#dc2626">${skipped}</strong></div>
            `;
            document.getElementById('progressFill').style.width = `${pct}%`;
        }

        function filterBooks(filter) {
            currentFilter = filter;
            document.querySelectorAll('.filters button').forEach(b => b.classList.remove('active'));
            document.querySelector(`.filters button[data-filter="${filter}"]`).classList.add('active');
            renderBooks();
        }

        function saveState() {
            localStorage.setItem('bookReviewState', JSON.stringify(reviewState));
        }

        function escHtml(str) {
            return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        loadBooks();
    </script>
</body>
</html>
