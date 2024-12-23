<footer id="contact" class="bg-dark text-white text-center py-4" aria-label="Footer with contact information and copyright">
    <div class="container">
        <p class="mb-2">&copy; 2024 مكتبة الفقراء جميع الحقوق محفوظة.</p>
        <p class="mb-0">تم التصميم بمحبة ❤️.</p>
        <div class="mt-3">
            <a href="https://facebook.com" target="_blank" class="text-white mx-2"><i class="fab fa-facebook"></i></a>
            <a href="https://twitter.com" target="_blank" class="text-white mx-2"><i class="fab fa-twitter"></i></a>
            <a href="mailto:example@example.com" class="text-white mx-2"><i class="fas fa-envelope"></i></a>
        </div>
    </div>
</footer>
<button id="backToTop" class="btn btn-primary rounded-circle position-fixed" style="bottom: 20px; right: 20px; display: none;">
    ↑
</button>
<script>
    const backToTopButton = document.getElementById('backToTop');
    window.addEventListener('scroll', () => {
        if (window.scrollY > 300) {
            backToTopButton.style.display = 'block';
        } else {
            backToTopButton.style.display = 'none';
        }
    });
    backToTopButton.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
</script>
