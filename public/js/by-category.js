document.addEventListener('DOMContentLoaded', function () {
    const showMoreBtn = document.getElementById('showMoreBtn');
    if (showMoreBtn) {
        showMoreBtn.addEventListener('click', function () {
            document.querySelectorAll('.extra-publisher').forEach(el => {
                el.classList.toggle('d-none');
            });

            // Toggle button text/icon
            if (showMoreBtn.innerHTML.includes('عرض المزيد')) {
                showMoreBtn.innerHTML = '<i class="fas fa-chevron-up me-1"></i> عرض أقل';
                showMoreBtn.classList.add('active');
            } else {
                showMoreBtn.innerHTML = '<i class="fas fa-chevron-down me-1"></i> عرض المزيد';
                showMoreBtn.classList.remove('active');
            }
        });
    }
});
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('publisherSearch');
    const publisherItems = document.querySelectorAll('#publisherList .custom-checkbox');
    const showMoreBtn = document.getElementById('showMoreBtn');

    searchInput.addEventListener('input', function () {
        const keyword = this.value.trim().toLowerCase();
        let matchCount = 0;

        publisherItems.forEach(item => {
            const label = item.querySelector('label').textContent.trim().toLowerCase();
            const matches = label.includes(keyword);

            item.classList.toggle('d-none', !matches);

            if (matches) matchCount++;
        });

        // Hide the "Show More" button when searching
        if (keyword !== '') {
            if (showMoreBtn) showMoreBtn.style.display = 'none';
        } else {
            if (showMoreBtn) showMoreBtn.style.display = 'block';
            // Reset all hidden items if not filtered
            publisherItems.forEach((item, index) => {
                if (index >= 4) {
                    item.classList.add('d-none', !showMoreBtn.classList.contains('active'));
                } else {
                    item.classList.remove('d-none');
                }
            });
        }
    });
});