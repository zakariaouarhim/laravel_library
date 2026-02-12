/**
 * View Accessory Details
 */
function viewAccessory(id) {
    const viewModal = new bootstrap.Modal(document.getElementById('viewAccessoryModal'));
    const viewContent = document.getElementById('viewAccessoryContent');

    viewContent.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">جاري التحميل...</span>
            </div>
        </div>
    `;

    fetch(`/admin/accessories/${id}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) throw new Error('Failed to load accessory');
        return response.json();
    })
    .then(item => {
        const content = `
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <div>
                    ${item.image ? `
                        <img src="/${item.image}" alt="${item.title}" style="width: 100%; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                    ` : `
                        <div style="width: 100%; height: 250px; background: #e9ecef; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-bookmark" style="font-size: 3rem; color: #bdc3c7;"></i>
                        </div>
                    `}
                </div>
                <div>
                    <h4 style="color: #2c3e50; font-weight: 700; margin-bottom: 1rem;">${item.title}</h4>
                    <div style="margin-bottom: 1rem;">
                        <label style="color: #48CAE4; font-weight: 600; font-size: 0.9rem;">السعر</label>
                        <p style="color: #27ae60; font-weight: 700; font-size: 1.3rem; margin: 0.5rem 0 0;">${parseFloat(item.price).toFixed(2)} ر.س</p>
                    </div>
                    ${item.discount ? `
                    <div style="margin-bottom: 1rem;">
                        <label style="color: #48CAE4; font-weight: 600; font-size: 0.9rem;">الخصم</label>
                        <p style="color: #e74c3c; margin: 0.5rem 0 0;">${item.discount}%</p>
                    </div>
                    ` : ''}
                    <div style="margin-bottom: 1rem;">
                        <label style="color: #48CAE4; font-weight: 600; font-size: 0.9rem;">الكمية المتاحة</label>
                        <p style="color: #2c3e50; margin: 0.5rem 0 0;">${item.Quantity}</p>
                    </div>
                    <div style="margin-bottom: 1rem;">
                        <label style="color: #48CAE4; font-weight: 600; font-size: 0.9rem;">الصنف</label>
                        <p style="color: #2c3e50; margin: 0.5rem 0 0;">${item.category ? item.category.name : '-'}</p>
                    </div>
                </div>
            </div>
            <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e9ecef;">
                <label style="color: #48CAE4; font-weight: 600; font-size: 0.9rem;">الوصف</label>
                <p style="color: #2c3e50; line-height: 1.6; margin-top: 0.5rem;">${item.description || '-'}</p>
            </div>
        `;
        viewContent.innerHTML = content;
    })
    .catch(error => {
        console.error('Error:', error);
        viewContent.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>
                خطأ في تحميل تفاصيل الإكسسوار
            </div>
        `;
    });

    viewModal.show();
}

/**
 * Edit Accessory - Load data
 */
function editAccessory(id) {
    const editModal = new bootstrap.Modal(document.getElementById('editAccessoryModal'));

    fetch(`/admin/accessories/${id}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) throw new Error('Failed to load accessory');
        return response.json();
    })
    .then(item => {
        document.getElementById('editAccessoryId').value = item.id;
        document.getElementById('editTitle').value = item.title;
        document.getElementById('editDescription').value = item.description || '';
        document.getElementById('editPrice').value = item.price;
        document.getElementById('editDiscount').value = item.discount || '';
        document.getElementById('editQuantity').value = item.Quantity;
        document.getElementById('editCategoryId').value = item.category_id || '';

        if (item.image) {
            const preview = document.getElementById('editImagePreview');
            preview.src = `/${item.image}`;
            preview.style.display = 'block';
        } else {
            document.getElementById('editImagePreview').style.display = 'none';
        }

        editModal.show();
    })
    .catch(error => {
        console.error('Error:', error);
        alert('خطأ في تحميل بيانات الإكسسوار');
    });
}

/**
 * Delete Accessory
 */
function deleteAccessory(id) {
    if (confirm('هل أنت متأكد من حذف هذا الإكسسوار؟')) {
        fetch(`/admin/accessories/${id}`, {
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
                alert('تم حذف الإكسسوار بنجاح');
                location.reload();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('خطأ في حذف الإكسسوار');
        });
    }
}

/**
 * Handle Edit Form Submission
 */
document.getElementById('editAccessoryForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const id = document.getElementById('editAccessoryId').value;
    const formData = new FormData(this);

    fetch(`/admin/accessories/${id}`, {
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
            alert('تم تحديث الإكسسوار بنجاح');
            bootstrap.Modal.getInstance(document.getElementById('editAccessoryModal')).hide();
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
