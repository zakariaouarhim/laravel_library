
        /**
         * View Product Details
         */
        function viewProduct(productId) {
            const viewModal = new bootstrap.Modal(document.getElementById('viewProductModal'));
            const viewContent = document.getElementById('viewProductContent');

            viewContent.innerHTML = `
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">جاري التحميل...</span>
                    </div>
                </div>
            `;

            fetch(`/admin/products/${productId}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) throw new Error('Failed to load product');
                return response.json();
            })
            .then(product => {
                const content = `
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                        <div>
                            ${product.image ? `
                                <img src="/${product.image}" alt="${product.title}" style="width: 100%; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                            ` : `
                                <div style="width: 100%; height: 300px; background: #e9ecef; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-book" style="font-size: 3rem; color: #bdc3c7;"></i>
                                </div>
                            `}
                        </div>
                        <div>
                            <h4 style="color: #2c3e50; font-weight: 700; margin-bottom: 1rem;">${product.title}</h4>
                            <div style="margin-bottom: 1rem;">
                                <label style="color: #667eea; font-weight: 600; font-size: 0.9rem;">المؤلف</label>
                                <p style="color: #2c3e50; margin: 0.5rem 0 0;">${product.author}</p>
                            </div>
                            <div style="margin-bottom: 1rem;">
                                <label style="color: #667eea; font-weight: 600; font-size: 0.9rem;">السعر</label>
                                <p style="color: #27ae60; font-weight: 700; font-size: 1.3rem; margin: 0.5rem 0 0;">${parseFloat(product.price).toFixed(2)} ر.س</p>
                            </div>
                            <div style="margin-bottom: 1rem;">
                                <label style="color: #667eea; font-weight: 600; font-size: 0.9rem;">عدد الصفحات</label>
                                <p style="color: #2c3e50; margin: 0.5rem 0 0;">${product.Page_Num || '-'}</p>
                            </div>
                            <div style="margin-bottom: 1rem;">
                                <label style="color: #667eea; font-weight: 600; font-size: 0.9rem;">اللغة</label>
                                <p style="color: #2c3e50; margin: 0.5rem 0 0;">${product.Langue || '-'}</p>
                            </div>
                            <div style="margin-bottom: 1rem;">
                                <label style="color: #667eea; font-weight: 600; font-size: 0.9rem;">ISBN</label>
                                <p style="color: #2c3e50; margin: 0.5rem 0 0;">${product.ISBN || '-'}</p>
                            </div>
                            <div style="margin-bottom: 1rem;">
                                <label style="color: #667eea; font-weight: 600; font-size: 0.9rem;">دار النشر</label>
                                <p style="color: #2c3e50; margin: 0.5rem 0 0;">${product.Publishing_House || '-'}</p>
                            </div>
                        </div>
                    </div>
                    <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e9ecef;">
                        <label style="color: #667eea; font-weight: 600; font-size: 0.9rem;">الوصف</label>
                        <p style="color: #2c3e50; line-height: 1.6; margin-top: 0.5rem;">${product.description}</p>
                    </div>
                `;
                viewContent.innerHTML = content;
            })
            .catch(error => {
                console.error('Error:', error);
                viewContent.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        خطأ في تحميل تفاصيل المنتج
                    </div>
                `;
            });

            viewModal.show();
        }

        /**
         * Edit Product - Load data
         */
        function editProduct(productId) {
            const editModal = new bootstrap.Modal(document.getElementById('editProductModal'));

            fetch(`/admin/products/${productId}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) throw new Error('Failed to load product');
                return response.json();
            })
            .then(product => {
                document.getElementById('productId').value = product.id;
                document.getElementById('editProductName').value = product.title;
                document.getElementById('editAuthor').value = product.author;
                document.getElementById('editProductDescription').value = product.description;
                document.getElementById('editProductPrice').value = product.price;
                document.getElementById('editProductPageNum').value = product.Page_Num;
                document.getElementById('editProductLanguage').value = product.Langue;
                document.getElementById('editProductISBN').value = product.ISBN;
                document.getElementById('editProductPublishingHouse').value = product.Publishing_House;
                document.getElementById('editProductQuantity').value = product.Quantity;

                if (product.image) {
                    const preview = document.getElementById('editProductImagePreview');
                    preview.src = `/${product.image}`;
                    preview.style.display = 'block';
                }

                editModal.show();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('خطأ في تحميل بيانات المنتج');
            });
        }

        /**
         * Delete Product
         */
        function deleteProduct(productId) {
            if (confirm('هل أنت متأكد من حذف هذا المنتج؟')) {
                fetch(`/admin/products/${productId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('تم حذف المنتج بنجاح');
                        location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('خطأ في حذف المنتج');
                });
            }
        }

        /**
         * Handle Edit Form Submission
         */
        document.getElementById('editProductForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const productId = document.getElementById('productId').value;
            const formData = new FormData(this);

            fetch(`/admin/products/${productId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('تم تحديث المنتج بنجاح');
                    bootstrap.Modal.getInstance(document.getElementById('editProductModal')).hide();
                    location.reload();
                } else {
                    alert(data.message || 'حدث خطأ ما');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('خطأ في حفظ البيانات');
            });
        });

        /**
         * Search Products
        
        document.getElementById('searchInput').addEventListener('input', function() {
            const query = this.value.toLowerCase();
            const rows = document.querySelectorAll('#productsTable tbody tr');

            rows.forEach(row => {
                if (row.querySelector('.empty-state')) return;

                const name = row.querySelector('td:nth-child(3)')?.textContent.toLowerCase() || '';
                const author = row.querySelector('td:nth-child(5)')?.textContent.toLowerCase() || '';

                const matches = name.includes(query) || author.includes(query);
                row.style.display = matches ? '' : 'none';
            });
        }); */

        /**
         * Reset Filters
         */
        function resetFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('categoryFilter').value = '';
            document.querySelectorAll('#productsTable tbody tr').forEach(row => {
                row.style.display = '';
            });
        }
    