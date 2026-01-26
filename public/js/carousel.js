class EnhancedCarousel {
    constructor(element) {
        // Auto-detect carousel container with data-carousel attribute
        this.container = element;
        this.wrapper = this.container.querySelector('[data-carousel-wrapper]');
        this.prevBtn = this.container.querySelector('[data-carousel-prev]');
        this.nextBtn = this.container.querySelector('[data-carousel-next]');
        this.indicators = this.container.querySelector('[data-carousel-indicators]');
        
        if (!this.wrapper || !this.prevBtn || !this.nextBtn || !this.indicators) {
            console.warn('Carousel missing required elements:', element);
            return;
        }
        
        this.cardWidth = 240; // 220px card + 20px gap
        this.isRTL = document.documentElement.dir === 'rtl';
        
        this.init();
    }

    init() {
        this.createIndicators();
        this.updateNavButtons();
        
        // Event listeners
        this.prevBtn.addEventListener('click', () => this.move(-1));
        this.nextBtn.addEventListener('click', () => this.move(1));
        
        this.wrapper.addEventListener('scroll', () => {
            this.updateIndicators();
            this.updateNavButtons();
        });
        
        window.addEventListener('resize', () => {
            this.createIndicators();
            this.updateNavButtons();
        });
    }

    move(direction) {
        const scrollAmount = this.cardWidth * direction;
        const targetScroll = this.isRTL ? -(scrollAmount) : scrollAmount;

        this.wrapper.scrollBy({
            left: targetScroll,
            behavior: 'smooth'
        });
    }

    createIndicators() {
        if (!this.indicators) return;
        this.indicators.innerHTML = '';
        
        const totalSlides = Math.ceil(this.wrapper.scrollWidth / this.cardWidth);
        const visibleSlides = Math.floor(this.wrapper.clientWidth / this.cardWidth);
        const dotCount = Math.max(1, totalSlides - visibleSlides + 1);

        for (let i = 0; i < dotCount; i++) {
            const indicator = document.createElement('button');
            indicator.className = 'indicator';
            indicator.setAttribute('aria-label', `Go to slide ${i + 1}`);
            
            indicator.addEventListener('click', () => {
                const targetPos = i * this.cardWidth;
                this.wrapper.scrollTo({
                    left: this.isRTL ? -targetPos : targetPos,
                    behavior: 'smooth'
                });
            });
            
            this.indicators.appendChild(indicator);
        }
        this.updateIndicators();
    }

    updateIndicators() {
        if (!this.indicators) return;
        
        const currentScroll = Math.abs(this.wrapper.scrollLeft);
        const currentIndex = Math.round(currentScroll / this.cardWidth);
        
        const indicators = this.indicators.querySelectorAll('.indicator');
        indicators.forEach((ind, index) => {
            ind.classList.toggle('active', index === currentIndex);
        });
    }

    updateNavButtons() {
        if (!this.prevBtn || !this.nextBtn) return;

        const currentScroll = Math.abs(this.wrapper.scrollLeft);
        const maxScroll = this.wrapper.scrollWidth - this.wrapper.clientWidth;

        this.prevBtn.disabled = currentScroll <= 10; 
        this.nextBtn.disabled = currentScroll >= (maxScroll - 10);
    }
}

// Auto-detect and initialize all carousels on page
document.addEventListener('DOMContentLoaded', () => {
    const carouselElements = document.querySelectorAll('[data-carousel]');
    carouselElements.forEach(element => {
        new EnhancedCarousel(element);
    });
});

// Optional: Re-initialize carousels if new ones are dynamically added
function reinitializeCarousels() {
    const carouselElements = document.querySelectorAll('[data-carousel]');
    carouselElements.forEach(element => {
        // Check if already initialized
        if (!element.dataset.initialized) {
            new EnhancedCarousel(element);
            element.dataset.initialized = 'true';
        }
    });
}