 const carouselWrapper = document.getElementById('carouselWrapper');
        let isScrolling = false;

        // Touch/Swipe support for mobile
        let startX = 0;
        let scrollLeft = 0;

        carouselWrapper.addEventListener('mousedown', (e) => {
            isScrolling = true;
            startX = e.pageX - carouselWrapper.offsetLeft;
            scrollLeft = carouselWrapper.scrollLeft;
        });

        carouselWrapper.addEventListener('mouseleave', () => {
            isScrolling = false;
        });

        carouselWrapper.addEventListener('mouseup', () => {
            isScrolling = false;
        });

        carouselWrapper.addEventListener('mousemove', (e) => {
            if (!isScrolling) return;
            e.preventDefault();
            const x = e.pageX - carouselWrapper.offsetLeft;
            const walk = (x - startX) * 1.5;
            carouselWrapper.scrollLeft = scrollLeft - walk;
        });

        // Touch events for mobile
        carouselWrapper.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
            scrollLeft = carouselWrapper.scrollLeft;
        });

        carouselWrapper.addEventListener('touchmove', (e) => {
            const x = e.touches[0].clientX;
            const walk = (startX - x) * 1.5;
            carouselWrapper.scrollLeft = scrollLeft + walk;
        });

        // Prevent text selection while dragging
        carouselWrapper.addEventListener('selectstart', (e) => {
            e.preventDefault();
        });