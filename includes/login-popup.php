<!-- Login/Register Popup Modal -->
<div class="auth-modal-overlay" id="authModalOverlay"></div>

<div class="auth-modal" id="authModal">
    <button class="auth-modal-close" id="authModalClose" aria-label="Close">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <line x1="18" y1="6" x2="6" y2="18"></line>
            <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
    </button>

    <div class="auth-modal-container">
        <!-- Login Form -->
        <div class="auth-form-wrapper login-form active" id="loginForm">
            <div class="auth-header">
                <h2>Willkommen zurück!</h2>
                <p>Melden Sie sich bei Ihrem Konto an</p>
            </div>

            <form class="auth-form" id="loginFormElement" novalidate>
                <!-- Email -->
                <div class="form-group">
                    <label for="login-email">E-Mail</label>
                    <input
                        type="email"
                        id="login-email"
                        name="email"
                        placeholder="ihre-email@beispiel.de"
                        required
                    >
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label for="login-password">Passwort</label>
                    <div class="password-input-wrapper">
                        <input
                            type="password"
                            id="login-password"
                            name="password"
                            placeholder="Ihr Passwort"
                            required
                        >
                        <button type="button" class="password-toggle" data-target="login-password" aria-label="Passwort anzeigen/verbergen" title="Passwort anzeigen">
                            <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg class="eye-off-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="form-extras">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember">
                        <span>Angemeldet bleiben</span>
                    </label>
                    <a href="#" class="forgot-password">Passwort vergessen?</a>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="auth-submit-btn">
                    Anmelden
                </button>

                <!-- Social Login -->
                <div class="social-login-divider">
                    <span>oder anmelden mit</span>
                </div>

                <div class="social-login-buttons">
                    <button type="button" class="social-login-btn google-btn">
                        <svg width="18" height="18" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        Google
                    </button>

                    <button type="button" class="social-login-btn apple-btn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M17.05 20.28c-.98.95-2.05.88-3.08.4-1.09-.5-2.08-.48-3.24 0-1.44.62-2.2.44-3.06-.4C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.54 4.09l.01-.01zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z"/>
                        </svg>
                        Apple
                    </button>
                </div>

                <!-- Switch to Register -->
                <div class="auth-switch">
                    <p>Noch kein Konto? <a href="#" class="switch-link" id="switchToRegister">Jetzt registrieren</a></p>
                </div>
            </form>
        </div>

        <!-- Register Form -->
        <div class="auth-form-wrapper register-form" id="registerForm">
            <div class="auth-header">
                <h2>Konto erstellen</h2>
                <p>Registrieren Sie sich für ein neues Konto</p>
            </div>

            <form class="auth-form" id="registerFormElement" novalidate>
                <!-- First Name -->
                <div class="form-group">
                    <label for="register-firstname">Vorname *</label>
                    <input
                        type="text"
                        id="register-firstname"
                        name="firstname"
                        placeholder="Max"
                        required
                    >
                </div>

                <!-- Last Name -->
                <div class="form-group">
                    <label for="register-lastname">Nachname *</label>
                    <input
                        type="text"
                        id="register-lastname"
                        name="lastname"
                        placeholder="Mustermann"
                        required
                    >
                </div>

                <!-- Email -->
                <div class="form-group">
                    <label for="register-email">E-Mail *</label>
                    <input
                        type="email"
                        id="register-email"
                        name="email"
                        placeholder="ihre-email@beispiel.de"
                        required
                    >
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label for="register-password">Passwort * (min. 8 Zeichen)</label>
                    <div class="password-input-wrapper">
                        <input
                            type="password"
                            id="register-password"
                            name="password"
                            placeholder="Mindestens 8 Zeichen"
                            minlength="8"
                            required
                        >
                        <button type="button" class="password-toggle" data-target="register-password" aria-label="Passwort anzeigen/verbergen" title="Passwort anzeigen">
                            <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg class="eye-off-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                            </svg>
                        </button>
                    </div>
                    <small class="password-strength" id="passwordStrength"></small>
                </div>

                <!-- Confirm Password -->
                <div class="form-group">
                    <label for="register-password-confirm">Passwort bestätigen *</label>
                    <div class="password-input-wrapper">
                        <input
                            type="password"
                            id="register-password-confirm"
                            name="password_confirm"
                            placeholder="Passwort wiederholen"
                            minlength="8"
                            required
                        >
                        <button type="button" class="password-toggle" data-target="register-password-confirm" aria-label="Passwort anzeigen/verbergen" title="Passwort anzeigen">
                            <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg class="eye-off-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;">
                                <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path>
                                <line x1="1" y1="1" x2="23" y2="23"></line>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Terms & Privacy -->
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="terms" required>
                        <span>Ich akzeptiere die <a href="#" target="_blank">AGB</a> und <a href="#" target="_blank">Datenschutzerklärung</a></span>
                    </label>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="auth-submit-btn">
                    Registrieren
                </button>

                <!-- Social Login -->
                <div class="social-login-divider">
                    <span>oder registrieren mit</span>
                </div>

                <div class="social-login-buttons">
                    <button type="button" class="social-login-btn google-btn">
                        <svg width="18" height="18" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        Google
                    </button>

                    <button type="button" class="social-login-btn apple-btn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M17.05 20.28c-.98.95-2.05.88-3.08.4-1.09-.5-2.08-.48-3.24 0-1.44.62-2.2.44-3.06-.4C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.54 4.09l.01-.01zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z"/>
                        </svg>
                        Apple
                    </button>
                </div>

                <!-- Switch to Login -->
                <div class="auth-switch">
                    <p>Haben Sie bereits ein Konto? <a href="#" class="switch-link" id="switchToLogin">Jetzt anmelden</a></p>
                </div>
            </form>
        </div>
    </div>
</div>
