// Login/Register Modal JavaScript

(function() {
    'use strict';

    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function() {
        initAuthModal();
    });

    function initAuthModal() {
        // Elements - Use more specific selector
        const loginIcon = document.querySelector('.auth-modal-trigger');
        const authModal = document.getElementById('authModal');
        const authModalOverlay = document.getElementById('authModalOverlay');
        const authModalClose = document.getElementById('authModalClose');
        const switchToRegister = document.getElementById('switchToRegister');
        const switchToLogin = document.getElementById('switchToLogin');
        const loginForm = document.getElementById('loginForm');
        const registerForm = document.getElementById('registerForm');
        const passwordToggles = document.querySelectorAll('.password-toggle');
        const registerPassword = document.getElementById('register-password');
        const passwordStrength = document.getElementById('passwordStrength');

        if (!authModal || !loginIcon) return;

        // Open Modal (Login) - Direct event listener, no capture phase needed
        loginIcon.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            openModal();
            showLogin();
        });

        // Close Modal
        authModalClose.addEventListener('click', closeModal);
        authModalOverlay.addEventListener('click', closeModal);

        // ESC key to close
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && authModal.classList.contains('active')) {
                closeModal();
            }
        });

        // Switch Forms
        switchToRegister.addEventListener('click', function(e) {
            e.preventDefault();
            showRegister();
        });

        switchToLogin.addEventListener('click', function(e) {
            e.preventDefault();
            showLogin();
        });

        // Password Toggle (Show/Hide)
        passwordToggles.forEach(function(toggle) {
            toggle.addEventListener('click', function() {
                const targetId = this.dataset.target;
                const input = document.getElementById(targetId);
                const eyeIcon = this.querySelector('.eye-icon');
                const eyeOffIcon = this.querySelector('.eye-off-icon');

                if (input.type === 'password') {
                    input.type = 'text';
                    eyeIcon.style.display = 'none';
                    eyeOffIcon.style.display = 'block';
                } else {
                    input.type = 'password';
                    eyeIcon.style.display = 'block';
                    eyeOffIcon.style.display = 'none';
                }
            });
        });

        // Password Strength Indicator
        if (registerPassword && passwordStrength) {
            registerPassword.addEventListener('input', function() {
                const password = this.value;
                const strength = checkPasswordStrength(password);

                if (password.length === 0) {
                    passwordStrength.textContent = '';
                    passwordStrength.className = 'password-strength';
                } else if (strength === 'weak') {
                    passwordStrength.textContent = '⚠️ Schwach';
                    passwordStrength.className = 'password-strength weak';
                } else if (strength === 'medium') {
                    passwordStrength.textContent = '👍 Mittel';
                    passwordStrength.className = 'password-strength medium';
                } else {
                    passwordStrength.textContent = '✅ Stark';
                    passwordStrength.className = 'password-strength strong';
                }
            });
        }

        // Form Validation
        const registerFormElement = document.getElementById('registerFormElement');
        if (registerFormElement) {
            registerFormElement.addEventListener('submit', function(e) {
                e.preventDefault();

                const firstname = document.getElementById('register-firstname').value.trim();
                const lastname = document.getElementById('register-lastname').value.trim();
                const email = document.getElementById('register-email').value.trim();
                const password = document.getElementById('register-password').value;
                const passwordConfirm = document.getElementById('register-password-confirm').value;
                const termsAccepted = document.querySelector('input[name="terms"]').checked;

                // Validation
                if (!firstname || !lastname) {
                    showAlert('Bitte geben Sie Vor- und Nachnamen ein.', 'error');
                    return;
                }

                if (!isValidEmail(email)) {
                    showAlert('Bitte geben Sie eine gültige E-Mail-Adresse ein.', 'error');
                    return;
                }

                if (password.length < 8) {
                    showAlert('Das Passwort muss mindestens 8 Zeichen lang sein.', 'error');
                    return;
                }

                if (password !== passwordConfirm) {
                    showAlert('Die Passwörter stimmen nicht überein.', 'error');
                    return;
                }

                if (!termsAccepted) {
                    showAlert('Bitte akzeptieren Sie die AGB und Datenschutzerklärung.', 'error');
                    return;
                }

                // Submit registration to server
                submitRegistration(firstname, lastname, email, password, passwordConfirm);
            });
        }

        // Login Form
        const loginFormElement = document.getElementById('loginFormElement');
        if (loginFormElement) {
            loginFormElement.addEventListener('submit', function(e) {
                e.preventDefault();

                const email = document.getElementById('login-email').value.trim();
                const password = document.getElementById('login-password').value;

                if (!isValidEmail(email)) {
                    showAlert('Bitte geben Sie eine gültige E-Mail-Adresse ein.', 'error');
                    return;
                }

                if (!password) {
                    showAlert('Bitte geben Sie Ihr Passwort ein.', 'error');
                    return;
                }

                // Get remember me checkbox
                const remember = document.getElementById('login-remember') ? document.getElementById('login-remember').checked : false;

                // Submit login to server
                submitLogin(email, password, remember);
            });
        }

        // Helper Functions
        function openModal() {
            authModal.classList.add('active');
            authModalOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            authModal.classList.remove('active');
            authModalOverlay.classList.remove('active');
            document.body.style.overflow = '';
        }

        function showLogin() {
            loginForm.classList.add('active');
            registerForm.classList.remove('active');
        }

        function showRegister() {
            registerForm.classList.add('active');
            loginForm.classList.remove('active');
        }

        function checkPasswordStrength(password) {
            if (password.length < 8) return 'weak';

            let strength = 0;
            if (password.length >= 12) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;

            if (strength <= 2) return 'weak';
            if (strength <= 4) return 'medium';
            return 'strong';
        }

        function isValidEmail(email) {
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        }

        function showAlert(message, type) {
            const alert = document.createElement('div');
            alert.className = 'custom-alert alert-' + type;
            alert.textContent = message;
            alert.style.cssText = `
                position: fixed;
                top: 100px;
                right: 20px;
                background: ${type === 'error' ? '#f44336' : '#4caf50'};
                color: white;
                padding: 15px 25px;
                border-radius: 10px;
                box-shadow: 0 5px 25px rgba(0, 0, 0, 0.2);
                z-index: 20000;
                animation: slideIn 0.3s ease-out;
                max-width: 400px;
            `;

            document.body.appendChild(alert);

            setTimeout(function() {
                alert.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(function() {
                    alert.remove();
                }, 300);
            }, 4000);
        }

        // AJAX Functions
        function submitLogin(email, password, remember) {
            fetch('/api/login.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    email: email,
                    password: password,
                    remember: remember
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    setTimeout(function() {
                        closeModal();
                        loginFormElement.reset();
                        // Reload page to update user interface
                        window.location.reload();
                    }, 1500);
                } else {
                    showAlert(data.message, 'error');
                }
            })
            .catch(error => {
                showAlert('Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.', 'error');
            });
        }

        function submitRegistration(firstname, lastname, email, password, passwordConfirm) {
            fetch('/api/register.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    firstname: firstname,
                    lastname: lastname,
                    email: email,
                    password: password,
                    password_confirm: passwordConfirm
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    setTimeout(function() {
                        closeModal();
                        registerFormElement.reset();
                        // Reload page to update user interface
                        window.location.reload();
                    }, 1500);
                } else {
                    showAlert(data.message, 'error');
                }
            })
            .catch(error => {
                showAlert('Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.', 'error');
            });
        }
    } // End of initAuthModal

})();
