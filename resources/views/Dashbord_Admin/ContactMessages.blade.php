<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>إدارة الرسائل</title>
    <link rel="stylesheet" href="{{ asset('css/variables.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.rtl.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/sidebardaschboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/contactMessage.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    
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
