const carouselWrapper = document.getElementById('carouselWrapper');

// Mouse drag support for desktop only
if (window.matchMedia('(hover: hover)').matches) {
    let isScrolling = false;
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

    carouselWrapper.addEventListener('selectstart', (e) => {
        if (isScrolling) e.preventDefault();
    });
}
// Touch devices: native scroll handles everything (momentum, inertia, smoothness)