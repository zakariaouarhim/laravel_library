<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول أو إنشاء حساب</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
    <link rel="icon" href="{{ asset('images/logo.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
</head>
<body>
    <div class="container">
        <!-- Logo -->
        <div class="logo-container text-center my-4">
            <a href="{{ route('index.page') }}">
                <img src="{{ asset('images/Logo2Black.svg') }}" alt="شعار المكتبة" class="img-fluid">
            </a>
        </div>

        <!-- Form Container -->
        <div class="form-container bg-light p-4 rounded shadow">
            <!-- Login Form -->
            <form id="login-form" method="POST" action="{{ route('userlogin') }}">
              @csrf
              @if ($errors->any())
              <div class="alert alert-danger">
                  <ul>
                      @foreach ($errors->all() as $error)
                          <li>{{ $error }}</li>
                      @endforeach
                  </ul>
              </div>
              @endif
                <h2 class="form-title text-center mb-4">تسجيل الدخول</h2>
                <div class="mb-3">
                    <label for="loginEmail" class="form-label">البريد الإلكتروني</label>
                    <input type="email" class="form-control" id="loginEmail" name="email" required autocomplete="email">
                </div>
                <div class="mb-3">
                    <label for="loginPassword" class="form-label">كلمة المرور</label>
                    <input type="password" class="form-control" id="loginPassword" name="password" required autocomplete="current-password">
                </div>
                <button type="submit" class="btn btn-primary w-100">تسجيل الدخول</button>
            </form>

            <!-- Register Form -->
            <form id="register-form" style="display: none;" method="POST" action="{{ route('adduser') }}">
                @csrf
                <h2 class="form-title text-center mb-4">إنشاء حساب</h2>
                <div class="mb-3">
                    <label for="registerName" class="form-label">الاسم الكامل</label>
                    <input type="text" class="form-control" id="registerName" name="name" required autocomplete="name">
                </div>
                <div class="mb-3">
                    <label for="registerEmail" class="form-label">البريد الإلكتروني</label>
                    <input type="email" class="form-control" id="registerEmail" name="email" required autocomplete="email">
                </div>
                <div class="mb-3">
                    <label for="registerPassword" class="form-label">كلمة المرور</label>
                    <input type="password" class="form-control" id="registerPassword" name="password" required autocomplete="new-password">
                </div>
                <div class="mb-3">
                    <label for="confirmPassword" class="form-label">تأكيد كلمة المرور</label>
                    <input type="password" class="form-control" id="confirmPassword" name="password_confirmation" required autocomplete="new-password">
                    <small id="passwordError" class="text-danger d-none">كلمات المرور غير متطابقة</small>
                </div>
                <button type="submit" class="btn btn-success w-100">إنشاء حساب</button>
            </form>

            <!-- Toggle Link -->
            <div class="toggle-link text-center mt-3">
                <a href="#" id="toggleForm" class="text-decoration-none">إنشاء حساب جديد</a>
            </div>
        </div>
    </div>

    <script src="{{ asset('js/login.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>