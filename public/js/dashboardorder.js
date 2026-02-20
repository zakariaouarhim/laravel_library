
        function viewOrder(orderId) {
            const modalContent = document.getElementById('modalContent');
            modalContent.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"></div></div>';

            fetch(`/admin/orders/${orderId}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                let statusMap = {
                    'pending': 'قيد الانتظار',
                    'processing': 'قيد المعالجة',
                    'shipped': 'مشحون',
                    'delivered': 'تم التسليم',
                    'cancelled': 'ملغى',
                    'Failed': 'فشل',
                    'Refunded': 'مسترجع',
                    'returned': 'مرتجع'
                };

                let booksHtml = data.order_details.map(item => `
                    <div class="book-item">
                        <div class="book-info">
                            <div class="book-title">${item.book.title}</div>
                            <div class="book-quantity">الكمية: ${item.quantity}</div>
                        </div>
                        <div class="book-price">${parseFloat(item.price * item.quantity).toFixed(2)} د.م</div>
                    </div>
                `).join('');

                let content = `
                    <div class="detail-row">
                        <div>
                            <div class="detail-label">رقم الطلب</div>
                            <div class="detail-value">#${data.id}</div>
                        </div>
                        <div>
                            <div class="detail-label">رقم التتبع</div>
                            <div class="detail-value">${data.tracking_number || '-'}</div>
                        </div>
                    </div>

                    <div class="detail-row">
                        <div>
                            <div class="detail-label">المبلغ الإجمالي</div>
                            <div class="detail-value">${parseFloat(data.total_price).toFixed(2)} د.م</div>
                        </div>
                        <div>
                            <div class="detail-label">طريقة الدفع</div>
                            <div class="detail-value">${data.payment_method === 'cod' ? 'الدفع عند الاستلام' : 'بطاقة ائتمان'}</div>
                        </div>
                    </div>

                    <div class="detail-row">
                        <div>
                            <div class="detail-label">حالة الطلب</div>
                            <div class="detail-value">${statusMap[data.status] || data.status}</div>
                        </div>
                        <div>
                            <div class="detail-label">تاريخ الطلب</div>
                            <div class="detail-value">${new Date(data.created_at).toLocaleDateString('ar-SA')}</div>
                        </div>
                    </div>

                    <div class="detail-row">
                        <div>
                            <div class="detail-label">عنوان الشحن</div>
                            <div class="detail-value">${data.shipping_address || '-'}</div>
                        </div>
                        <div>
                            <div class="detail-label">عنوان الفاتورة</div>
                            <div class="detail-value">${data.billing_address || '-'}</div>
                        </div>
                    </div>

                    <div>
                        <div class="detail-label mb-3">الكتب المطلوبة</div>
                        <div class="books-list">
                            ${booksHtml}
                        </div>
                    </div>

                    <div class="status-update-section">
                        <h6>تحديث حالة الطلب</h6>
                        <select id="newStatus" class="form-select mb-2">
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
                modalContent.innerHTML = '<div class="alert alert-danger">خطأ في تحميل البيانات</div>';
            });
        }

        function updateOrderStatus(orderId) {
            const newStatus = document.getElementById('newStatus').value;
            
            if (!newStatus) {
                alert('اختر حالة جديدة');
                return;
            }

            fetch(`/admin/orders/${orderId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ status: newStatus })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('تم تحديث حالة الطلب بنجاح');
                    location.reload();
                }
            })
            .catch(error => console.error('Error:', error));
        }

        function editOrder(orderId) {
            window.location.href = `/admin/orders/${orderId}/edit`;
        }

        function applyFilters() {
            const search = document.getElementById('searchInput').value.trim();
            const status = document.getElementById('statusFilter').value;

            const url = new URL(window.location.href);

            // Handle search filter
            if (search) {
                url.searchParams.set('search', search);
            } else {
                url.searchParams.delete('search');
            }

            // Handle status filter
            if (status) {
                url.searchParams.set('status', status);
            } else {
                url.searchParams.delete('status');
            }

            window.location.href = url.toString();
        }
    