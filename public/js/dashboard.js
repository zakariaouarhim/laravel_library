/**
 * View Order Details in Modal
 */
function viewOrder(orderId) {
    const modalContent = document.getElementById('modalContent');
  const modalElement = document.getElementById('orderModal');
    const myModal = new bootstrap.Modal(modalElement);
    myModal.show();
    // Fetch order data
    fetch(`/admin/orders/${orderId}`, {
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
            'pending': 'قيد الانتظار',
            'processing': 'قيد المعالجة',
            'shipped': 'مشحون',
            'delivered': 'تم التسليم',
            'cancelled': 'ملغى',
            'Failed': 'فشل',
            'Refunded': 'مسترجع',
            'returned': 'مرتجع'
        };

        // Build books HTML
        let booksHtml = '';
        if (data.order_details && data.order_details.length > 0) {
            booksHtml = data.order_details.map(item => `
                <div class="book-item">
                    <div class="book-info">
                        <div class="book-title">${escapeHtml(item.book.title)}</div>
                        <div class="book-quantity">الكمية: ${item.quantity}</div>
                    </div>
                    <div class="book-price">${parseFloat(item.price * item.quantity).toFixed(2)} د.م</div>
                </div>
            `).join('');
        }

        // Format date
        const orderDate = new Date(data.created_at).toLocaleDateString('ar-SA', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });

        // Build content HTML
        const content = `
            <div class="detail-row">
                <div>
                    <span class="detail-label">رقم الطلب</span>
                    <div class="detail-value">#${data.id}</div>
                </div>
                <div>
                    <span class="detail-label">رقم التتبع</span>
                    <div class="detail-value">${data.tracking_number ? escapeHtml(data.tracking_number) : '<span class="text-muted">-</span>'}</div>
                </div>
            </div>

            <div class="detail-row">
                <div>
                    <span class="detail-label">المبلغ الإجمالي</span>
                    <div class="detail-value"><strong>${parseFloat(data.total_price).toFixed(2)} د.م</strong></div>
                </div>
                <div>
                    <span class="detail-label">طريقة الدفع</span>
                    <div class="detail-value">
                        ${data.payment_method === 'cod' ? '<span class="badge bg-warning text-dark">الدفع عند الاستلام</span>' : '<span class="badge bg-info">بطاقة ائتمان</span>'}
                    </div>
                </div>
            </div>

            <div class="detail-row">
                <div>
                    <span class="detail-label">حالة الطلب</span>
                    <div class="detail-value">${statusMap[data.status] || data.status}</div>
                </div>
                <div>
                    <span class="detail-label">تاريخ الطلب</span>
                    <div class="detail-value">${orderDate}</div>
                </div>
            </div>

            <div class="detail-row">
                <div>
                    <span class="detail-label">عنوان الشحن</span>
                    <div class="detail-value">${data.shipping_address ? escapeHtml(data.shipping_address) : '<span class="text-muted">-</span>'}</div>
                </div>
                <div>
                    <span class="detail-label">عنوان الفاتورة</span>
                    <div class="detail-value">${data.billing_address ? escapeHtml(data.billing_address) : '<span class="text-muted">-</span>'}</div>
                </div>
            </div>

            ${booksHtml ? `
                <div style="margin-bottom: 2rem;">
                    <span class="detail-label mb-3" style="display: block;">الكتب المطلوبة</span>
                    <div class="books-list">
                        ${booksHtml}
                    </div>
                </div>
            ` : ''}

            <div class="status-update-section">
                <h6>
                    <i class="fas fa-sync-alt"></i>
                    تحديث حالة الطلب
                </h6>
                <select id="newStatus" class="form-select mb-3">
                    <option value="">اختر حالة جديدة</option>
                    <option value="pending">قيد الانتظار</option>
                    <option value="processing">قيد المعالجة</option>
                    <option value="shipped">مشحون</option>
                    <option value="delivered">تم التسليم</option>
                    <option value="cancelled">ملغى</option>
                    <option value="Failed">فشل</option>
                    <option value="Refunded">مسترجع</option>
                    <option value="returned">مرتجع</option>
                </select>
                <button class="btn-update-status" onclick="updateOrderStatus(${data.id})">
                    <i class="fas fa-save me-2"></i>حفظ التغييرات
                </button>
            </div>
        `;

        modalContent.innerHTML = content;
    })
    .catch(error => {
        console.error('Error:', error);
        modalContent.innerHTML = `
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <strong>خطأ!</strong> حدث خطأ في تحميل بيانات الطلب. يرجى المحاولة مرة أخرى.
            </div>
        `;
    });
}

/**
 * Update Order Status
 */
function updateOrderStatus(orderId) {
    const newStatus = document.getElementById('newStatus').value;

    if (!newStatus) {
        alert('اختر حالة جديدة');
        return;
    }

    // Get button
    const button = document.querySelector('.btn-update-status');
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>جاري الحفظ...';

    fetch(`/admin/orders/${orderId}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
            'Accept': 'application/json'
        },
        body: JSON.stringify({ status: newStatus })
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
            const modalContent = document.getElementById('modalContent');
            const alertHtml = `
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>تم بنجاح!</strong> تم تحديث حالة الطلب بنجاح.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            modalContent.insertAdjacentHTML('afterbegin', alertHtml);
            
            // Reload page after 2 seconds
            setTimeout(() => {
                location.reload();
            }, 2000);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('خطأ في تحديث حالة الطلب');
        button.disabled = false;
        button.innerHTML = '<i class="fas fa-save me-2"></i>حفظ التغييرات';
    });
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Export Data
 */
function exportData() {
    alert('سيتم تصدير البيانات قريباً');
}