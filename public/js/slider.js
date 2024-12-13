document.addEventListener('DOMContentLoaded', function() {
    const slider = {
        container: document.querySelector('.slider-container'),
        track: document.querySelector('.slider-track'),
        prevButton: document.querySelector('.slider-prev'),
        nextButton: document.querySelector('.slider-next'),
        
        // Sample book data - replace with your actual data
        books: [
            {
                title: 'كتاب الأول',
                price: '50 ريال',
                image: 'path/to/book1.jpg'
            },
            // Add more books here
        ],

        init() {
            this.createSlides();
            this.addEventListeners();
            this.updateArrowVisibility();
        },

        createSlides() {
            this.books.forEach(book => {
                const slide = document.createElement('div');
                slide.className = 'product-card';
                slide.innerHTML = `
                    <img src="${book.image}" alt="${book.title}">
                    <h3>${book.title}</h3>
                    <p class="price">${book.price}</p>
                `;
                this.track.appendChild(slide);
            });
        },

        addEventListeners() {
            this.prevButton.addEventListener('click', () => this.slide('right'));
            this.nextButton.addEventListener('click', () => this.slide('left'));
        },

        slide(direction) {
            const slideWidth = this.track.firstElementChild.offsetWidth + 16; // width + gap
            const currentScroll = this.container.scrollLeft;
            const newScroll = direction === 'left' 
                ? currentScroll + slideWidth 
                : currentScroll - slideWidth;
            
            this.container.scrollTo({
                left: newScroll,
                behavior: 'smooth'
            });

            setTimeout(() => this.updateArrowVisibility(), 300);
        },

        updateArrowVisibility() {
            const { scrollLeft, scrollWidth, clientWidth } = this.container;
            this.prevButton.style.display = scrollLeft > 0 ? 'block' : 'none';
            this.nextButton.style.display = 
                scrollLeft < scrollWidth - clientWidth ? 'block' : 'none';
        }
    };

    slider.init();
});
