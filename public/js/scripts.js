document.addEventListener('DOMContentLoaded', function () {
    const CAROUSEL_SELECTOR = '.carousel-item';
    const ACTIVE_CLASS = 'active';
    const NEXT_CLASS = 'carousel-item-next';
    const PREV_CLASS = 'carousel-item-prev';

    function getVisibleBookCount() {
        const screenWidth = window.innerWidth;
        if (screenWidth >= 1200) return 5;
        if (screenWidth >= 992) return 4;
        if (screenWidth >= 768) return 3;
        if (screenWidth >= 576) return 2;
        return 1;
    }

    function createCarouselItem(books) {
        const carouselItem = document.createElement('div');
        carouselItem.classList.add('carousel-item');

        const row = document.createElement('div');
        row.classList.add('row');

        books.forEach(book => {
            const col = document.createElement('div');
            col.classList.add('col-6', 'col-md-2');
            col.appendChild(book);
            row.appendChild(col);
        });

        carouselItem.appendChild(row);
        return carouselItem;
    }

    function rearrangeBooks(carouselInner) {
        const visibleCount = getVisibleBookCount();
        const books = Array.from(carouselInner.querySelectorAll('.card'));
        carouselInner.innerHTML = '';

        for (let i = 0; i < books.length; i += visibleCount) {
            const carouselItem = createCarouselItem(books.slice(i, i + visibleCount));
            carouselInner.appendChild(carouselItem);
        }

        if (carouselInner.children.length > 0) {
            carouselInner.children[0].classList.add(ACTIVE_CLASS);
        }
    }

    function smoothTransition(currentItem, nextItem, direction) {
        currentItem.classList.remove(ACTIVE_CLASS, NEXT_CLASS, PREV_CLASS);
        nextItem.classList.remove(ACTIVE_CLASS, NEXT_CLASS, PREV_CLASS);

        if (direction === 'next') {
            currentItem.classList.add(PREV_CLASS);
            nextItem.classList.add(NEXT_CLASS, ACTIVE_CLASS);
        } else {
            currentItem.classList.add(NEXT_CLASS);
            nextItem.classList.add(PREV_CLASS, ACTIVE_CLASS);
        }

        setTimeout(() => {
            currentItem.classList.remove(NEXT_CLASS, PREV_CLASS);
            nextItem.classList.remove(NEXT_CLASS, PREV_CLASS);
        }, 600);
    }

    function setupCarousel(carouselId) {
        const carousel = document.getElementById(carouselId);
        const carouselInner = carousel.querySelector('.carousel-inner');
        const prevButton = carousel.querySelector('.carousel-control-prev');
        const nextButton = carousel.querySelector('.carousel-control-next');

        rearrangeBooks(carouselInner);

        window.addEventListener('resize', debounce(() => rearrangeBooks(carouselInner), 100));

        prevButton.addEventListener('click', () => {
            const activeItem = carouselInner.querySelector(`.${ACTIVE_CLASS}`);
            const prevItem = activeItem.previousElementSibling || carouselInner.lastElementChild;
            smoothTransition(activeItem, prevItem, 'prev');
        });

        nextButton.addEventListener('click', () => {
            const activeItem = carouselInner.querySelector(`.${ACTIVE_CLASS}`);
            const nextItem = activeItem.nextElementSibling || carouselInner.firstElementChild;
            smoothTransition(activeItem, nextItem, 'next');
        });
    }

    function setupSwipe(carousel) {
        let touchStartX = 0;
        let touchEndX = 0;

        carousel.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
        });

        carousel.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        });

        function handleSwipe() {
            if (touchEndX < touchStartX) {
                document.querySelector('.carousel-control-next').click();
            }
            if (touchEndX > touchStartX) {
                document.querySelector('.carousel-control-prev').click();
            }
        }
    }

    function debounce(func, wait) {
        let timeout;
        return function (...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    // Initialize carousels
    setupCarousel('bookCarousel');
    setupCarousel('bookCarousel2');
    setupCarousel('bookCarousel3');

    // Initialize swipe for all carousels
    setupSwipe(document.getElementById('bookCarousel'));
    setupSwipe(document.getElementById('bookCarousel2'));
    setupSwipe(document.getElementById('bookCarousel3'));
});


function addToCart(bookId) {
    fetch(`/add-to-cart/${bookId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}' // Add CSRF token for Laravel
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('تمت إضافة الكتاب إلى السلة بنجاح!');
            // Update the cart count in the header
            document.getElementById('cartCount').innerText = data.cartCount;
        } else {
            alert('حدث خطأ أثناء إضافة الكتاب إلى السلة.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
}