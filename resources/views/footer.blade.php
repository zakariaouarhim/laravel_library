<!-- Footer -->
    <footer aria-label="Footer" class="Footerlayout">
        <div class="footer-top">
            <div class="container">
                <div class="footer-grid">
                    <!-- Brand Column -->
                    <div class="footer-col footer-brand">
                        <a href="{{ route('index.page') }}" class="footer-logo">
                            <img src="{{ asset('images/logo.svg') }}" alt="{{ $footerSettings['store_name'] ?? 'مكتبة الفقراء' }}">
                            <span>{{ $footerSettings['store_name'] ?? 'مكتبة الفقراء' }}</span>
                        </a>
                        <p class="footer-about">نؤمن بأن المعرفة حق للجميع. نسعى لتوفير أفضل الكتب بأسعار مناسبة لكل القراء.</p>
                        <div class="footer-social">
                            @if(!empty($footerSettings['facebook_url']))
                            <a href="{{ $footerSettings['facebook_url'] }}" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            @endif
                            @if(!empty($footerSettings['instagram_url']))
                            <a href="{{ $footerSettings['instagram_url'] }}" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
                                <i class="fab fa-instagram"></i>
                            </a>
                            @endif
                            @if(!empty($footerSettings['whatsapp_number']))
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $footerSettings['whatsapp_number']) }}" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp">
                                <i class="fab fa-whatsapp"></i>
                            </a>
                            @endif
                            @if(!empty($footerSettings['tiktok_url']))
                            <a href="{{ $footerSettings['tiktok_url'] }}" target="_blank" rel="noopener noreferrer" aria-label="TikTok">
                                <i class="fab fa-tiktok"></i>
                            </a>
                            @endif
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
                            <li><a href="{{ route('privacy.page') }}"><i class="fas fa-chevron-left"></i> سياسة الخصوصية</a></li>
                            <li><a href="{{ route('terms.page') }}"><i class="fas fa-chevron-left"></i> الشروط والأحكام</a></li>
                        </ul>
                    </div>

                    <!-- Contact Info -->
                    <div class="footer-col">
                        <h4 class="footer-heading">تواصل معنا</h4>
                        <ul class="footer-contact">
                            @if(!empty($footerSettings['store_email']))
                            <li>
                                <i class="fas fa-envelope"></i>
                                <span>{{ $footerSettings['store_email'] }}</span>
                            </li>
                            @endif
                            @if(!empty($footerSettings['store_phone']))
                            <li>
                                <i class="fas fa-phone-alt"></i>
                                <a href="tel:{{ $footerSettings['store_phone'] }}" dir="ltr" style="color:inherit;text-decoration:none;">{{ $footerSettings['store_phone'] }}</a>
                            </li>
                            @endif
                            @if(!empty($footerSettings['store_address']))
                            <li>
                                <i class="fas fa-map-marker-alt"></i>
                                <span>{{ $footerSettings['store_address'] }}</span>
                            </li>
                            @endif
                            <li>
                                <i class="fas fa-clock"></i>
                                <span>الثلثاء - الأحد: 10 صباحاً - 8 مساءً</span>
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
                    <p>&copy; {{ date('Y') }} {{ $footerSettings['store_name'] ?? 'مكتبة الفقراء' }}. جميع الحقوق محفوظة.</p>
                    <p>
                        <a href="{{ route('privacy.page') }}" style="color:inherit;opacity:.7;text-decoration:none;font-size:.85rem;">سياسة الخصوصية</a>
                        <span style="opacity:.4;margin:0 8px;">|</span>
                        <a href="{{ route('terms.page') }}" style="color:inherit;opacity:.7;text-decoration:none;font-size:.85rem;">الشروط والأحكام</a>
                    </p>
                </div>
            </div>
        </div>
    </footer>

    @include('partials.cookie-consent')

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