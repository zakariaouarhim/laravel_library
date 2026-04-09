(function() {
    // Account dropdown toggle
    var accountToggle = document.getElementById('accountToggle');
    var accountMenu = document.getElementById('accountMenu');
    if (accountToggle && accountMenu) {
        accountToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            accountMenu.classList.toggle('show');
        });
        document.addEventListener('click', function(e) {
            if (!accountMenu.contains(e.target) && e.target !== accountToggle) {
                accountMenu.classList.remove('show');
            }
        });
    }

    // Categories mega menu
    var categoriesBtn = document.getElementById('categoriesBtn');
    var megaMenu = document.getElementById('categoriesMegaMenu');
    if (categoriesBtn && megaMenu) {
        categoriesBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            megaMenu.classList.toggle('show');
            categoriesBtn.classList.toggle('active');
        });
        document.addEventListener('click', function(e) {
            if (!megaMenu.contains(e.target) && e.target !== categoriesBtn) {
                megaMenu.classList.remove('show');
                categoriesBtn.classList.remove('active');
            }
        });
    }

    // Mobile menu
    var mobileBtn = document.getElementById('mobileMenuBtn');
    var mobileMenu = document.getElementById('mobileMenu');
    var mobileOverlay = document.getElementById('mobileOverlay');
    var mobileClose = document.getElementById('mobileMenuClose');

    function openMobile() {
        mobileMenu.classList.add('open');
        mobileOverlay.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
    function closeMobile() {
        mobileMenu.classList.remove('open');
        mobileOverlay.classList.remove('show');
        document.body.style.overflow = '';
    }
    if (mobileBtn) mobileBtn.addEventListener('click', openMobile);
    if (mobileClose) mobileClose.addEventListener('click', closeMobile);
    if (mobileOverlay) mobileOverlay.addEventListener('click', closeMobile);

    // Expose closeMobile so the slide-menu cart link can call it
    window.closeMobile = closeMobile;

    // Sync cart badge to bottom bar + mobile menu whenever #cartCount changes
    var headerCartBadge = document.getElementById('cartCount');
    if (headerCartBadge) {
        var badgeObserver = new MutationObserver(function() {
            var count = headerCartBadge.textContent.trim();
            var bottomBadge = document.getElementById('bottomCartCount');
            var mobileBadge = document.getElementById('mobileCartCount');
            if (bottomBadge) bottomBadge.textContent = count;
            if (mobileBadge) mobileBadge.textContent = count;
        });
        badgeObserver.observe(headerCartBadge, { childList: true, characterData: true, subtree: true });
    }

    // Bottom bar: highlight active item based on current path
    var bottomItems = document.querySelectorAll('.bottom-bar-item');
    var currentPath = window.location.pathname;
    bottomItems.forEach(function(item) {
        var href = item.getAttribute('href');
        if (href && href !== 'javascript:void(0);' && currentPath.indexOf(href) === 0) {
            item.classList.add('active');
        }
    });

    // Sticky navbar — hide top bar on scroll, navbar + nav links stick together
    var topBar = document.getElementById('topBar');
    var mainNavbar = document.getElementById('mainNavbar');
    var navLinksBar = document.getElementById('navLinksBar');

    window.addEventListener('scroll', function() {
        var scrollY = window.scrollY;
        if (scrollY > 50) {
            topBar.classList.add('hidden');
            mainNavbar.classList.add('sticky');
            navLinksBar.classList.add('sticky');
        } else {
            topBar.classList.remove('hidden');
            mainNavbar.classList.remove('sticky');
            navLinksBar.classList.remove('sticky');
        }
    });
})();
