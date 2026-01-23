class EnhancedCarousel {
    constructor(wrapperId, prevBtnId, nextBtnId, indicatorsId) {
        this.wrapper = document.getElementById(wrapperId);
        this.prevBtn = document.getElementById(prevBtnId);
        this.nextBtn = document.getElementById(nextBtnId);
        this.indicators = document.getElementById(indicatorsId);
        
        if (!this.wrapper || !this.prevBtn || !this.nextBtn || !this.indicators) return;
        
        this.cardWidth = 240; // 220px card + 20px gap
        
        // Check direction for RTL support
        this.isRTL = document.documentElement.dir === 'rtl';

        this.init();
    }

    init() {
        this.createIndicators();
        this.updateNavButtons();
        
        // LISTEN FOR SCROLL: Updates dots when user swipes manually
        this.wrapper.addEventListener('scroll', () => {
            this.updateIndicators();
            this.updateNavButtons();
        });
        
        // Window resize
        window.addEventListener('resize', () => {
            this.createIndicators();
        });
    }

    // Move the carousel using Native Scroll
    move(direction) {
        // Calculate movement distance
        // In RTL, "Next" (Left arrow) means moving to negative scroll values
        const scrollAmount = this.cardWidth * direction;
        
        const targetScroll = this.isRTL 
            ? -(scrollAmount) // RTL needs negative value to go "Next" (Left)
            : scrollAmount;

        this.wrapper.scrollBy({
            left: targetScroll,
            behavior: 'smooth'
        });
    }

    createIndicators() {
        if (!this.indicators) return;
        this.indicators.innerHTML = '';
        
        // Calculate total slides based on scroll width
        const totalScrollWidth = this.wrapper.scrollWidth - this.wrapper.clientWidth;
        const totalSlides = Math.ceil(this.wrapper.scrollWidth / this.cardWidth);
        const visibleSlides = Math.floor(this.wrapper.clientWidth / this.cardWidth);
        const dotCount = totalSlides - visibleSlides + 1;

        for (let i = 0; i < dotCount; i++) {
            const indicator = document.createElement('button');
            indicator.className = 'indicator';
            // Click to scroll to specific position
            indicator.onclick = () => {
                const targetPos = i * this.cardWidth;
                this.wrapper.scrollTo({
                    left: this.isRTL ? -targetPos : targetPos,
                    behavior: 'smooth'
                });
            };
            this.indicators.appendChild(indicator);
        }
        this.updateIndicators();
    }

    updateIndicators() {
        if (!this.indicators) return;
        
        // Calculate current index based on scroll position
        const currentScroll = Math.abs(this.wrapper.scrollLeft);
        const currentIndex = Math.round(currentScroll / this.cardWidth);
        
        const indicators = this.indicators.querySelectorAll('.indicator');
        indicators.forEach((ind, index) => {
            ind.classList.toggle('active', index === currentIndex);
        });
    }

    updateNavButtons() {
        if (!this.prevBtn || !this.nextBtn) return;

        // Use a small buffer (10px) for float math safety
        const currentScroll = Math.abs(this.wrapper.scrollLeft);
        const maxScroll = this.wrapper.scrollWidth - this.wrapper.clientWidth;

        // In RTL, "Next" increases scroll magnitude (goes more negative)
        // "Prev" goes back to 0
        this.prevBtn.disabled = currentScroll <= 10; 
        this.nextBtn.disabled = currentScroll >= (maxScroll - 10);
    }
}

// Initialization remains the same
let carousels = {};
document.addEventListener('DOMContentLoaded', () => {
    carousels.carousel1 = new EnhancedCarousel('carouselWrapper1', 'prevBtn1', 'nextBtn1', 'indicators1');
    carousels.carousel2 = new EnhancedCarousel('carouselWrapper2', 'prevBtn2', 'nextBtn2', 'indicators2');
    carousels.carousel3 = new EnhancedCarousel('carouselWrapper3', 'prevBtn3', 'nextBtn3', 'indicators3');
});

// Helper function
function moveCarousel(direction, carouselId) {
    if (carousels[carouselId]) {
        carousels[carouselId].move(direction);
    }
}