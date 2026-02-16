<link rel="stylesheet" href="{{ asset('css/cookie-consent.css') }}">
<!-- Cookie Consent Banner -->
<div id="cookieConsent" class="cookie-consent" style="display: none;">
    <div class="cookie-consent-inner">
        <div class="cookie-consent-text">
            <i class="fas fa-cookie-bite cookie-icon"></i>
            <div>
                <strong>نحن نستخدم ملفات تعريف الارتباط</strong>
                <p>نستخدم ملفات تعريف الارتباط (الكوكيز) لتحسين تجربتك في الموقع وتذكر تفضيلاتك. باستمرارك في التصفح، فإنك توافق على استخدامنا لها.</p>
            </div>
        </div>
        <div class="cookie-consent-actions">
            <button id="cookieAccept" class="cookie-btn cookie-btn-accept">موافق</button>
            <button id="cookieDecline" class="cookie-btn cookie-btn-decline">رفض</button>
        </div>
    </div>
</div>

<script>
(function() {
    function getCookie(name) {
        var match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
        return match ? match[2] : null;
    }

    function setCookie(name, value, days) {
        var expires = '';
        if (days) {
            var date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = '; expires=' + date.toUTCString();
        }
        document.cookie = name + '=' + value + expires + '; path=/; SameSite=Lax';
    }

    var consent = getCookie('cookie_consent');
    if (!consent) {
        var banner = document.getElementById('cookieConsent');
        setTimeout(function() {
            banner.style.display = 'block';
            setTimeout(function() {
                banner.classList.add('show');
            }, 10);
        }, 1000);
    }

    document.getElementById('cookieAccept').addEventListener('click', function() {
        setCookie('cookie_consent', 'accepted', 365);
        closeBanner();
    });

    document.getElementById('cookieDecline').addEventListener('click', function() {
        setCookie('cookie_consent', 'declined', 30);
        closeBanner();
    });

    function closeBanner() {
        var banner = document.getElementById('cookieConsent');
        banner.classList.remove('show');
        banner.classList.add('hide');
        setTimeout(function() {
            banner.style.display = 'none';
        }, 400);
    }
})();
</script>
