function addToCart(bookId, bookTitle, bookPrice, bookImage) {
    console.log("Parameters:", { bookId, bookTitle, bookPrice, bookImage });
    
    // Get the button element that was clicked
    const button = event.target.closest('.add-btn');
    
    fetch(`/add-to-cart/${bookId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
        body: JSON.stringify({
            title: bookTitle,
            price: bookPrice,
            image: bookImage
        })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            // Add success animation
            button.classList.add('add-success');
            
            // Simulate API call
            setTimeout(() => {
                button.classList.remove('add-success');
                // Show success feedback
                button.innerHTML = '<i class="fas fa-check"></i>';
                button.style.background = '#28a745';
                
                setTimeout(() => {
                    button.innerHTML = '<i class="fas fa-shopping-cart"></i>';
                    button.style.background = '';
                }, 1500);
                
                console.log(`تمت إضافة الكتاب ${bookId} إلى السلة`);
            }, 300);
            
            updateCartCount(data.cartCount);
            showCartToast(`تمت إضافة "${bookTitle}" إلى السلة`);
            
            // Update the cart modal if it's open
            const cartModal = document.getElementById('cartDetailsModal');
            if(cartModal && cartModal.classList.contains('show')) {
                showCartModal();
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showCartToast('حدث خطأ أثناء الإضافة إلى السلة');
    });
}

// Fixed helper functions to match your HTML structure
function updateCartCount(count) {
    const cartCountElement = document.getElementById('cartCount');
    if (cartCountElement) {
        cartCountElement.textContent = count;
    }
}

function showCartToast(message) {
    // Use the correct ID from your HTML: cartSuccessToast
    const toastElement = document.getElementById('cartSuccessToast');
    if (toastElement) {
        // Update the toast body message
        const toastBody = toastElement.querySelector('.toast-body');
        if (toastBody) {
            toastBody.textContent = message;
        }
        
        // Show the toast using Bootstrap's Toast API
        const toast = new bootstrap.Toast(toastElement);
        toast.show();
    } else {
        console.error('Toast element not found');
    }
}

// Initialize cart count on page load
document.addEventListener('DOMContentLoaded', function() {
    fetch('/get-cart')
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                updateCartCount(data.cartCount);
            }
        })
        .catch(error => {
            console.error('Error loading cart count:', error);
        });
});

document.getElementById('cartDetailsModal').addEventListener('hidden.bs.modal', function () {
    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
    document.body.classList.remove('modal-open');
    document.body.style.paddingRight = '';
});