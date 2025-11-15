<!-- Newsletter Popup Modal -->
<div class="newsletter-popup-overlay" id="newsletterPopupOverlay">
    <div class="newsletter-popup">
        <!-- Close Button -->
        <button class="newsletter-popup-close" id="newsletterPopupClose" aria-label="Close">
            <svg viewBox="0 0 24 24" fill="none" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>

        <!-- Left Side - Image -->
        <div class="newsletter-popup-image"></div>

        <!-- Right Side - Form -->
        <div class="newsletter-popup-content">
            <!-- Alert Messages -->
            <div class="newsletter-alert success" id="newsletterSuccessAlert" style="display: none;">
                Vielen Dank! Ihre E-Mail wurde erfolgreich registriert.
            </div>
            <div class="newsletter-alert error" id="newsletterErrorAlert" style="display: none;">
                Es ist ein Fehler aufgetreten. Bitte versuchen Sie es erneut.
            </div>

            <h2 class="newsletter-popup-title">Subscribe for the Updates!</h2>
            <p class="newsletter-popup-subtitle">
                Bleiben Sie auf dem Laufenden mit unseren neuesten Angeboten, Menü-Updates und exklusiven Rabatten. Melden Sie sich jetzt für unseren Newsletter an!
            </p>

            <form class="newsletter-popup-form" id="newsletterPopupForm">
                <!-- Email Input -->
                <div class="newsletter-input-wrapper">
                    <svg viewBox="0 0 24 24" fill="none" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                    <input
                        type="email"
                        class="newsletter-popup-input"
                        id="newsletter-email"
                        name="email"
                        placeholder="Enter Your Email Address"
                        required
                    >
                </div>

                <!-- Privacy Checkbox -->
                <div class="newsletter-checkbox-wrapper">
                    <input
                        type="checkbox"
                        id="newsletter-privacy"
                        name="privacy"
                        required
                    >
                    <label for="newsletter-privacy">
                        Ich stimme der <a href="#" target="_blank">Datenschutzerklärung</a> zu.
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="newsletter-submit-btn">
                    Subscribe
                </button>
            </form>
        </div>
    </div>
</div>
