<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>كتب قيد المراجعة</title>
    <link rel="stylesheet" href="{{ asset('css/variables.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/sidebardaschboard.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .status-pill { padding: 3px 10px; border-radius: 999px; font-size: .75rem; font-weight: 600; }
        .status-enriched  { background:#d1ecf1; color:#0c5460; }
        .status-failed    { background:#f8d7da; color:#721c24; }
        .status-duplicate { background:#fff3cd; color:#856404; }
        .status-approved  { background:#d4edda; color:#155724; }
        .status-discarded { background:#e2e3e5; color:#6c757d; }
        .stat-pill   { display: inline-block; padding: .35rem .8rem; border-radius: .5rem; background:#f8f9fa; margin-inline-end: .5rem; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        @include('Dashbord_Admin.Sidebar')

        <main class="col main-content">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show mt-3">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger mt-3">{{ session('error') }}</div>
            @endif

            <div class="d-flex justify-content-between align-items-center my-4">
                <h1 class="fs-4 fw-bold mb-0">
                    <i class="fas fa-inbox me-2 text-primary"></i>كتب قيد المراجعة
                </h1>
                <a href="{{ route('admin.books.ingest.create') }}" class="btn btn-primary btn-sm">
                    <i class="fas fa-plus me-1"></i> إضافة كتاب جديد
                </a>
            </div>

            <div class="mb-3">
                <span class="stat-pill">المجموع: {{ $pendingBooks->total() }}</span>
                @foreach(['enriched' => 'تم الإثراء', 'failed' => 'فشل', 'duplicate' => 'مكرر', 'approved' => 'معتمد', 'discarded' => 'مهمل'] as $key => $label)
                    @if(($counts[$key] ?? 0) > 0)
                        <a href="{{ route('admin.books.pending.index', ['status' => $key]) }}" class="stat-pill text-decoration-none">
                            {{ $label }}: <strong>{{ $counts[$key] }}</strong>
                        </a>
                    @endif
                @endforeach
                @if($statusFilter)
                    <a href="{{ route('admin.books.pending.index') }}" class="stat-pill text-decoration-none">
                        <i class="fas fa-times"></i> إلغاء الفلتر
                    </a>
                @endif
            </div>

            <div class="card">
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>العنوان</th>
                                <th>المؤلف</th>
                                <th>اللغة</th>
                                <th>المصادر</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($pendingBooks as $row)
                                <tr>
                                    <td>{{ $row->id }}</td>
                                    <td>{{ $row->title }}</td>
                                    <td>{{ $row->author_name }}</td>
                                    <td>{{ \App\Models\Book::LANGUAGE_LABELS[$row->language] ?? $row->language }}</td>
                                    <td>
                                        @php $srcs = $row->availableSources(); @endphp
                                        {{ !empty($srcs) ? implode(', ', $srcs) : '—' }}
                                    </td>
                                    <td>
                                        <span class="status-pill status-{{ $row->status }}">
                                            {{ ['enriched' => 'تم الإثراء', 'failed' => 'فشل', 'duplicate' => 'مكرر', 'approved' => 'معتمد', 'discarded' => 'مهمل'][$row->status] ?? $row->status }}
                                        </span>
                                        @if($row->status === 'duplicate' && $row->existingBook)
                                            <small class="text-muted d-block">→ كتاب موجود #{{ $row->existing_book_id }}</small>
                                        @endif
                                        @if($row->status === 'approved' && $row->approvedBook)
                                            <small class="text-muted d-block">→ كتاب #{{ $row->approved_book_id }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        @if($row->isReviewable())
                                            <a href="{{ route('admin.books.pending.show', $row->id) }}" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i> مراجعة
                                            </a>
                                        @else
                                            <a href="{{ route('admin.books.pending.show', $row->id) }}" class="btn btn-sm btn-outline-secondary">
                                                <i class="fas fa-eye"></i> عرض
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="text-center text-muted p-4">لا توجد عناصر.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($pendingBooks->hasPages())
                <div class="mt-3">{{ $pendingBooks->links() }}</div>
            @endif
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
