function addToWishlist(bookId) {
    // Add loading state
    const button = event.target;
    const originalContent = button.innerHTML;
    button.innerHTML = '<i class="bi bi-hourglass-split"></i>';
    button.disabled = true;
    
    fetch(`/wishlist/add/${bookId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        credentials: 'same-origin'
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        
        if (data.success) {
            // Show success message
            showToast('تم إضافة الكتاب للمفضلة', 'success');
            
            // Update button state
            button.innerHTML = '<i class="bi bi-heart-fill"></i>';
            button.classList.remove('btn-outline-primary');
            button.classList.add('btn-primary');
            button.title = 'تم الإضافة للمفضلة';
        } else {
            showToast(data.message || 'حدث خطأ', 'error');
            button.innerHTML = originalContent;
        }
    })
    .catch(error => {
        console.error('Fetch Error Details:', error);
        showToast('حدث خطأ في الشبكة: ' + error.message, 'error');
        button.innerHTML = originalContent;
    })
    .finally(() => {
        button.disabled = false;
    });
}

function hideRecommendation(bookId) {
    const button = event.target;
    const recommendationItem = button.closest('.recommendation-item');
    
    fetch(`/recommendations/hide/${bookId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        credentials: 'same-origin'
    })
    .then(response => {
        console.log('Hide recommendation response:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Remove the recommendation from view with animation
            recommendationItem.style.transition = 'opacity 0.3s ease';
            recommendationItem.style.opacity = '0';
            
            setTimeout(() => {
                recommendationItem.remove();
            }, 300);
            
            showToast('تم إخفاء الترشيح', 'info');
        } else {
            showToast(data.message || 'حدث خطأ في إخفاء الترشيح', 'error');
        }
    })
    .catch(error => {
        console.error('Hide recommendation error:', error);
        showToast('حدث خطأ في إخفاء الترشيح', 'error');
    });
}

function showToast(message, type) {
    // Remove existing toasts
    const existingToasts = document.querySelectorAll('.custom-toast');
    existingToasts.forEach(toast => toast.remove());
    
    // Create new toast
    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'} position-fixed custom-toast`;
    toast.style.cssText = `
        top: 20px; 
        right: 20px; 
        z-index: 9999; 
        min-width: 300px;
        animation: slideIn 0.3s ease-out;
    `;
    
    toast.innerHTML = `
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <i class="bi bi-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white" aria-label="Close" onclick="this.parentElement.parentElement.remove()"></button>
        </div>
    `;
    
    // Add CSS for animation
    if (!document.querySelector('#toast-animations')) {
        const style = document.createElement('style');
        style.id = 'toast-animations';
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    }
    
    document.body.appendChild(toast);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (toast.parentElement) {
            toast.style.animation = 'slideOut 0.3s ease-in';
            setTimeout(() => {
                if (toast.parentElement) {
                    toast.remove();
                }
            }, 300);
        }
    }, 5000);
}

// Debug function to check if CSRF token exists
function checkCSRFToken() {
    const token = document.querySelector('meta[name="csrf-token"]');
    console.log('CSRF Token exists:', !!token);
    if (token) {
        console.log('CSRF Token value:', token.getAttribute('content'));
    }
    return !!token;
}

document.querySelectorAll('.toggle-wishlist-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        const bookId = this.getAttribute('data-book-id');
        const addIcon = this.querySelector('.add-icon');
        const removeIcon = this.querySelector('.remove-icon');

        // If it's not added → Add to wishlist
        if (!removeIcon.classList.contains('d-none')) {
            // REMOVE from wishlist
            fetch(`/wishlist/remove/${bookId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),

                    'Accept': 'application/json',
                }
            }).then(response => response.json())
              .then(data => {
                  if (data.success) {
                      // Toggle icons
                      removeIcon.classList.add('d-none');
                      addIcon.classList.remove('d-none');
                  } else {
                      alert(data.message || 'خطأ في الحذف من المفضلة');
                  }
              });
        } else {
            // ADD to wishlist
            fetch(`/wishlist/add/${bookId}`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),

                    'Accept': 'application/json',
                }
            }).then(response => response.json())
              .then(data => {
                  if (data.success) {
                      // Toggle icons
                      addIcon.classList.add('d-none');
                      removeIcon.classList.remove('d-none');
                  } else {
                      alert(data.message || 'خطأ في الإضافة للمفضلة');
                  }
              });
        }
    });
});
// Call this when page loads to verify setup
document.addEventListener('DOMContentLoaded', function() {
    checkCSRFToken();
    
    // Test if user is authenticated
    console.log('Current URL:', window.location.href);
    console.log('User authenticated check - looking for auth indicators...');
});