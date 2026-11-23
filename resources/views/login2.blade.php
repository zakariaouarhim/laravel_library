<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>تسجيل الدخول أو إنشاء حساب</title>
    <link rel="stylesheet" href="{{ asset('css/variables.css') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
</head>
<body>
    <div class="login-wrapper">
        <!-- Left Decorative Panel -->
        <div class="login-banner">
            <div class="banner-content">
                <a href="{{ route('index.page') }}">
                    <img src="{{ asset('images/Logo2Black.svg') }}" alt="شعار المكتبة" class="banner-logo" width="180" height="60">
                </a>
                <h1 class="banner-title">مرحباً بك في مكتبة الفقراء</h1>
                <p class="banner-subtitle">اكتشف عالماً من الكتب والمعرفة</p>
                <div class="banner-decoration">
                    <i class="fas fa-book-open"></i>
                </div>
            </div>
        </div>

        <!-- Right Form Panel -->
        <div class="login-form-panel">
            <!-- Mobile Logo -->
            <div class="mobile-logo">
                <a href="{{ route('index.page') }}">
                    <img src="{{ asset('images/Logo2Black.svg') }}" alt="شعار المكتبة" width="140" height="50">
                </a>
            </div>

            <!-- Tab Buttons -->
            <div class="form-tabs">
                <button class="form-tab active" id="tab-login" type="button">
                    <i class="fas fa-sign-in-alt"></i>
                    تسجيل الدخول
                </button>
                <button class="form-tab" id="tab-register" type="button">
                    <i class="fas fa-user-plus"></i>
                    إنشاء حساب
                </button>
            </div>

            <!-- Success/Error Messages -->
            @if ($message = Session::get('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>{{ $message }}</strong>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            @if ($message = Session::get('fail'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>{{ $message }}</strong>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            @if ($message = Session::get('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>{{ $message }}</strong>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            <!-- Login Form -->
            <form id="login-form" class="auth-form" method="POST" action="{{ route('userlogin') }}">
                @csrf
                @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <div class="input-group-custom">
                    <span class="input-icon"><i class="fas fa-envelope"></i></span>
                    <input type="email" class="form-control" id="loginEmail" name="email" placeholder="البريد الإلكتروني" required autocomplete="email" value="{{ old('email') }}">
                </div>

                <div class="input-group-custom">
                    <span class="input-icon"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control" id="loginPassword" name="password" placeholder="كلمة المرور" required autocomplete="current-password">
                    <button type="button" class="password-toggle" tabindex="-1">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>

                <div class="form-options">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                        <label class="form-check-label" for="remember">تذكرني</label>
                    </div>
                    <a href="{{ route('password.request') }}" class="forgot-link">هل نسيت كلمة المرور؟</a>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-sign-in-alt"></i>
                    تسجيل الدخول
                </button>
            </form>

            <!-- Register Form -->
            <form id="register-form" class="auth-form" style="display: none;" method="POST" action="{{ route('adduser') }}">
                @csrf
                @include('partials.anti-spam-fields')

                <div class="input-group-custom">
                    <span class="input-icon"><i class="fas fa-user"></i></span>
                    <input type="text" class="form-control" id="registerName" name="name" placeholder="الاسم الكامل" required autocomplete="name" value="{{ old('name') }}">
                </div>

                <div class="input-group-custom">
                    <span class="input-icon"><i class="fas fa-envelope"></i></span>
                    <input type="email" class="form-control" id="registerEmail" name="email" placeholder="البريد الإلكتروني" required autocomplete="email" value="{{ old('email') }}">
                </div>

                <div class="input-group-custom">
                    <span class="input-icon"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control" id="registerPassword" name="password" placeholder="كلمة المرور" required autocomplete="new-password">
                    <button type="button" class="password-toggle" tabindex="-1">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>

                <div class="input-group-custom">
                    <span class="input-icon"><i class="fas fa-lock"></i></span>
                    <input type="password" class="form-control" id="confirmPassword" name="password_confirmation" placeholder="تأكيد كلمة المرور" required autocomplete="new-password">
                    <button type="button" class="password-toggle" tabindex="-1">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <small id="passwordError" class="text-danger d-none" style="margin-top: -10px; display: block; margin-bottom: 10px;">كلمات المرور غير متطابقة</small>

                {{-- Rendered only once Turnstile keys exist in .env; until then the
                     honeypot and timing checks in AntiSpam stand alone.
                     Filled by turnstile.render() at the end of this file, not by
                     the implicit `cf-turnstile` class -- see the note there. --}}
                @if (config('services.turnstile.site_key'))
                    <div id="turnstile-widget" style="margin-bottom: 12px;"></div>
                @endif

                <button type="submit" class="btn-submit btn-submit-register">
                    <i class="fas fa-user-plus"></i>
                    إنشاء حساب
                </button>
            </form>

            <!-- Back to Home -->
            <div class="back-home">
                <a href="{{ route('index.page') }}">
                    <i class="fas fa-arrow-right"></i>
                    العودة إلى الصفحة الرئيسية
                </a>
            </div>
        </div>
    </div>

    @if (config('services.turnstile.site_key'))
        {{--
            Explicit rendering, not the implicit `cf-turnstile` class.

            #register-form is display:none until its tab is clicked, and
            Cloudflare documents automatic rendering for widgets present at page
            load, recommending turnstile.render() for forms revealed later. A
            widget that silently failed to produce a token would make AntiSpam
            reject every genuine signup -- honeypot and timing would have let
            them through -- so this path stays on documented ground and builds
            the widget only once its container is actually visible.
        --}}
        <script>
            (function () {
                var SITE_KEY = @json(config('services.turnstile.site_key'));
                var apiReady = false, wanted = false, rendered = false;

                function render() {
                    if (rendered || !apiReady || !wanted || !window.turnstile) { return; }
                    var host = document.getElementById('turnstile-widget');
                    if (!host) { return; }
                    rendered = true;
                    window.turnstile.render(host, { sitekey: SITE_KEY, language: 'ar' });
                }

                // Named in the api.js query string; Cloudflare calls it on load.
                window.onTurnstileLoad = function () { apiReady = true; render(); };

                // login.js is deferred, so its tab handler runs after this one:
                // on click the form is still hidden. Yield a tick so the widget
                // is built into a visible container.
                function want() { wanted = true; setTimeout(render, 0); }

                var tab = document.getElementById('tab-register');
                if (tab) { tab.addEventListener('click', want); }

                // Don't depend on the click alone -- the form may already be
                // open (restored browser state, or a later deep link).
                var form = document.getElementById('register-form');
                if (form && form.style.display !== 'none') { want(); }
            })();
        </script>
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?onload=onTurnstileLoad&render=explicit" defer></script>
    @endif
    <script src="{{ asset('js/login.js') }}" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js" defer></script>
</body>
</html>
