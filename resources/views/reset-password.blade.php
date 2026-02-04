<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعيين كلمة مرور جديدة</title>
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
        <div class="form-container bg-light p-4 rounded shadow" style="max-width: 500px; margin: 0 auto;">
            
            <!-- Success/Error Messages -->
            @if ($message = Session::get('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
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

            <!-- Reset Password Form -->
            <form method="POST" action="{{ route('password.update') }}" id="resetPasswordForm">
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

                <h2 class="form-title text-center mb-4">تعيين كلمة مرور جديدة</h2>

                <!-- Hidden Email Field -->
                <input type="hidden" name="email" value="{{ $email }}">
                <!-- Hidden Token Field -->
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="mb-3">
                    <label for="password" class="form-label">كلمة المرور الجديدة</label>
                    <input 
                        type="password" 
                        class="form-control @error('password') is-invalid @enderror" 
                        id="password" 
                        name="password" 
                        required 
                        autocomplete="new-password"
                        placeholder="أدخل كلمة مرور جديدة (8 أحرف على الأقل)"
                        minlength="8">
                    @error('password')
                    <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                    <small class="form-text text-muted d-block mt-1">
                        يجب أن تكون كلمة المرور 8 أحرف على الأقل
                    </small>
                </div>

                <div class="mb-3">
                    <label for="password_confirmation" class="form-label">تأكيد كلمة المرور</label>
                    <input 
                        type="password" 
                        class="form-control @error('password_confirmation') is-invalid @enderror" 
                        id="password_confirmation" 
                        name="password_confirmation" 
                        required 
                        autocomplete="new-password"
                        placeholder="أعد إدخال كلمة المرور">
                    @error('password_confirmation')
                    <span class="invalid-feedback">{{ $message }}</span>
                    @enderror
                    <small id="passwordMatch" class="form-text text-muted d-block mt-1">
                        يجب أن تتطابق كلمتا المرور
                    </small>
                </div>

                <button type="submit" class="btn btn-primary w-100 mb-3">
                    تعيين كلمة المرور الجديدة
                </button>

                <div class="text-center">
                    <a href="{{ route('login2.page') }}" class="text-decoration-none text-secondary">
                        العودة إلى تسجيل الدخول
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Validate password matching in real-time
        document.getElementById('password').addEventListener('input', validatePasswords);
        document.getElementById('password_confirmation').addEventListener('input', validatePasswords);

        function validatePasswords() {
            const password = document.getElementById('password').value;
            const confirmation = document.getElementById('password_confirmation').value;
            const matchText = document.getElementById('passwordMatch');
            const submitBtn = document.querySelector('button[type="submit"]');

            if (confirmation && password !== confirmation) {
                matchText.classList.remove('text-muted');
                matchText.classList.add('text-danger');
                matchText.textContent = '❌ كلمات المرور غير متطابقة';
                submitBtn.disabled = true;
            } else if (confirmation && password === confirmation) {
                matchText.classList.remove('text-danger');
                matchText.classList.add('text-success');
                matchText.textContent = '✓ كلمات المرور متطابقة';
                submitBtn.disabled = false;
            } else {
                matchText.classList.remove('text-success', 'text-danger');
                matchText.classList.add('text-muted');
                matchText.textContent = 'يجب أن تتطابق كلمتا المرور';
                submitBtn.disabled = false;
            }
        }
    </script>
</body>
</html>