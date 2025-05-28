class EnhancedCarousel {
    constructor() {
        this.wrapper = document.getElementById('carouselWrapper');
        this.prevBtn = document.getElementById('prevBtn');
        this.nextBtn = document.getElementById('nextBtn');
        this.indicators = document.getElementById('indicators');
        
        this.currentPosition = 0;
        this.cardWidth = 240; // 220px + 20px gap
        this.visibleCards = this.calculateVisibleCards();
        this.totalCards = this.wrapper.children.length;
        this.maxPosition = Math.max(0, this.totalCards - this.visibleCards);
        
        this.init();
        this.setupEventListeners();
    }
    
    calculateVisibleCards() {
        const containerWidth = this.wrapper.parentElement.clientWidth;
        if (containerWidth < 576) return 1;
        if (containerWidth < 768) return 2;
        if (containerWidth < 992) return 3;
        if (containerWidth < 1200) return 4;
        return 5;
    }
    
    init() {
        this.createIndicators();
        this.updateCarousel();
        this.updateNavButtons();
    }
    
    createIndicators() {
        this.indicators.innerHTML = '';
        const indicatorCount = Math.ceil(this.totalCards / this.visibleCards);
        
        for (let i = 0; i < indicatorCount; i++) {
            const indicator = document.createElement('button');
            indicator.className = 'indicator';
            indicator.onclick = () => this.goToSlide(i);
            this.indicators.appendChild(indicator);
        }
        
        this.updateIndicators();
    }
    
    updateIndicators() {
        const indicators = this.indicators.querySelectorAll('.indicator');
        const activeIndex = Math.floor(this.currentPosition / this.visibleCards);
        
        indicators.forEach((indicator, index) => {
            indicator.classList.toggle('active', index === activeIndex);
        });
    }
    
    updateCarousel() {
        const translateX = this.currentPosition * this.cardWidth;
        this.wrapper.style.transform = `translateX(${translateX}px)`;
        this.updateIndicators();
    }
    
    updateNavButtons() {
        this.prevBtn.disabled = this.currentPosition === 0;
        this.nextBtn.disabled = this.currentPosition >= this.maxPosition;
    }
    
    move(direction) {
        const newPosition = this.currentPosition + (direction * this.visibleCards);
        
        if (newPosition >= 0 && newPosition <= this.maxPosition) {
            this.currentPosition = newPosition;
        } else if (direction > 0) {
            this.currentPosition = this.maxPosition;
        } else {
            this.currentPosition = 0;
        }
        
        this.updateCarousel();
        this.updateNavButtons();
    }
    
    goToSlide(slideIndex) {
        this.currentPosition = Math.min(slideIndex * this.visibleCards, this.maxPosition);
        this.updateCarousel();
        this.updateNavButtons();
    }
    
    setupEventListeners() {
        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') this.move(1);
            if (e.key === 'ArrowRight') this.move(-1);
        });
        
        // Touch/swipe support
        let startX = 0;
        let currentX = 0;
        let isDragging = false;
        
        this.wrapper.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
            isDragging = true;
        });
        
        this.wrapper.addEventListener('touchmove', (e) => {
            if (!isDragging) return;
            currentX = e.touches[0].clientX;
            e.preventDefault();
        });
        
        this.wrapper.addEventListener('touchend', () => {
            if (!isDragging) return;
            
            const diffX = startX - currentX;
            if (Math.abs(diffX) > 50) {
                this.move(diffX > 0 ? 1 : -1);
            }
            
            isDragging = false;
        });
        
        // Window resize
        window.addEventListener('resize', () => {
            this.visibleCards = this.calculateVisibleCards();
            this.maxPosition = Math.max(0, this.totalCards - this.visibleCards);
            this.currentPosition = Math.min(this.currentPosition, this.maxPosition);
            this.createIndicators();
            this.updateCarousel();
            this.updateNavButtons();
        });
    }
}

// Initialize carousel when DOM is loaded
let carousel;
document.addEventListener('DOMContentLoaded', () => {
    carousel = new EnhancedCarousel();
});

// Global functions for compatibility
function moveCarousel(direction) {
    if (carousel) {
        carousel.move(direction);
    }
}

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