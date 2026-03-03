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
