class EnhancedCarousel {
    constructor(wrapperId, prevBtnId, nextBtnId, indicatorsId) {
        this.wrapper = document.getElementById(wrapperId);
        this.prevBtn = document.getElementById(prevBtnId);
        this.nextBtn = document.getElementById(nextBtnId);
        this.indicators = document.getElementById(indicatorsId);
        
        // Check if all elements exist
        if (!this.wrapper || !this.prevBtn || !this.nextBtn || !this.indicators) {
            console.warn(`Carousel elements not found for ${wrapperId}`);
            return;
        }
        
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
        // Calculate which indicator should be active based on current position
        const activeIndex = Math.floor(this.currentPosition / this.visibleCards);
        
        indicators.forEach((indicator, index) => {
            indicator.classList.toggle('active', index === activeIndex);
        });
    }
    
    updateCarousel() {
        // Move by cardWidth for each position (one card at a time)
        const translateX = this.currentPosition * this.cardWidth;
        this.wrapper.style.transform = `translateX(${translateX}px)`;
        this.updateIndicators();
    }
    
    updateNavButtons() {
        this.prevBtn.disabled = this.currentPosition === 0;
        this.nextBtn.disabled = this.currentPosition >= this.maxPosition;
    }
    
    move(direction) {
        // Move one card at a time instead of visibleCards
        const newPosition = this.currentPosition + direction;
        
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
                // Move one card at a time for touch swipe as well
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

// Store carousel instances
let carousels = {};

// Initialize carousels when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    // Initialize first carousel (Arabic books)
    carousels.carousel1 = new EnhancedCarousel('carouselWrapper1', 'prevBtn1', 'nextBtn1', 'indicators1');
    
    // Initialize second carousel (English books)
    carousels.carousel2 = new EnhancedCarousel('carouselWrapper2', 'prevBtn2', 'nextBtn2', 'indicators2');
});

// Global function for compatibility with onclick handlers
function moveCarousel(direction, carouselId) {
    if (carousels[carouselId]) {
        carousels[carouselId].move(direction);
    }
}