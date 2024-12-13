document.addEventListener('DOMContentLoaded', function() {
    // Responsive Carousel Configuration
    function setupBookCarousel() {
        const carousel = document.getElementById('bookCarousel');
        const carousel2 = document.getElementById('bookCarousel2');
        const carousel3 = document.getElementById('bookCarousel3');
        const carouselInner = carousel.querySelector('.carousel-inner');
        const prevButton = carousel.querySelector('.carousel-control-prev');
        const nextButton = carousel.querySelector('.carousel-control-next');

        // Determine number of books to show based on screen width
        function getVisibleBookCount() {
            const screenWidth = window.innerWidth;
            if (screenWidth >= 1200) return 5; // Large screens
            if (screenWidth >= 992) return 4; // Medium-large screens
            if (screenWidth >= 768) return 3; // Tablets
            if (screenWidth >= 576) return 2; // Small tablets
            return 1; // Mobile screens
        }

        // Rearrange books in carousel items
        function rearrangeBooks() {
            const visibleCount = getVisibleBookCount();
            const books = Array.from(carouselInner.querySelectorAll('.card'));
            
            // Clear existing carousel items
            carouselInner.innerHTML = '';

            // Create new carousel items
            for (let i = 0; i < books.length; i += visibleCount) {
                const carouselItem = document.createElement('div');
                carouselItem.classList.add('carousel-item');
                
                const row = document.createElement('div');
                row.classList.add('row');

                // Add books to this carousel item
                books.slice(i, i + visibleCount).forEach(book => {
                    const col = document.createElement('div');
                    col.classList.add('col-6', 'col-md-2');
                    col.appendChild(book);
                    row.appendChild(col);
                });

                carouselItem.appendChild(row);
                carouselInner.appendChild(carouselItem);
            }

            // Set first item as active
            if (carouselInner.children.length > 0) {
                carouselInner.children[0].classList.add('active');
            }
        }

        // Smooth transition function
        function smoothTransition(currentItem, nextItem, direction) {
            // Remove any existing transition classes
            carouselInner.querySelectorAll('.carousel-item').forEach(item => {
                item.classList.remove('carousel-item-next', 'carousel-item-prev', 'active');
            });

            // Add transition classes based on direction
            if (direction === 'next') {
                currentItem.classList.add('carousel-item-prev');
                nextItem.classList.add('carousel-item-next', 'active');
            } else {
                currentItem.classList.add('carousel-item-next');
                nextItem.classList.add('carousel-item-prev', 'active');
            }

            // Remove transition classes after animation
            setTimeout(() => {
                carouselInner.querySelectorAll('.carousel-item').forEach(item => {
                    item.classList.remove('carousel-item-next', 'carousel-item-prev');
                });
            }, 600);
        }

        // Initial setup
        rearrangeBooks();

        // Rearrange on window resize
        window.addEventListener('resize', rearrangeBooks);

        // Custom navigation handling
        prevButton.addEventListener('click', () => {
            const activeItem = carouselInner.querySelector('.carousel-item.active');
            const prevItem = activeItem.previousElementSibling || carouselInner.lastElementChild;
            
            smoothTransition(activeItem, prevItem, 'prev');
        });

        nextButton.addEventListener('click', () => {
            const activeItem = carouselInner.querySelector('.carousel-item.active');
            const nextItem = activeItem.nextElementSibling || carouselInner.firstElementChild;
            
            smoothTransition(activeItem, nextItem, 'next');
        });
    }

    // Initialize carousel
    setupBookCarousel();

    // Additional touch/swipe support for mobile
    let touchStartX = 0;
    let touchEndX = 0;
    const carousel = document.getElementById('bookCarousel');
    const carousel2 = document.getElementById('bookCarousel2');
    const carousel3 = document.getElementById('bookCarousel3');

    carousel.addEventListener('touchstart', (e) => {
        touchStartX = e.changedTouches[0].screenX;
    });

    carousel2.addEventListener('touchstart', (e) => {
        touchStartX = e.changedTouches[0].screenX;
    });

    carousel.addEventListener('touchend', (e) => {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipe();
    });

    carousel2.addEventListener('touchend', (e) => {
        touchEndX = e.changedTouches[0].screenX;
        handleSwipe();
    });
    

    function handleSwipe() {
        if (touchEndX < touchStartX) {
            // Swiped left, go to next
            document.querySelector('.carousel-control-next').click();
        }
        if (touchEndX > touchStartX) {
            // Swiped right, go to previous
            document.querySelector('.carousel-control-prev').click();
        }
    }
});