<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>إدارة الرسائل</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.rtl.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/sidebardaschboard.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        * { font-family: 'Cairo', sans-serif; }
        body { background-color: #f5f7fa; }
        .main-content { padding: 2rem 1rem; }

        /* Page Header */
        .page-header {
            margin-bottom: 2rem;
            border-bottom: 3px solid #2C4B79;
            padding-bottom: 1rem;
        }
        .page-header h2 {
            color: #2c3e50;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .page-header i { color: #2C4B79; font-size: 2rem; }

        /* Stats Cards */
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-right: 4px solid #2C4B79;
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .stat-card.total { border-right-color: #2C4B79; }
        .stat-card.unread { border-right-color: #e74c3c; }
        .stat-card.today { border-right-color: #48CAE4; }
        .stat-card-icon { font-size: 2rem; margin-bottom: 0.5rem; }
        .stat-card-title { color: #7f8c8d; font-size: 0.9rem; margin-bottom: 0.3rem; }
        .stat-card-value { font-size: 1.8rem; font-weight: 700; color: #2c3e50; }

        /* Filter Section */
        .filter-section {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .filter-section .form-control,
        .filter-section .form-select {
            border-radius: 10px;
            border: 1px solid #e0e6ed;
            padding: 0.8rem;
        }
        .filter-section .form-control:focus,
        .filter-section .form-select:focus {
            border-color: #2C4B79;
            box-shadow: 0 0 0 0.2rem rgba(44,75,121,0.25);
        }
        .btn-apply-filters {
            background: linear-gradient(135deg, #2C4B79, #48CAE4);
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: 600;
            padding: 0.8rem;
            transition: all 0.3s ease;
        }
        .btn-apply-filters:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(44,75,121,0.3);
            color: white;
        }

        /* Table */
        .table-section {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            overflow: hidden;
        }
        .table thead th {
            background: #2C4B79;
            color: white;
            font-weight: 600;
            padding: 1rem;
            border: none;
            white-space: nowrap;
        }
        .table tbody tr {
            transition: background 0.2s;
            cursor: pointer;
        }
        .table tbody tr:hover {
            background: #f0f4ff;
        }
        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #f0f0f0;
        }
        .table tbody tr.unread-row {
            background: #f8f9ff;
            font-weight: 600;
        }
        .table tbody tr.unread-row:hover {
            background: #eef1ff;
        }

        /* Status dot */
        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
        }
        .status-dot.unread { background: #e74c3c; }
        .status-dot.read { background: #27ae60; }

        /* Action buttons */
        .action-buttons { display: flex; gap: 6px; justify-content: center; }
        .btn-action {
            padding: 6px 14px;
            border-radius: 8px;
            border: none;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-view {
            background: #e8f4fd;
            color: #2C4B79;
        }
        .btn-view:hover {
            background: #2C4B79;
            color: white;
        }
        .btn-toggle-read {
            background: #fef3e2;
            color: #f39c12;
        }
        .btn-toggle-read:hover {
            background: #f39c12;
            color: white;
        }
        .btn-delete {
            background: #fde8e8;
            color: #e74c3c;
        }
        .btn-delete:hover {
            background: #e74c3c;
            color: white;
        }

        /* Sender info */
        .sender-name {
            font-weight: 600;
            color: #2c3e50;
            display: block;
        }
        .sender-email {
            font-size: 0.8rem;
            color: #7f8c8d;
        }
        .subject-text {
            max-width: 250px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Modal */
        .message-modal .modal-header {
            background: linear-gradient(135deg, #2C4B79, #48CAE4);
            color: white;
            border-radius: 15px 15px 0 0;
        }
        .message-modal .modal-content {
            border-radius: 15px;
            border: none;
        }
        .message-modal .btn-close {
            filter: invert(1);
        }
        .message-detail-label {
            font-weight: 600;
            color: #7f8c8d;
            font-size: 0.85rem;
            margin-bottom: 2px;
        }
        .message-detail-value {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 1rem;
        }
        .message-body {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            line-height: 1.8;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        .empty-state i {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 1rem;
        }
        .empty-state p {
            color: #7f8c8d;
            font-size: 1.1rem;
        }

        /* Pagination */
        .pagination-wrapper {
            padding: 1.5rem;
            display: flex;
            justify-content: center;
        }
        .pagination .page-link {
            border-radius: 8px;
            margin: 0 3px;
            color: #2C4B79;
            border-color: #e0e6ed;
        }
        .pagination .page-item.active .page-link {
            background: #2C4B79;
            border-color: #2C4B79;
        }

        @media (max-width: 768px) {
            .stats-cards { grid-template-columns: 1fr; }
            .subject-text { max-width: 120px; }
            .action-buttons { flex-direction: column; }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            @include('Dashbord_Admin.Sidebar')

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <!-- Page Header -->
                <div class="page-header">
                    <h2>
                        <i class="fas fa-envelope-open-text"></i>
                        إدارة الرسائل
                    </h2>
                </div>

                <!-- Success Message -->
                @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong>{{ session('success') }}</strong>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

                <!-- Stats Cards -->
                <div class="stats-cards">
                    <div class="stat-card total">
                        <div class="stat-card-icon"><i class="fas fa-envelope text-primary"></i></div>
                        <div class="stat-card-title">إجمالي الرسائل</div>
                        <div class="stat-card-value">{{ $totalCount }}</div>
                    </div>
                    <div class="stat-card unread">
                        <div class="stat-card-icon"><i class="fas fa-envelope-open text-danger"></i></div>
                        <div class="stat-card-title">غير مقروءة</div>
                        <div class="stat-card-value">{{ $unreadCount }}</div>
                    </div>
                    <div class="stat-card today">
                        <div class="stat-card-icon"><i class="fas fa-calendar-day text-info"></i></div>
                        <div class="stat-card-title">رسائل اليوم</div>
                        <div class="stat-card-value">{{ $todayCount }}</div>
                    </div>
                </div>

                <!-- Filter Section -->
                <div class="filter-section">
                    <form method="GET" action="{{ route('admin.contact-messages.index') }}">
                        <div class="row g-3">
                            <div class="col-md-5">
                                <input type="text" name="search" class="form-control"
                                    placeholder="بحث بالاسم أو البريد أو الموضوع..."
                                    value="{{ request('search') }}">
                            </div>
                            <div class="col-md-4">
                                <select name="filter" class="form-select">
                                    <option value="">كل الرسائل</option>
                                    <option value="unread" {{ request('filter') === 'unread' ? 'selected' : '' }}>غير مقروءة</option>
                                    <option value="read" {{ request('filter') === 'read' ? 'selected' : '' }}>مقروءة</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-apply-filters w-100">
                                    <i class="fas fa-filter me-2"></i>تطبيق
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Messages Table -->
                <div class="table-section">
                    @if($messages->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover text-center align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>الحالة</th>
                                    <th>المرسل</th>
                                    <th>الموضوع</th>
                                    <th>التاريخ</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($messages as $msg)
                                <tr class="{{ !$msg->is_read ? 'unread-row' : '' }}">
                                    <td>
                                        <span class="status-dot {{ $msg->is_read ? 'read' : 'unread' }}"
                                              title="{{ $msg->is_read ? 'مقروءة' : 'غير مقروءة' }}"></span>
                                    </td>
                                    <td class="text-start">
                                        <span class="sender-name">{{ $msg->name }}</span>
                                        <span class="sender-email">{{ $msg->email }}</span>
                                    </td>
                                    <td class="text-start">
                                        <span class="subject-text" title="{{ $msg->subject }}">{{ $msg->subject }}</span>
                                    </td>
                                    <td>{{ $msg->created_at->format('d/m/Y H:i') }}</td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-action btn-view" onclick="viewMessage({{ $msg->id }})" title="عرض">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn-action btn-toggle-read" onclick="toggleRead({{ $msg->id }})" title="{{ $msg->is_read ? 'تحديد كغير مقروءة' : 'تحديد كمقروءة' }}">
                                                <i class="fas {{ $msg->is_read ? 'fa-envelope' : 'fa-envelope-open' }}"></i>
                                            </button>
                                            <button class="btn-action btn-delete" onclick="confirmDelete({{ $msg->id }})" title="حذف">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($messages->hasPages())
                    <div class="pagination-wrapper">
                        {{ $messages->links() }}
                    </div>
                    @endif
                    @else
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>لا توجد رسائل</p>
                    </div>
                    @endif
                </div>
            </main>
        </div>
    </div>

    <!-- View Message Modal -->
    <div class="modal fade message-modal" id="messageModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-envelope-open me-2"></i>
                        تفاصيل الرسالة
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="message-detail-label">الاسم</div>
                            <div class="message-detail-value" id="modalName">-</div>
                        </div>
                        <div class="col-md-6">
                            <div class="message-detail-label">البريد الإلكتروني</div>
                            <div class="message-detail-value">
                                <a href="#" id="modalEmail" class="text-decoration-none">-</a>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="message-detail-label">الموضوع</div>
                            <div class="message-detail-value" id="modalSubject">-</div>
                        </div>
                        <div class="col-md-6">
                            <div class="message-detail-label">التاريخ</div>
                            <div class="message-detail-value" id="modalDate">-</div>
                        </div>
                    </div>
                    <div class="message-detail-label">الرسالة</div>
                    <div class="message-body" id="modalMessage">-</div>
                </div>
                <div class="modal-footer">
                    <a href="#" id="modalReplyBtn" class="btn btn-primary">
                        <i class="fas fa-reply me-1"></i>رد عبر البريد
                    </a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content" style="border-radius: 15px;">
                <div class="modal-body text-center p-4">
                    <i class="fas fa-exclamation-triangle text-danger" style="font-size: 3rem;"></i>
                    <h5 class="mt-3 mb-2">حذف الرسالة</h5>
                    <p class="text-muted">هل أنت متأكد من حذف هذه الرسالة؟ لا يمكن التراجع عن هذا الإجراء.</p>
                    <form id="deleteForm" method="POST">
                        @csrf
                        @method('DELETE')
                        <div class="d-flex gap-2 justify-content-center mt-3">
                            <button type="submit" class="btn btn-danger px-4">حذف</button>
                            <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">إلغاء</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewMessage(id) {
            fetch('/admin/contact-messages/' + id, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(function(response) { return response.json(); })
            .then(function(msg) {
                document.getElementById('modalName').textContent = msg.name;
                document.getElementById('modalEmail').textContent = msg.email;
                document.getElementById('modalEmail').href = 'mailto:' + msg.email;
                document.getElementById('modalSubject').textContent = msg.subject;
                document.getElementById('modalMessage').textContent = msg.message;
                document.getElementById('modalReplyBtn').href = 'mailto:' + msg.email + '?subject=Re: ' + encodeURIComponent(msg.subject);

                var date = new Date(msg.created_at);
                document.getElementById('modalDate').textContent = date.toLocaleDateString('ar-EG', {
                    year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit'
                });

                // Mark row as read in the table
                var rows = document.querySelectorAll('tr[class]');
                rows.forEach(function(row) {
                    // Update will happen on reload
                });

                var modal = new bootstrap.Modal(document.getElementById('messageModal'));
                modal.show();
            });
        }

        function toggleRead(id) {
            fetch('/admin/contact-messages/' + id + '/toggle-read', {
                method: 'PATCH',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json'
                }
            })
            .then(function(response) { return response.json(); })
            .then(function() {
                location.reload();
            });
        }

        function confirmDelete(id) {
            document.getElementById('deleteForm').action = '/admin/contact-messages/' + id;
            var modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }
    </script>
</body>
</html>
