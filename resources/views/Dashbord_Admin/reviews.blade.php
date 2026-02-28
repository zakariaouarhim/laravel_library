<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>إدارة التقييمات</title>
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

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
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
            cursor: pointer;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }
        .stat-card.total { border-right-color: #2C4B79; }
        .stat-card.pending { border-right-color: #f39c12; }
        .stat-card.approved { border-right-color: #27ae60; }
        .stat-card.rejected { border-right-color: #e74c3c; }
        .stat-card-icon { font-size: 2rem; margin-bottom: 0.5rem; }
        .stat-card-title { color: #7f8c8d; font-size: 0.9rem; margin-bottom: 0.3rem; }
        .stat-card-value { font-size: 1.8rem; font-weight: 700; color: #2c3e50; }

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
        }
        .table tbody tr:hover {
            background: #f0f4ff;
        }
        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #f0f0f0;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .status-badge.approved { background: #d4edda; color: #155724; }
        .status-badge.pending { background: #fff3cd; color: #856404; }
        .status-badge.rejected { background: #f8d7da; color: #721c24; }

        .action-buttons { display: flex; gap: 6px; justify-content: center; flex-wrap: wrap; }
        .btn-action {
            padding: 5px 12px;
            border-radius: 8px;
            border: none;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .btn-approve { background: #d4edda; color: #155724; }
        .btn-approve:hover { background: #27ae60; color: white; }
        .btn-reject { background: #fff3cd; color: #856404; }
        .btn-reject:hover { background: #f39c12; color: white; }
        .btn-delete { background: #f8d7da; color: #721c24; }
        .btn-delete:hover { background: #e74c3c; color: white; }

        .review-stars { color: #f39c12; font-size: 0.85rem; }
        .review-comment {
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .toast-notification {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 9999;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            display: none;
        }
        .toast-notification.success { background: #d4edda; color: #155724; }
        .toast-notification.error { background: #f8d7da; color: #721c24; }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #7f8c8d;
        }
        .empty-state i { font-size: 3rem; margin-bottom: 1rem; display: block; }

        .review-detail-modal .modal-header { background: #2C4B79; color: white; }
        .review-detail-modal .modal-header .btn-close { filter: invert(1); }
    </style>
</head>
<body>
    @include('Dashbord_Admin.Sidebar')

    <div class="main-content">
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="page-header">
                <h2><i class="fas fa-star-half-alt"></i> إدارة التقييمات</h2>
            </div>

            <!-- Stats Cards -->
            @php
                $totalReviews = ($statusCounts->get('approved', 0) + $statusCounts->get('pending', 0) + $statusCounts->get('rejected', 0));
            @endphp
            <div class="stats-cards">
                <a href="{{ route('admin.reviews.index') }}" class="stat-card total text-decoration-none">
                    <div class="stat-card-icon">📊</div>
                    <div class="stat-card-title">إجمالي التقييمات</div>
                    <div class="stat-card-value">{{ $totalReviews }}</div>
                </a>
                <a href="{{ route('admin.reviews.index', ['status' => 'pending']) }}" class="stat-card pending text-decoration-none">
                    <div class="stat-card-icon">⏳</div>
                    <div class="stat-card-title">في الانتظار</div>
                    <div class="stat-card-value">{{ $statusCounts->get('pending', 0) }}</div>
                </a>
                <a href="{{ route('admin.reviews.index', ['status' => 'approved']) }}" class="stat-card approved text-decoration-none">
                    <div class="stat-card-icon">✅</div>
                    <div class="stat-card-title">مقبولة</div>
                    <div class="stat-card-value">{{ $statusCounts->get('approved', 0) }}</div>
                </a>
                <a href="{{ route('admin.reviews.index', ['status' => 'rejected']) }}" class="stat-card rejected text-decoration-none">
                    <div class="stat-card-icon">❌</div>
                    <div class="stat-card-title">مرفوضة</div>
                    <div class="stat-card-value">{{ $statusCounts->get('rejected', 0) }}</div>
                </a>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <form method="GET" action="{{ route('admin.reviews.index') }}">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">بحث</label>
                            <input type="text" name="search" class="form-control" placeholder="ابحث باسم المستخدم أو عنوان الكتاب..." value="{{ request('search') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">الحالة</label>
                            <select name="status" class="form-select">
                                <option value="">الكل</option>
                                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>في الانتظار</option>
                                <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>مقبولة</option>
                                <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>مرفوضة</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-apply-filters w-100">
                                <i class="fas fa-search me-1"></i> بحث
                            </button>
                        </div>
                        @if(request()->hasAny(['search', 'status']))
                        <div class="col-md-2">
                            <a href="{{ route('admin.reviews.index') }}" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-times me-1"></i> مسح
                            </a>
                        </div>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Toast -->
            <div class="toast-notification" id="toastNotification"></div>

            <!-- Reviews Table -->
            <div class="table-section">
                @if($reviews->count() > 0)
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>المستخدم</th>
                                <th>الكتاب</th>
                                <th>التقييم</th>
                                <th>التعليق</th>
                                <th>التاريخ</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($reviews as $review)
                            <tr id="review-row-{{ $review->id }}">
                                <td>{{ $review->id }}</td>
                                <td>
                                    <strong>{{ $review->user->name ?? 'محذوف' }}</strong>
                                </td>
                                <td>
                                    <a href="{{ route('moredetail2.page', $review->book_id) }}" target="_blank" class="text-decoration-none text-dark">
                                        {{ \Illuminate\Support\Str::limit($review->book->title ?? 'محذوف', 30) }}
                                    </a>
                                </td>
                                <td>
                                    <div class="review-stars">
                                        @for($i = 1; $i <= 5; $i++)
                                            <i class="{{ $i <= $review->rating ? 'fas' : 'far' }} fa-star"></i>
                                        @endfor
                                    </div>
                                </td>
                                <td>
                                    <div class="review-comment" title="{{ $review->comment }}">{{ $review->comment }}</div>
                                </td>
                                <td>{{ $review->created_at->format('Y/m/d') }}</td>
                                <td>
                                    <span class="status-badge {{ $review->status }}" id="status-badge-{{ $review->id }}">
                                        @if($review->status === 'approved') مقبول
                                        @elseif($review->status === 'pending') في الانتظار
                                        @else مرفوض @endif
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        @if($review->status !== 'approved')
                                        <button class="btn-action btn-approve" onclick="updateStatus({{ $review->id }}, 'approved')" title="قبول">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        @endif
                                        @if($review->status !== 'rejected')
                                        <button class="btn-action btn-reject" onclick="updateStatus({{ $review->id }}, 'rejected')" title="رفض">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                        @endif
                                        <button class="btn-action btn-delete" onclick="deleteReview({{ $review->id }})" title="حذف">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                        <button class="btn-action" style="background:#e8f4fd;color:#2C4B79;" onclick="viewReview({{ $review->id }})" title="عرض">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-center py-3">
                    {{ $reviews->links() }}
                </div>
                @else
                <div class="empty-state">
                    <i class="fas fa-star"></i>
                    <h5>لا توجد تقييمات</h5>
                    <p>لم يتم العثور على تقييمات تطابق معايير البحث</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Review Detail Modal -->
    <div class="modal fade review-detail-modal" id="reviewModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border-radius:15px;overflow:hidden;">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-star me-2"></i> تفاصيل التقييم</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="reviewModalBody">
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showToast(message, type) {
            const toast = document.getElementById('toastNotification');
            toast.textContent = message;
            toast.className = 'toast-notification ' + type;
            toast.style.display = 'block';
            setTimeout(() => { toast.style.display = 'none'; }, 3000);
        }

        function updateStatus(id, status) {
            fetch(`{{ url('admin/reviews') }}/${id}/status`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ status: status })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    // Reload to reflect changes in stats and button visibility
                    setTimeout(() => location.reload(), 800);
                } else {
                    showToast('حدث خطأ', 'error');
                }
            })
            .catch(() => showToast('حدث خطأ في الاتصال', 'error'));
        }

        function deleteReview(id) {
            if (!confirm('هل أنت متأكد من حذف هذا التقييم نهائياً؟')) return;

            fetch(`{{ url('admin/reviews') }}/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message, 'success');
                    document.getElementById('review-row-' + id).remove();
                } else {
                    showToast('حدث خطأ', 'error');
                }
            })
            .catch(() => showToast('حدث خطأ في الاتصال', 'error'));
        }

        function viewReview(id) {
            const row = document.getElementById('review-row-' + id);
            const user = row.querySelector('td:nth-child(2) strong').textContent;
            const book = row.querySelector('td:nth-child(3) a').textContent;
            const stars = row.querySelector('td:nth-child(4)').innerHTML;
            const comment = row.querySelector('td:nth-child(5) .review-comment').getAttribute('title');
            const date = row.querySelector('td:nth-child(6)').textContent;
            const status = row.querySelector('td:nth-child(7) .status-badge').textContent.trim();

            document.getElementById('reviewModalBody').innerHTML = `
                <div class="mb-3"><strong>المستخدم:</strong> ${user}</div>
                <div class="mb-3"><strong>الكتاب:</strong> ${book}</div>
                <div class="mb-3"><strong>التقييم:</strong> ${stars}</div>
                <div class="mb-3"><strong>التاريخ:</strong> ${date}</div>
                <div class="mb-3"><strong>الحالة:</strong> ${status}</div>
                <div class="mb-3"><strong>التعليق:</strong></div>
                <div class="p-3 bg-light rounded">${comment}</div>
            `;

            new bootstrap.Modal(document.getElementById('reviewModal')).show();
        }
    </script>
</body>
</html>
