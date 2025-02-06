// Fetch and display cart modal
function showCartModal() {
    fetch('/get-cart')
        .then(response => response.json())
        .then(data => {
            const modalBody = document.querySelector('#cartDetailsModal .modal-body');
            modalBody.innerHTML = '';

            if (!data.success || Object.keys(data.cart).length === 0) {
                modalBody.innerHTML = '<p>سلّة التسوق فارغة</p>';
            } else {
                Object.values(data.cart).forEach(item => {
                    const itemHTML = `
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <img src="/${item.image}" alt="${item.title}" class="img-thumbnail" style="width: 80px; height: 100px;">
                        <div class="ms-3">
                            <h6 class="mb-1">${item.title}</h6>
                            <div class="d-flex align-items-center">
                                <span class="text-muted me-2">${item.quantity}x</span>
                                <span class="fw-bold">${item.price} ر.س</span>
                            </div>
                        </div>
                        <button class="btn btn-danger btn-sm" onclick="removeFromCart('${item.id}')">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                `;

                    modalBody.innerHTML += itemHTML;
                });
            }

            // Initialize and show modal
            new bootstrap.Modal(document.getElementById('cartDetailsModal')).show();
            
        })
        .catch(error => {
            console.error('Error:', error);
            showCartToast('حدث خطأ أثناء تحميل السلة');
        });
}

// Toast notification function
function showCartToast(message) {
    const toastElement = document.getElementById('cartToast');
    const toastBody = toastElement.querySelector('.toast-body');
    toastBody.textContent = message;
    new bootstrap.Toast(toastElement).show();
}

function removeFromCart(itemId) {
    fetch('/remove-from-cart', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') // CSRF Token for Laravel
        },
        body: JSON.stringify({ id: itemId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showCartModal(); // Refresh the modal
            document.getElementById('cartCount').textContent = data.cartCount; // Update cart count badge
        } else {
            alert('حدث خطأ أثناء حذف المنتج');
        }
    })
    .catch(error => console.error('Error:', error));
}
