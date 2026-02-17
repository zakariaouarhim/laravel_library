(function () {
    var tabLogin = document.getElementById('tab-login');
    var tabRegister = document.getElementById('tab-register');
    var loginForm = document.getElementById('login-form');
    var registerForm = document.getElementById('register-form');
    var confirmPassword = document.getElementById('confirmPassword');
    var passwordError = document.getElementById('passwordError');

    // Switch between Login and Register tabs
    function showLogin() {
        tabLogin.classList.add('active');
        tabRegister.classList.remove('active');
        loginForm.style.display = 'block';
        registerForm.style.display = 'none';
        loginForm.style.animation = 'none';
        loginForm.offsetHeight; // trigger reflow
        loginForm.style.animation = 'fadeIn 0.4s ease-out';
    }

    function showRegister() {
        tabRegister.classList.add('active');
        tabLogin.classList.remove('active');
        registerForm.style.display = 'block';
        loginForm.style.display = 'none';
        registerForm.style.animation = 'none';
        registerForm.offsetHeight; // trigger reflow
        registerForm.style.animation = 'fadeIn 0.4s ease-out';
    }

    tabLogin.addEventListener('click', showLogin);
    tabRegister.addEventListener('click', showRegister);

    // Password visibility toggle
    var toggleButtons = document.querySelectorAll('.password-toggle');
    toggleButtons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = btn.parentElement.querySelector('input[type="password"], input[type="text"]');
            var icon = btn.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });

    // Password matching validation
    confirmPassword.addEventListener('input', function () {
        var password = document.getElementById('registerPassword').value;
        if (confirmPassword.value !== password) {
            passwordError.classList.remove('d-none');
        } else {
            passwordError.classList.add('d-none');
        }
    });

    // Form submission validation
    registerForm.addEventListener('submit', function (e) {
        var password = document.getElementById('registerPassword').value;
        if (confirmPassword.value !== password) {
            e.preventDefault();
            passwordError.classList.remove('d-none');
        }
    });
})();
