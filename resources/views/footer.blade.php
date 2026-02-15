<!-- Footer -->
    <footer aria-label="Footer" class="Footerlayout">
        <div class="footer-top">
            <div class="container">
                <div class="footer-grid">
                    <!-- Brand Column -->
                    <div class="footer-col footer-brand">
                        <a href="{{ route('index.page') }}" class="footer-logo">
                            <img src="{{ asset('images/logo.svg') }}" alt="مكتبة الفقراء">
                            <span>مكتبة الفقراء</span>
                        </a>
                        <p class="footer-about">نؤمن بأن المعرفة حق للجميع. نسعى لتوفير أفضل الكتب بأسعار مناسبة لكل القراء.</p>
                        <div class="footer-social">
                            <a href="https://facebook.com" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="https://instagram.com" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
                                <i class="fab fa-instagram"></i>
                            </a>
                            <a href="https://wa.me/1234567890" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp">
                                <i class="fab fa-whatsapp"></i>
                            </a>
                            <a href="https://twitter.com" target="_blank" rel="noopener noreferrer" aria-label="Twitter">
                                <i class="fab fa-twitter"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Quick Links -->
                    <div class="footer-col">
                        <h4 class="footer-heading">روابط سريعة</h4>
                        <ul class="footer-links">
                            <li><a href="{{ route('index.page') }}"><i class="fas fa-chevron-left"></i> الرئيسية</a></li>
                            <li><a href="{{ route('categories.index') }}"><i class="fas fa-chevron-left"></i> التصنيفات</a></li>
                            <li><a href="{{ route('authors.index') }}"><i class="fas fa-chevron-left"></i> المؤلفون</a></li>
                            <li><a href="{{ route('accessories.index') }}"><i class="fas fa-chevron-left"></i> الإكسسوارات</a></li>
                            <li><a href="{{ route('publishers.index') }}"><i class="fas fa-chevron-left"></i> دور النشر</a></li>
                        </ul>
                    </div>

                    <!-- Help Links -->
                    <div class="footer-col">
                        <h4 class="footer-heading">المساعدة</h4>
                        <ul class="footer-links">
                            <li><a href="{{ route('about.page') }}"><i class="fas fa-chevron-left"></i> من نحن</a></li>
                            <li><a href="{{ route('contact.page') }}"><i class="fas fa-chevron-left"></i> اتصل بنا</a></li>
                        </ul>
                    </div>

                    <!-- Contact Info -->
                    <div class="footer-col">
                        <h4 class="footer-heading">تواصل معنا</h4>
                        <ul class="footer-contact">
                            <li>
                                <i class="fas fa-envelope"></i>
                                <span>info@maktabet-alfuqara.com</span>
                            </li>
                            <li>
                                <i class="fas fa-phone-alt"></i>
                                <span dir="ltr">+966 XX XXX XXXX</span>
                            </li>
                            <li>
                                <i class="fas fa-map-marker-alt"></i>
                                <span>المملكة العربية السعودية</span>
                            </li>
                            <li>
                                <i class="fas fa-clock"></i>
                                <span>السبت - الخميس: 9ص - 10م</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Bottom -->
        <div class="footer-bottom">
            <div class="container">
                <div class="footer-bottom-content">
                    <p>&copy; {{ date('Y') }} مكتبة الفقراء. جميع الحقوق محفوظة.</p>
                    <p class="design-credit">تم التصميم بمحبة ❤️</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <button id="backToTop" class="btn btn-primary rounded-circle position-fixed" aria-label="العودة للأعلى">
        <i class="fas fa-arrow-up"></i>
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