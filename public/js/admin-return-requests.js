function viewReturnRequest(id) {
    const modalContent = document.getElementById('modalContent');
    modalContent.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div></div>';

    fetch(`/admin/return-requests/${id}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        const statusMap = {
            'pending': 'قيد المراجعة',
            'approved': 'مقبول',
            'rejected': 'مرفوض',
            'refunded': 'تم الاسترداد'
        };

        const customerName = (data.order && data.order.user) ? escapeHtml(data.order.user.name) : '—';
        const customerEmail = (data.order && data.order.user) ? escapeHtml(data.order.user.email) : '—';
        const paymentLabel = data.payment_method === 'cod' ? 'الدفع عند الاستلام' : 'بطاقة ائتمان';

        let booksHtml = '';
        if (data.order && data.order.order_details) {
            booksHtml = data.order.order_details.map(item => `
                <div class="book-item">
                    <div class="book-info">
                        <div class="book-title">${item.book ? escapeHtml(item.book.title) : 'كتاب محذوف'}</div>
                        <div class="book-quantity">الكمية: ${item.quantity}</div>
                    </div>
                    <div class="book-price">${parseFloat(item.price * item.quantity).toFixed(2)} د.م</div>
                </div>
            `).join('');
        }

        const resolvedHtml = data.resolved_at
            ? `<div class="detail-row">
                    <div>
                        <div class="detail-label">تاريخ المعالجة</div>
                        <div class="detail-value">${new Date(data.resolved_at).toLocaleDateString('ar-SA')}</div>
                    </div>
                    <div></div>
               </div>`
            : '';

        const content = `
            <div class="detail-row">
                <div>
                    <div class="detail-label">رقم طلب الإسترجاع</div>
                    <div class="detail-value">#${data.id}</div>
                </div>
                <div>
                    <div class="detail-label">رقم الطلب الأصلي</div>
                    <div class="detail-value">#${data.order_id}</div>
                </div>
            </div>

            <div class="detail-row">
                <div>
                    <div class="detail-label">العميل</div>
                    <div class="detail-value">${customerName}</div>
                </div>
                <div>
                    <div class="detail-label">البريد الإلكتروني</div>
                    <div class="detail-value">${customerEmail}</div>
                </div>
            </div>

            <div class="detail-row">
                <div>
                    <div class="detail-label">طريقة الدفع</div>
                    <div class="detail-value">${paymentLabel}</div>
                </div>
                <div>
                    <div class="detail-label">مبلغ الاسترداد</div>
                    <div class="detail-value">${parseFloat(data.refund_amount).toFixed(2)} د.م</div>
                </div>
            </div>

            <div class="detail-row">
                <div>
                    <div class="detail-label">الحالة</div>
                    <div class="detail-value">${statusMap[data.status] || data.status}</div>
                </div>
                <div>
                    <div class="detail-label">تاريخ الطلب</div>
                    <div class="detail-value">${new Date(data.created_at).toLocaleDateString('ar-SA')}</div>
                </div>
            </div>

            ${resolvedHtml}

            <div>
                <div class="detail-label mb-2">سبب الإرجاع</div>
                <div class="reason-box">${escapeHtml(data.reason)}</div>
            </div>

            ${data.admin_notes ? `
                <div>
                    <div class="detail-label mb-2">ملاحظات الإدارة الحالية</div>
                    <div class="admin-notes-box">${escapeHtml(data.admin_notes)}</div>
                </div>
            ` : ''}

            ${booksHtml ? `
                <div>
                    <div class="detail-label mb-3">الكتب في الطلب</div>
                    <div class="books-list">${booksHtml}</div>
                </div>
            ` : ''}

            <div class="status-update-section">
                <h6>تحديث طلب الإسترجاع</h6>
                <div class="mb-3">
                    <label class="form-label fw-bold">الحالة</label>
                    <select id="newStatus" class="form-select">
                        <option value="pending" ${data.status === 'pending' ? 'selected' : ''}>قيد المراجعة</option>
                        <option value="approved" ${data.status === 'approved' ? 'selected' : ''}>مقبول</option>
                        <option value="rejected" ${data.status === 'rejected' ? 'selected' : ''}>مرفوض</option>
                        <option value="refunded" ${data.status === 'refunded' ? 'selected' : ''}>تم الاسترداد</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">ملاحظات الإدارة</label>
                    <textarea id="adminNotes" class="form-control" rows="3" placeholder="أضف ملاحظاتك هنا...">${data.admin_notes ? escapeHtml(data.admin_notes) : ''}</textarea>
                </div>
                <button class="btn-update-status" onclick="updateReturnRequest(${data.id})">
                    <i class="fas fa-save me-2"></i>حفظ التغييرات
                </button>
            </div>
        `;

        modalContent.innerHTML = content;
    })
    .catch(error => {
        console.error('Error:', error);
        modalContent.innerHTML = '<div class="alert alert-danger">خطأ في تحميل البيانات</div>';
    });
}

function updateReturnRequest(id) {
    const newStatus = document.getElementById('newStatus').value;
    const adminNotes = document.getElementById('adminNotes').value;

    if (!newStatus) {
        alert('اختر حالة جديدة');
        return;
    }

    fetch(`/admin/return-requests/${id}`, {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            status: newStatus,
            admin_notes: adminNotes
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        }
    })
    .catch(error => console.error('Error:', error));
}

function applyFilters() {
    const search = document.getElementById('searchInput').value.trim();
    const status = document.getElementById('statusFilter').value;

    const url = new URL(window.location.href);

    if (search) {
        url.searchParams.set('search', search);
    } else {
        url.searchParams.delete('search');
    }

    if (status) {
        url.searchParams.set('status', status);
    } else {
        url.searchParams.delete('status');
    }

    window.location.href = url.toString();
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.appendChild(document.createTextNode(text));
    return div.innerHTML;
}
