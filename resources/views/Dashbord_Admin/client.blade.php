<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>إدارة الزبائن</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
    
    <link rel="stylesheet" href="{{ asset('css/sidebardaschboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/clients.css') }}">
    
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    

    <div class="container-fluid">
        <div class="row">
            @include('Dashbord_Admin.Sidebar')

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                
                <!-- Page Header -->
                <div class="page-header">
                    <h1>
                        <i class="fas fa-users"></i>
                        إدارة الزبائن
                    </h1>
                    <button class="btn-add" data-bs-toggle="modal" data-bs-target="#addClientModal">
                        <i class="fas fa-plus me-2"></i>إضافة زبون جديد
                    </button>
                </div>

                <!-- Stats Cards -->
                <div class="stats-row">
                    <div class="stat-card">
                        <div class="stat-label">إجمالي الزبائن</div>
                        <div class="stat-value">{{ $totalClients ?? 0 }}</div>
                    </div>
                    <div class="stat-card" style="border-left-color: #27ae60;">
                        <div class="stat-label">الزبائن الجدد (هذا الشهر)</div>
                        <div class="stat-value">{{ $newClientsThisMonth ?? 0 }}</div>
                    </div>
                    <div class="stat-card" style="border-left-color: #f39c12;">
                        <div class="stat-label">الزبائن النشطين</div>
                        <div class="stat-value">{{ $activeClients ?? 0 }}</div>
                    </div>
                </div>

                <!-- Search and Filter Section -->
                <div class="search-section">
                    <div class="search-controls">
                        <div class="form-group">
                            <label for="searchClientInput">بحث</label>
                            <input 
                                type="text" 
                                id="searchClientInput" 
                                class="form-control" 
                                placeholder="ابحث عن اسم أو بريد إلكتروني..."
                            >
                        </div>
                        <div class="form-group">
                            <label for="sortBy">ترتيب حسب</label>
                            <select id="sortBy" class="form-select">
                                <option value="latest">الأحدث أولاً</option>
                                <option value="oldest">الأقدم أولاً</option>
                                <option value="name">الاسم (أ-ي)</option>
                            </select>
                        </div>
                        <button class="btn-add" onclick="resetFilters()" style="background: #95a5a6;">
                            <i class="fas fa-redo me-2"></i>إعادة تعيين
                        </button>
                    </div>
                </div>

                <!-- Alerts -->
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                @if (session('success'))
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        {{ session('success') }}
                    </div>
                @endif

                <!-- Table Section -->
                <div class="table-section">
                    <div class="table-responsive">
                        <table class="table table-hover" id="clientsTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>الزبون</th>
                                    <th>البريد الإلكتروني</th>
                                    <th>رقم الهاتف</th>
                                    <th>تاريخ التسجيل</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($clients ?? [] as $client)
                                <tr data-client-id="{{ $client->id }}">
                                    <td>#{{ $client->id }}</td>
                                    <td>
                                        <div class="client-name">
                                            <div class="client-avatar">
                                                {{ substr($client->name, 0, 1) }}
                                            </div>
                                            {{ $client->name }}
                                        </div>
                                    </td>
                                    <td>
                                        <a href="mailto:{{ $client->email }}" class="client-email">
                                            {{ $client->email }}
                                        </a>
                                    </td>
                                    <td>
                                        <span class="client-phone">{{ $client->phone ?? '-' }}</span>
                                    </td>
                                    <td>
                                        <span class="registration-date">
                                            {{ $client->created_at->format('d-m-Y') }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button 
                                              class="btn-action btn-view" 
                                              onclick="viewClient({{ $client->id }})"
                                              data-bs-toggle="modal" 
                                              data-bs-target="#clientDetailsModal"
                                              title="عرض التفاصيل"
                                          >
                                              <i class="fas fa-eye"></i>
                                          </button>
                                            <button 
                                                class="btn-action btn-edit" 
                                                onclick="editClient({{ $client->id }})"
                                                title="تعديل"
                                            >
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button 
                                                class="btn-action btn-delete" 
                                                onclick="deleteClient({{ $client->id }})"
                                                title="حذف"
                                            >
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6">
                                        <div class="empty-state">
                                            <i class="fas fa-inbox"></i>
                                            <p>لا توجد زبائن</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                @if($clients instanceof \Illuminate\Pagination\Paginator || $clients instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <nav>
                    {{ $clients->links('pagination::bootstrap-5') }}
                </nav>
                @endif
            </main>
        </div>
    </div>

    <!-- Add/Edit Client Modal -->
    <div class="modal fade" id="addClientModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-plus me-2"></i>إضافة زبون جديد
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="clientForm" method="POST" action="{{ route('client.store') }}">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">اسم الزبون</label>
                            <input type="text" class="form-control" name="name" id="clientName" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">البريد الإلكتروني</label>
                            <input type="email" class="form-control" name="email" id="clientEmail" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">رقم الهاتف</label>
                            <input type="text" class="form-control" name="phone" id="clientPhone">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>حفظ
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Client Details Modal -->
    <div class="modal fade" id="clientDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-circle me-2"></i>تفاصيل الزبون
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="clientDetailsContent">
                    <div class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">جاري التحميل...</span>
                        </div>
                        <p class="text-muted mt-3">جاري تحميل البيانات...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Edit Client Modal -->
    <!-- Edit Client Modal -->
<div class="modal fade" id="editClientModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-edit me-2"></i>تعديل بيانات الزبون
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <!-- Tabs -->
            <ul class="nav nav-tabs" id="editTabs" role="tablist" style="padding: 1rem 1.5rem 0; border-bottom: 2px solid #e9ecef;">
                <li class="nav-item" role="presentation">
                    <button 
                        class="nav-link active" 
                        id="general-tab" 
                        data-bs-toggle="tab" 
                        data-bs-target="#general" 
                        type="button" 
                        role="tab"
                    >
                        <i class="fas fa-user me-2"></i>معلومات عامة
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button 
                        class="nav-link" 
                        id="password-tab" 
                        data-bs-toggle="tab" 
                        data-bs-target="#password" 
                        type="button" 
                        role="tab"
                    >
                        <i class="fas fa-lock me-2"></i>كلمة المرور
                    </button>
                </li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="editTabContent">
                <!-- General Info Tab -->
                <div class="tab-pane fade show active" id="general" role="tabpanel">
                    <form id="editClientForm">
                        @csrf
                        @method('PUT')
                        <div class="modal-body">
                            <input type="hidden" id="editClientId">
                            
                            <div class="mb-3">
                                <label class="form-label">اسم الزبون</label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="editClientName" 
                                    name="name" 
                                    required
                                >
                            </div>

                            <div class="mb-3">
                                <label class="form-label">البريد الإلكتروني</label>
                                <input 
                                    type="email" 
                                    class="form-control" 
                                    id="editClientEmail" 
                                    name="email" 
                                    required
                                >
                            </div>

                            <div class="mb-3">
                                <label class="form-label">رقم الهاتف</label>
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="editClientPhone" 
                                    name="phone"
                                >
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>حفظ التغييرات
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Password Tab -->
                <div class="tab-pane fade" id="password" role="tabpanel">
                    <form id="resetPasswordForm">
                        @csrf
                        <div class="modal-body">
                            <input type="hidden" id="passwordClientId">

                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                اختر إحدى الطريقتين لتعيين كلمة مرور جديدة للزبون
                            </div>

                            <!-- Method 1: Auto Generate -->
                            <div class="password-method">
                                <div class="form-check">
                                    <input 
                                        class="form-check-input" 
                                        type="radio" 
                                        name="passwordMethod" 
                                        id="autoGenerate" 
                                        value="auto" 
                                        checked
                                    >
                                    <label class="form-check-label" for="autoGenerate">
                                        <strong>توليد كلمة مرور عشوائية تلقائياً</strong>
                                    </label>
                                </div>
                                <p class="text-muted ms-4 mt-2">سيتم توليد كلمة مرور عشوائية آمنة وإرسالها للزبون عبر البريد الإلكتروني</p>
                            </div>

                            <hr>

                            <!-- Method 2: Manual -->
                            <div class="password-method">
                                <div class="form-check">
                                    <input 
                                        class="form-check-input" 
                                        type="radio" 
                                        name="passwordMethod" 
                                        id="manualPassword" 
                                        value="manual"
                                    >
                                    <label class="form-check-label" for="manualPassword">
                                        <strong>إدخال كلمة مرور يدوياً</strong>
                                    </label>
                                </div>

                                <div class="mt-3" id="manualPasswordFields" style="display: none; margin-right: 1.5rem;">
                                    <div class="mb-3">
                                        <label class="form-label">كلمة المرور الجديدة</label>
                                        <div class="input-group">
                                            <input 
                                                type="password" 
                                                class="form-control" 
                                                id="newPassword" 
                                                name="password"
                                                minlength="8"
                                            >
                                            <button 
                                                class="btn btn-outline-secondary" 
                                                type="button" 
                                                onclick="togglePasswordVisibility('newPassword')"
                                            >
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        <small class="text-muted">يجب أن تكون 8 أحرف على الأقل</small>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label">تأكيد كلمة المرور</label>
                                        <div class="input-group">
                                            <input 
                                                type="password" 
                                                class="form-control" 
                                                id="confirmPassword" 
                                                name="password_confirmation"
                                                minlength="8"
                                            >
                                            <button 
                                                class="btn btn-outline-secondary" 
                                                type="button" 
                                                onclick="togglePasswordVisibility('confirmPassword')"
                                            >
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div id="passwordStrength" style="display: none;">
                                        <label class="form-label">قوة كلمة المرور</label>
                                        <div class="progress">
                                            <div id="strengthBar" class="progress-bar" style="width: 0%"></div>
                                        </div>
                                        <small id="strengthText" class="text-muted"></small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-key me-2"></i>تعيين كلمة المرور
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('js/dashboardclient.js') }}"></script> 
</body>
</html>