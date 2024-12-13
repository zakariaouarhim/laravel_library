document.addEventListener("DOMContentLoaded", function () {
    const updateCarouselItems = () => {
        const screenWidth = window.innerWidth;
        const carouselItems = document.querySelectorAll(".carousel-inner .row");

        carouselItems.forEach((row) => {
            const cols = row.children;

            if (screenWidth < 768) {
                Array.from(cols).forEach((col) => (col.style.flex = "0 0 50%")); // 2 items
            } else if (screenWidth < 1200) {
                Array.from(cols).forEach((col) => (col.style.flex = "0 0 25%")); // 4 items
            } else {
                Array.from(cols).forEach((col) => (col.style.flex = "0 0 20%")); // 5 items
            }
        });
    };

    updateCarouselItems();
    window.addEventListener("resize", updateCarouselItems);
});
