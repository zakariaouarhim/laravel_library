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


// Initialize an empty array to store cart items
let cartItems = [];

// Function to add book to the cart
function addToCart(bookId) {
    console.log('Adding book to cart:', bookId); // Debugging

    // Find the clicked button and get book data from data attributes
    let button = document.querySelector(`button[onclick="addToCart(${bookId})"]`);
    let bookTitle = button.getAttribute('data-title');
    let bookPrice = button.getAttribute('data-price');
    let bookImage = button.getAttribute('data-image');
    

    // Add book details to the cartItems array
    cartItems.push({
        id: bookId,
        title: bookTitle,
        price: bookPrice,
        image: bookImage
    });
    console.log(cartItems); // Debugging - Check if the array is updated
    // Update the cart count (for the header)
    document.getElementById('cartCount').innerText = cartItems.length;

    // Optionally, show a success toast
    document.querySelector('.toast-body').textContent = 
        `تمت إضافة "${bookTitle}" بسعر ${bookPrice} ر.س بنجاح إلى السلة`;
    let toastElement = document.getElementById('cartSuccessToast');
    let toast = new bootstrap.Toast(toastElement);
    toast.show();
}



