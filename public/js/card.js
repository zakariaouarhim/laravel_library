function toggleWishlist(bookId, buttonElement) {
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const icon = buttonElement.querySelector('i');
    
    // Check if already in wishlist by checking if icon is filled (fas)
    const isInWishlist = icon.classList.contains('fas');
    
    // Determine which route to use
    const route = isInWishlist ? `/wishlist/remove/${bookId}` : `/wishlist/add/${bookId}`;
    
    fetch(route, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': token
        }
    })
    .then(response => {
        const contentType = response.headers.get('content-type');
        
        if (!contentType || !contentType.includes('application/json')) {
            return response.text().then(text => {
                throw new Error('Invalid response format');
            });
        }
        
        return response.json().then(data => ({
            status: response.status,
            data: data
        }));
    })
    .then(({ status, data }) => {
        if (data.success) {
            // Show success modal
            showModal(data.message, 'success');
            
            // Toggle the icon
            if (isInWishlist) {
                icon.classList.remove('fas');
                icon.classList.add('far');
                buttonElement.classList.remove('active');
            } else {
                icon.classList.remove('far');
                icon.classList.add('fas');
                buttonElement.classList.add('active');
            }
        } else {
            // Show error modal
            if (status === 401) {
                showModal(data.message, 'warning', true, bookId);
            } else {
                showModal(data.message, 'error');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showModal('حدث خطأ في العملية', 'error');
    });
}

function showModal(message, type = 'info', showLogin = false, bookId = null) {
    // Create modal HTML if it doesn't exist
    let modal = document.getElementById('wishlistModal');
    
    if (!modal) {
        const modalHTML = `
            <div class="modal fade" id="wishlistModal" tabindex="-1" aria-labelledby="wishlistModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header" id="modalHeader">
                            <h5 class="modal-title" id="wishlistModalLabel">إشعار</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" id="modalBody">
                            <!-- Message will be inserted here -->
                        </div>
                        <div class="modal-footer" id="modalFooter">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        modal = document.getElementById('wishlistModal');
    }
    
    // Update modal content based on type
    const modalBody = modal.querySelector('#modalBody');
    const modalHeader = modal.querySelector('#modalHeader');
    const modalFooter = modal.querySelector('#modalFooter');
    
    modalBody.innerHTML = message;
    
    // Clear footer buttons
    const closeBtn = modalFooter.querySelector('.btn-secondary');
    const otherBtns = modalFooter.querySelectorAll('button:not(.btn-secondary)');
    otherBtns.forEach(btn => btn.remove());
    
    // Set header background color based on type
    modalHeader.className = 'modal-header';
    switch(type) {
        case 'success':
            modalHeader.classList.add('bg-success', 'text-white');
            modalHeader.querySelector('.modal-title').textContent = 'نجح';
            break;
        case 'error':
            modalHeader.classList.add('bg-danger', 'text-white');
            modalHeader.querySelector('.modal-title').textContent = 'خطأ';
            break;
        case 'warning':
            modalHeader.classList.add('bg-warning', 'text-dark');
            modalHeader.querySelector('.modal-title').textContent = 'تنبيه';
            break;
        case 'info':
        default:
            modalHeader.classList.add('bg-info', 'text-white');
            modalHeader.querySelector('.modal-title').textContent = 'إشعار';
    }
    
    // Add login button if needed
    if (showLogin) {
        const loginBtn = document.createElement('button');
        loginBtn.type = 'button';
        loginBtn.className = 'btn btn-primary me-2';
        loginBtn.textContent = 'الذهاب لتسجيل الدخول';
        loginBtn.onclick = function() {
            window.location.href = '/login2';
        };
        
        modalFooter.insertBefore(loginBtn, closeBtn);
    }
    
    // Show the modal
    const bsModal = new bootstrap.Modal(modal, {
        keyboard: false
    });
    bsModal.show();
}