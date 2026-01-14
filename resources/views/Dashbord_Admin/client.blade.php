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
    @include('Dashbord_Admin.dashbordHeader')

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
    <div class="modal fade" id="editClientModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-edit me-2"></i>تعديل بيانات الزبون
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
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
        </div>
    </div>
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Search functionality
        document.getElementById('searchClientInput').addEventListener('input', function() {
            const query = this.value.toLowerCase();
            const rows = document.querySelectorAll('#clientsTable tbody tr');
            
            rows.forEach(row => {
                if (row.innerHTML.includes('لا توجد زبائن')) return;
                
                const name = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                const email = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                const phone = row.querySelector('td:nth-child(4)').textContent.toLowerCase();
                
                const matches = name.includes(query) || email.includes(query) || phone.includes(query);
                row.style.display = matches ? '' : 'none';
            });
        });

        // Sort functionality
        document.getElementById('sortBy').addEventListener('change', function() {
            const value = this.value;
            // Add sorting logic here
            alert('سيتم تفعيل الترتيب قريباً: ' + value);
        });

        // Reset filters
        function resetFilters() {
            document.getElementById('searchClientInput').value = '';
            document.getElementById('sortBy').value = 'latest';
            document.querySelectorAll('#clientsTable tbody tr').forEach(row => {
                row.style.display = '';
            });
        }

        // View client
       function viewClient(clientId) {
        const clientDetailsContent = document.getElementById('clientDetailsContent');
        const modal = new bootstrap.Modal(document.getElementById('clientDetailsModal'));

        // Show loading state
        clientDetailsContent.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">جاري التحميل...</span>
                </div>
                <p class="text-muted mt-3">جاري تحميل بيانات الزبون...</p>
            </div>
        `;

        // Fetch client data
        fetch(`/admin/client/${clientId}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            // Status mapping
            const statusMap = {
                'pending': { text: 'قيد الانتظار', class: 'order-status-pending' },
                'processing': { text: 'قيد المعالجة', class: 'order-status-processing' },
                'shipped': { text: 'مشحون', class: 'order-status-shipped' },
                'delivered': { text: 'تم التسليم', class: 'order-status-delivered' },
                'cancelled': { text: 'ملغى', class: 'order-status-cancelled' },
                'Failed': { text: 'فشل', class: 'order-status-cancelled' },
                'Refunded': { text: 'مسترجع', class: 'order-status-cancelled' },
                'returned': { text: 'مرتجع', class: 'order-status-cancelled' }
            };

            // Client avatar initial
            const initials = data.name.split(' ').map(n => n[0]).join('').toUpperCase();

            // Build orders HTML
            let ordersHtml = '';
            if (data.orders && data.orders.length > 0) {
                ordersHtml = data.orders.map(order => {
                    const statusInfo = statusMap[order.status] || { text: order.status, class: 'order-status-pending' };
                    
                    // Build items HTML
                    let itemsHtml = '';
                    if (order.order_details && order.order_details.length > 0) {
                        itemsHtml = order.order_details.map(item => `
                            <div class="item-in-order">
                                <div>
                                    <div class="item-name">${escapeHtml(item.book?.title || 'منتج')}</div>
                                    <div class="item-quantity">الكمية: ${item.quantity}</div>
                                </div>
                                <div class="item-price">${parseFloat(item.price * item.quantity).toFixed(2)} ر.س</div>
                            </div>
                        `).join('');
                    }

                    return `
                        <div class="order-card">
                            <div class="order-header">
                                <span class="order-id">#${order.id}</span>
                                <span class="order-status-badge ${statusInfo.class}">${statusInfo.text}</span>
                            </div>
                            
                            <div class="order-details-row">
                                <div class="order-detail-item">
                                    <span class="order-detail-label">المبلغ الإجمالي</span>
                                    <span class="order-detail-value">${parseFloat(order.total_price).toFixed(2)} ر.س</span>
                                </div>
                                <div class="order-detail-item">
                                    <span class="order-detail-label">طريقة الدفع</span>
                                    <span class="order-detail-value">
                                        ${order.payment_method === 'cod' ? 'الدفع عند الاستلام' : 'بطاقة ائتمان'}
                                    </span>
                                </div>
                                <div class="order-detail-item">
                                    <span class="order-detail-label">تاريخ الطلب</span>
                                    <span class="order-detail-value">${new Date(order.created_at).toLocaleDateString('ar-SA')}</span>
                                </div>
                            </div>

                            ${itemsHtml ? `
                                <div class="order-items">
                                    <div class="order-items-title">المنتجات المطلوبة</div>
                                    ${itemsHtml}
                                </div>
                            ` : ''}
                        </div>
                    `;
                }).join('');
            } else {
                ordersHtml = `
                    <div class="empty-orders">
                        <i class="fas fa-shopping-bag"></i>
                        <p>لم يضع الزبون أي طلبات بعد</p>
                    </div>
                `;
            }

            // Build complete content
            const content = `
                <div class="client-info-section">
                    <div class="client-header">
                        <div class="client-large-avatar">${initials}</div>
                        <div class="client-basic-info">
                            <h3>${escapeHtml(data.name)}</h3>
                            <p>
                                <a href="mailto:${data.email}" class="text-decoration-none">
                                    <i class="fas fa-envelope me-2"></i>${data.email}
                                </a>
                            </p>
                            ${data.phone ? `
                                <p>
                                    <i class="fas fa-phone me-2"></i>
                                    <span style="direction: ltr; text-align: left;">${data.phone}</span>
                                </p>
                            ` : ''}
                        </div>
                    </div>

                    <div class="details-grid">
                        <div class="detail-box">
                            <span class="detail-label">رقم المستخدم</span>
                            <div class="detail-value">#${data.id}</div>
                        </div>
                        <div class="detail-box">
                            <span class="detail-label">تاريخ التسجيل</span>
                            <div class="detail-value">${new Date(data.created_at).toLocaleDateString('ar-SA')}</div>
                        </div>
                        <div class="detail-box">
                            <span class="detail-label">عدد الطلبات</span>
                            <div class="detail-value">${data.orders ? data.orders.length : 0}</div>
                        </div>
                        <div class="detail-box">
                            <span class="detail-label">إجمالي الإنفاق</span>
                            <div class="detail-value">
                                ${data.orders ? parseFloat(data.orders.reduce((sum, order) => sum + parseFloat(order.total_price || 0), 0)).toFixed(2) : '0.00'} ر.س
                            </div>
                        </div>
                    </div>
                </div>

                <div class="orders-section">
                    <h5 class="orders-title">
                        <i class="fas fa-shopping-bag"></i>
                        <span class="orders-count-badge">${data.orders ? data.orders.length : 0}</span>
                        الطلبات
                    </h5>
                    ${ordersHtml}
                </div>
            `;

            clientDetailsContent.innerHTML = content;
            modal.show();
        })
        .catch(error => {
            console.error('Error:', error);
            clientDetailsContent.innerHTML = `
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <strong>خطأ!</strong> حدث خطأ في تحميل بيانات الزبون. يرجى المحاولة مرة أخرى.
                </div>
            `;
            modal.show();
        });
    }

    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

        function editClient(clientId) {
        const editModal = new bootstrap.Modal(document.getElementById('editClientModal'));
        
        // Show loading state
        document.getElementById('editClientForm').style.opacity = '0.5';
        document.querySelector('#editClientModal .btn-primary').disabled = true;

        // Fetch client data
        fetch(`/admin/client/${clientId}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            // Populate form with client data
            document.getElementById('editClientId').value = data.id;
            document.getElementById('editClientName').value = data.name || '';
            document.getElementById('editClientEmail').value = data.email || '';
            document.getElementById('editClientPhone').value = data.phone || '';

            // Reset form opacity and button state
            document.getElementById('editClientForm').style.opacity = '1';
            document.querySelector('#editClientModal .btn-primary').disabled = false;

            // Show modal
            editModal.show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('خطأ في تحميل بيانات الزبون. يرجى المحاولة مرة أخرى.');
            document.getElementById('editClientForm').style.opacity = '1';
            document.querySelector('#editClientModal .btn-primary').disabled = false;
        });
    }

    /**
     * Handle Edit Client Form Submission
     */
    document.getElementById('editClientForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const clientId = document.getElementById('editClientId').value;
        const name = document.getElementById('editClientName').value;
        const email = document.getElementById('editClientEmail').value;
        const phone = document.getElementById('editClientPhone').value;

        // Get submit button
        const submitBtn = document.querySelector('#editClientModal .btn-primary');
        submitBtn.disabled = true;
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>جاري الحفظ...';

        // Send update request
        fetch(`/admin/client/${clientId}`, {
            method: 'PUT',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                name: name,
                email: email,
                phone: phone
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                // Show success message
                showAlert('تم تحديث بيانات الزبون بنجاح!', 'success');
                
                // Close modal after 1.5 seconds
                setTimeout(() => {
                    bootstrap.Modal.getInstance(document.getElementById('editClientModal')).hide();
                    // Reload page to show updated data
                    location.reload();
                }, 1500);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('خطأ في حفظ البيانات. يرجى المحاولة مرة أخرى.', 'danger');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        });
    });

    /**
     * Show Alert Message
     */
    function showAlert(message, type) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert edit-alert edit-alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        const modalBody = document.querySelector('#editClientModal .modal-body');
        modalBody.insertBefore(alertDiv, modalBody.firstChild);

        // Auto-remove alert after 5 seconds
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }

        // Delete client
        function deleteClient(id) {
            if (confirm('هل أنت متأكد من حذف هذا الزبون؟')) {
                alert('حذف الزبون #' + id);
                // Add delete logic here
            }
        }
    </script>
</body>
</html>