// updateCartCount() and showCartAlert() are provided by scripts.js (always loaded first)

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
        showCartAlert('حدث خطأ أثناء الإضافة إلى السلة', 'danger');
    });
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