<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>الأسئلة الشائعة</title>

    <link rel="stylesheet" href="{{ asset('css/variables.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.rtl.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">

    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sidebardaschboard.css') }}">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        .faq-table { background: #fff; border-radius: 14px; padding: 1rem; box-shadow: 0 2px 12px rgba(0,0,0,0.06); }
        .faq-table table { width: 100%; }
        .faq-table th, .faq-table td {
            padding: .75rem; border-bottom: 1px solid var(--color-border-light, #f0f0f0);
            vertical-align: top;
        }
        .faq-q { font-weight: 700; color: var(--color-primary-dark, #203a61); }
        .faq-a { color: var(--color-text-light, #666); font-size: .9rem; }
        .faq-actions { white-space: nowrap; text-align: center; }
        .faq-btn-add { margin-bottom: 1rem; }
        .badge-inactive { background: var(--color-text-muted, #888); }
    </style>
</head>
<body>

<div class="dashboard_layout">
    <div class="container-fluid">
        <div class="row">
            @include('Dashbord_Admin.Sidebar')

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="dashboard-header">
                    <h1><i class="fas fa-question-circle"></i> الأسئلة الشائعة</h1>
                </div>

                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                @endif

                <button class="btn btn-primary faq-btn-add" data-bs-toggle="modal" data-bs-target="#createFaqModal">
                    <i class="fas fa-plus"></i> إضافة سؤال جديد
                </button>

                <div class="faq-table">
                    @if($faqs->isEmpty())
                        <div class="text-center py-4 text-muted">لا توجد أسئلة بعد. أضف أولى الأسئلة الشائعة لتظهر في صفحة «حول» وفي نتائج البحث.</div>
                    @else
                        <table>
                            <thead>
                                <tr>
                                    <th width="60">الترتيب</th>
                                    <th>السؤال والإجابة</th>
                                    <th width="100">الحالة</th>
                                    <th width="140">إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($faqs as $faq)
                                <tr>
                                    <td class="text-center"><strong>{{ $faq->display_order }}</strong></td>
                                    <td>
                                        <div class="faq-q">{{ $faq->question }}</div>
                                        <div class="faq-a">{{ \Illuminate\Support\Str::limit($faq->answer, 200) }}</div>
                                    </td>
                                    <td class="text-center">
                                        @if($faq->is_active)
                                            <span class="badge bg-success">مفعّل</span>
                                        @else
                                            <span class="badge badge-inactive">معطّل</span>
                                        @endif
                                    </td>
                                    <td class="faq-actions">
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editFaqModal{{ $faq->id }}">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" action="{{ route('admin.faqs.destroy', $faq) }}" style="display:inline" onsubmit="return confirm('حذف هذا السؤال نهائياً؟');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>

                                {{-- Edit modal per row --}}
                                <div class="modal fade" id="editFaqModal{{ $faq->id }}" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <form method="POST" action="{{ route('admin.faqs.update', $faq) }}">
                                                @csrf @method('PUT')
                                                <div class="modal-header">
                                                    <h5 class="modal-title">تعديل سؤال</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">السؤال <span class="text-danger">*</span></label>
                                                        <input type="text" name="question" class="form-control" maxlength="255" required value="{{ $faq->question }}">
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label">الإجابة <span class="text-danger">*</span></label>
                                                        <textarea name="answer" class="form-control" rows="6" maxlength="5000" required>{{ $faq->answer }}</textarea>
                                                        <div class="form-text">يدعم الفقرات (سطر فارغ = فقرة جديدة).</div>
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-md-6 mb-3">
                                                            <label class="form-label">الترتيب</label>
                                                            <input type="number" name="display_order" class="form-control" min="0" value="{{ $faq->display_order }}">
                                                        </div>
                                                        <div class="col-md-6 mb-3 d-flex align-items-end">
                                                            <div class="form-check">
                                                                <input type="checkbox" name="is_active" id="active{{ $faq->id }}" class="form-check-input" value="1" {{ $faq->is_active ? 'checked' : '' }}>
                                                                <label class="form-check-label" for="active{{ $faq->id }}">مفعّل (يظهر للزوار)</label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                                                    <button type="submit" class="btn btn-primary">حفظ</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </main>
        </div>
    </div>
</div>

{{-- Create modal --}}
<div class="modal fade" id="createFaqModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.faqs.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus"></i> إضافة سؤال جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">السؤال <span class="text-danger">*</span></label>
                        <input type="text" name="question" class="form-control" maxlength="255" required placeholder="مثال: كم يستغرق الشحن داخل المغرب؟">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">الإجابة <span class="text-danger">*</span></label>
                        <textarea name="answer" class="form-control" rows="6" maxlength="5000" required placeholder="يفصل بين الفقرات سطر فارغ"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">الترتيب</label>
                            <input type="number" name="display_order" class="form-control" min="0" placeholder="فارغ = تلقائي في النهاية">
                        </div>
                        <div class="col-md-6 mb-3 d-flex align-items-end">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" id="newActive" class="form-check-input" value="1" checked>
                                <label class="form-check-label" for="newActive">مفعّل (يظهر للزوار)</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">إضافة</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
