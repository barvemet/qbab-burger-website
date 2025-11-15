// Newsletter Popup JavaScript

(function() {
    'use strict';

    // Configuration
    const POPUP_DELAY = 5000; // Show popup after 5 seconds
    const COOKIE_NAME = 'newsletter_popup_shown';
    const COOKIE_DAYS = 7; // Don't show again for 7 days

    // Wait for DOM to be ready
    document.addEventListener('DOMContentLoaded', function() {
        initNewsletterPopup();
    });

    function initNewsletterPopup() {

        // Elements
        const popupOverlay = document.getElementById('newsletterPopupOverlay');
        const popupClose = document.getElementById('newsletterPopupClose');
        const popupForm = document.getElementById('newsletterPopupForm');
        const successAlert = document.getElementById('newsletterSuccessAlert');
        const errorAlert = document.getElementById('newsletterErrorAlert');

        if (!popupOverlay) {
            console.error('Newsletter popup elements not found');
            return;
        }

        // Check if popup was already shown
        if (getCookie(COOKIE_NAME)) {
            return;
        }

        // Show popup after delay
        setTimeout(function() {
            showPopup();
        }, POPUP_DELAY);

        // Close popup
        popupClose.addEventListener('click', closePopup);
        popupOverlay.addEventListener('click', function(e) {
            if (e.target === popupOverlay) {
                closePopup();
            }
        });

        // ESC key to close
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && popupOverlay.classList.contains('active')) {
                closePopup();
            }
        });

        // Form submission
        popupForm.addEventListener('submit', function(e) {
            e.preventDefault();
            handleSubscribe();
        });

        function showPopup() {
            popupOverlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closePopup() {
            popupOverlay.classList.remove('active');
            document.body.style.overflow = '';
            // Set cookie to not show again
            setCookie(COOKIE_NAME, 'true', COOKIE_DAYS);
        }

        function handleSubscribe() {
            const emailInput = document.getElementById('newsletter-email');
            const privacyCheckbox = document.getElementById('newsletter-privacy');
            const submitBtn = popupForm.querySelector('.newsletter-submit-btn');

            const email = emailInput.value.trim();

            // Hide previous alerts
            successAlert.style.display = 'none';
            errorAlert.style.display = 'none';

            // Validate
            if (!email) {
                showError('Bitte geben Sie Ihre E-Mail-Adresse ein.');
                return;
            }

            if (!isValidEmail(email)) {
                showError('Bitte geben Sie eine gültige E-Mail-Adresse ein.');
                return;
            }

            if (!privacyCheckbox.checked) {
                showError('Bitte akzeptieren Sie die Datenschutzerklärung.');
                return;
            }

            // Disable submit button
            submitBtn.disabled = true;
            submitBtn.textContent = 'Wird gesendet...';

            // Submit to API
            fetch('/api/newsletter-subscribe.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    email: email
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccess(data.message);
                    popupForm.reset();
                    // Close popup after 3 seconds
                    setTimeout(function() {
                        closePopup();
                    }, 3000);
                } else {
                    showError(data.message);
                }
            })
            .catch(error => {
                console.error('Newsletter subscription error:', error);
                showError('Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.');
            })
            .finally(() => {
                // Re-enable submit button
                submitBtn.disabled = false;
                submitBtn.textContent = 'Subscribe';
            });
        }

        function showSuccess(message) {
            successAlert.textContent = message;
            successAlert.style.display = 'block';
            errorAlert.style.display = 'none';
        }

        function showError(message) {
            errorAlert.textContent = message;
            errorAlert.style.display = 'block';
            successAlert.style.display = 'none';
        }

        function isValidEmail(email) {
            const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return regex.test(email);
        }

    }

    // Cookie helper functions
    function setCookie(name, value, days) {
        let expires = '';
        if (days) {
            const date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = '; expires=' + date.toUTCString();
        }
        document.cookie = name + '=' + (value || '') + expires + '; path=/';
    }

    function getCookie(name) {
        const nameEQ = name + '=';
        const ca = document.cookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }

})();
