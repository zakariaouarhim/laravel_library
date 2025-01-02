
        const toggleForm = document.getElementById('toggleForm');
        const loginForm = document.getElementById('login-form');
        const registerForm = document.getElementById('register-form');
        const confirmPassword = document.getElementById('confirmPassword');
        const passwordError = document.getElementById('passwordError');

        // Toggle between Login and Register forms
        toggleForm.addEventListener('click', (e) => {
            e.preventDefault();
            if (loginForm.style.display === 'none') {
                loginForm.style.display = 'block';
                registerForm.style.display = 'none';
                toggleForm.textContent = 'إنشاء حساب جديد';
            } else {
                loginForm.style.display = 'none';
                registerForm.style.display = 'block';
                toggleForm.textContent = 'تسجيل الدخول';
            }
        });

        // Password matching validation
        confirmPassword.addEventListener('input', () => {
            const password = document.getElementById('registerPassword').value;
            if (confirmPassword.value !== password) {
                passwordError.classList.remove('d-none');
            } else {
                passwordError.classList.add('d-none');
            }
        });

        // Form submission validation
        registerForm.addEventListener('submit', (e) => {
            const password = document.getElementById('registerPassword').value;
            if (confirmPassword.value !== password) {
                e.preventDefault();
                passwordError.classList.remove('d-none');
            }
        });
    