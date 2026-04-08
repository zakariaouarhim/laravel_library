document.addEventListener('DOMContentLoaded', function () {
    // Mobile filter accordion: tap title to collapse/expand body (≤991px only)
    const mobileBreakpoint = window.matchMedia('(max-width: 991.98px)');
    const filterSections = document.querySelectorAll('.filter-section');

    // Collapse all sections by default on mobile (desktop CSS ignores .collapsed)
    if (mobileBreakpoint.matches) {
        filterSections.forEach(s => s.classList.add('collapsed'));
    }
    mobileBreakpoint.addEventListener('change', e => {
        if (e.matches) {
            filterSections.forEach(s => s.classList.add('collapsed'));
        }
    });

    document.querySelectorAll('.filter-section .filter-title').forEach(title => {
        title.addEventListener('click', function (e) {
            if (!mobileBreakpoint.matches) return;
            const section = title.closest('.filter-section');
            if (section) section.classList.toggle('collapsed');
        });
    });

    // Sidebar categories: show all / show less toggle
    const sidebarToggleBtn = document.querySelector('.toggle-sidebar-categories');
    if (sidebarToggleBtn) {
        const extras = document.querySelectorAll('#sidebarCategoryList .category-extra');
        const showHtml = sidebarToggleBtn.dataset.showHtml;
        const hideHtml = sidebarToggleBtn.dataset.hideHtml;
        let expanded = false;
        sidebarToggleBtn.addEventListener('click', function () {
            expanded = !expanded;
            extras.forEach(el => el.classList.toggle('d-none', !expanded));
            sidebarToggleBtn.innerHTML = expanded ? hideHtml : showHtml;
            sidebarToggleBtn.classList.toggle('expanded', expanded);
        });
    }

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