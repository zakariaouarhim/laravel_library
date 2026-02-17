<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول أو إنشاء حساب</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
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
                    <img src="{{ asset('images/Logo2Black.svg') }}" alt="شعار المكتبة" class="banner-logo">
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
                    <img src="{{ asset('images/Logo2Black.svg') }}" alt="شعار المكتبة">
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

    <script src="{{ asset('js/login.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
