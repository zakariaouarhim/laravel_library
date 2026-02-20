document.addEventListener('DOMContentLoaded', function() {
    // Initialize cart count on page load
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

function addToCartM(bookId) {
    const quantityInput = document.querySelector('input[aria-label="عدد النسخ"]');
    const quantity = quantityInput.value;
    const button = document.getElementById('addToCartButton');
    
    // Get book details from data attributes
    const title = button.getAttribute('data-title');
    const price = button.getAttribute('data-price');
    const image = button.getAttribute('data-image');

    // Disable button during request
    button.disabled = true;
    
    fetch('/add-to-cart/' + bookId, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            quantity: quantity,
            title: title,
            price: price,
            image: image
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update header cart count
            updateCartCount(data.cartCount);
            
            // Show success alert instead of toast
            showCartAlert(`تمت إضافة "${title}" (${quantity} نسخ) بسعر ${price} د.م بنجاح`);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showCartAlert('حدث خطأ أثناء إضافة المنتج للسلة', 'danger');
    })
    .finally(() => {
        button.disabled = false;
    });
}

// Function for carousel books
function addCarouselBookToCart(button) {
    const bookId = button.getAttribute('data-book-id');
    const bookTitle = button.getAttribute('data-book-title');
    const bookPrice = button.getAttribute('data-book-price');
    const bookImage = button.getAttribute('data-book-image');
    const quantity = 1; // Carousel books default to quantity 1
    
    performAddToCart(bookId, bookTitle, bookPrice, bookImage, quantity, button);
}

// Common function that performs the actual add to cart operation
function performAddToCart(bookId, bookTitle, bookPrice, bookImage, quantity, button) {
    fetch(`/add-to-cart/${bookId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
        body: JSON.stringify({
            title: bookTitle,
            price: bookPrice,
            image: bookImage,
            quantity: quantity
        })
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            // Add success animation
            if (button) {
                button.classList.add('add-success');
                
                setTimeout(() => {
                    button.classList.remove('add-success');
                    // Show success feedback
                    const originalContent = button.innerHTML;
                    button.innerHTML = '<i class="fas fa-check"></i>';
                    button.style.background = '#28a745';
                    
                    setTimeout(() => {
                        button.innerHTML = originalContent;
                        button.style.background = '';
                    }, 1500);
                }, 300);
            }
            
            // Update cart count
            updateCartCount(data.cartCount);
            
            // Show success alert
            showCartAlert(`تمت إضافة "${bookTitle}" إلى السلة`);
            
            // Update the cart modal if it's open
            const cartModal = document.getElementById('cartDetailsModal');
            if(cartModal && cartModal.classList.contains('show')) {
                if (typeof showCartModal === 'function') {
                    showCartModal();
                }
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showCartAlert('حدث خطأ أثناء الإضافة إلى السلة', 'danger');
    });
}

// Helper function to update cart count
function updateCartCount(count) {
    const cartCountElement = document.getElementById('cartCount');
    if (cartCountElement) {
        cartCountElement.textContent = count;
    }
}

// Alert function with timer and icon
function showCartAlert(message, type = 'success') {
    // Create alert element
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.setAttribute('role', 'alert');
    alert.style.position = 'fixed';
    alert.style.top = '80px'; // Adjust based on your header height
    alert.style.left = '50%';
    alert.style.transform = 'translateX(-50%)';
    alert.style.zIndex = '9999';
    alert.style.minWidth = '300px';
    alert.style.maxWidth = '500px';
    alert.style.boxShadow = '0 4px 6px rgba(0,0,0,0.1)';
    
    // Choose icon based on alert type
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
    
    // Get progress bar color based on alert type
    const progressBarColor = type === 'success' ? 'bg-success' : 
                            type === 'danger' ? 'bg-danger' : 
                            type === 'warning' ? 'bg-warning' : 'bg-info';
    
    alert.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="fas ${icon} me-2" style="font-size: 1.5rem;"></i>
            <div class="flex-grow-1">${message}</div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <div class="progress mt-2" style="height: 3px; background-color: rgba(0,0,0,0.1);">
            <div class="progress-bar ${progressBarColor}" role="progressbar" style="width: 100%; transition: width 3s linear;"></div>
        </div>
    `;
    
    // Add to body
    document.body.appendChild(alert);
    
    // Start the timer animation (progress bar goes from 100% to 0% in 3 seconds)
    const progressBar = alert.querySelector('.progress-bar');
    setTimeout(() => {
        progressBar.style.width = '0%';
    }, 10);
    
    // Auto remove after 3 seconds (when timer finishes)
    setTimeout(() => {
        alert.classList.remove('show');
        setTimeout(() => alert.remove(), 150);
    }, 3000);
}

// Star rating functionality
document.addEventListener('DOMContentLoaded', function() {
    const stars = document.querySelectorAll('.star-rating label');
    const ratingText = document.getElementById('rating-text');
    const ratingMessages = {
        1: 'نجمة واحدة - ضعيف',
        2: 'نجمتان - مقبول',
        3: 'ثلاث نجوم - جيد',
        4: 'أربع نجوم - جيد جداً',
        5: 'خمس نجوم - ممتاز'
    };
    
    // Add click animation and feedback
    stars.forEach(star => {
        star.addEventListener('click', function() {
            // Animation effect
            this.style.transform = 'scale(1.2)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 200);
            
            // Update feedback text
            const rating = this.previousElementSibling.value;
            ratingText.textContent = ratingMessages[rating];
            ratingText.style.color = '#ffc107';
            ratingText.style.fontWeight = '600';
        });
        
        // Hover effect for better UX
        star.addEventListener('mouseenter', function() {
            const rating = this.previousElementSibling.value;
            ratingText.textContent = ratingMessages[rating];
            ratingText.style.color = '#6c757d';
        });
    });
    
    // Reset text when mouse leaves star area
    const starRating = document.querySelector('.star-rating');
    if (starRating) {
        starRating.addEventListener('mouseleave', function() {
            const checkedInput = document.querySelector('input[name="rating"]:checked');
            if (checkedInput) {
                ratingText.textContent = ratingMessages[checkedInput.value];
                ratingText.style.color = '#ffc107';
                ratingText.style.fontWeight = '600';
            } else {
                ratingText.textContent = 'اختر عدد النجوم';
                ratingText.style.color = '#6c757d';
                ratingText.style.fontWeight = 'normal';
            }
        });
    }
    
    // Initialize display if there's a pre-selected rating
    const checkedInput = document.querySelector('input[name="rating"]:checked');
    if (checkedInput && ratingText) {
        ratingText.textContent = ratingMessages[checkedInput.value];
        ratingText.style.color = '#ffc107';
        ratingText.style.fontWeight = '600';
    }
});