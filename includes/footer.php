    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-content">
                <!-- Left Side - Slogan -->
                <div class="footer-left">
                    <h2><?php echo t('footer_slogan'); ?></h2>
                </div>

                <!-- Middle - Address -->
                <div class="footer-middle">
                    <h3><?php echo t('footer_address_title'); ?></h3>
                    <p>
                        Deutschland —<br>
                        Mühlweg 1<br>
                        86559 Adelzhausen
                    </p>
                </div>

                <!-- Right - Contact -->
                <div class="footer-right">
                    <h3><?php echo t('footer_say_hello'); ?></h3>
                    <p>
                        <a href="mailto:info@q-bab.de">info@q-bab.de</a><br><br>
                        <a href="tel:<?php echo ADMIN_PHONE; ?>" class="footer-phone">0152 / 05 700 600</a>
                    </p>

                    <!-- Social Icons -->
                    <div class="footer-social">
                        <a href="#" class="social-icon" aria-label="Facebook">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                        <a href="#" class="social-icon" aria-label="Twitter">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                        <a href="#" class="social-icon" aria-label="Dribbble">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                                <path d="M8.56 2.75c4.37 6.03 6.02 9.42 8.03 17.5m2.54-15.38c-3.72 4.35-8.94 5.66-16.88 5.85m19.5 1.9c-3.5-.93-6.63-.82-8.94 0-2.58.92-5.01 2.86-7.44 6.32" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </a>
                        <a href="#" class="social-icon" aria-label="Instagram">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect x="2" y="2" width="20" height="20" rx="5" stroke="currentColor" stroke-width="2"/>
                                <circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="2"/>
                                <circle cx="17.5" cy="6.5" r="1.5" fill="currentColor"/>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Footer Bottom -->
            <div class="footer-bottom">
                <div class="footer-bottom-content">
                    <div class="footer-links">
                        <a href="<?php echo SITE_URL; ?>/about.php"><?php echo t('footer_about_us'); ?></a>
                        <a href="<?php echo SITE_URL; ?>/contact.php"><?php echo t('footer_contacts'); ?></a>
                    </div>
                    <div class="footer-copyright">
                        <p>Kaan KOC © <?php echo date('Y'); ?>. <?php echo t('footer_rights'); ?>.</p>
                    </div>
                    <!-- Scroll to Top Button in Footer -->
                    <a href="#" class="footer-scroll-top" aria-label="Scroll to top">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 19V5M5 12l7-7 7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="<?php echo ASSETS_URL; ?>/js/main.js?v=<?php echo ASSET_VERSION; ?>"></script>
    <script src="<?php echo ASSETS_URL; ?>/js/animations.js?v=<?php echo ASSET_VERSION; ?>"></script>

    <!-- Newsletter Popup -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/newsletter-popup.css?v=<?php echo ASSET_VERSION; ?>">
    <?php include __DIR__ . '/newsletter-popup.php'; ?>
    <script src="<?php echo ASSETS_URL; ?>/js/newsletter-popup.js?v=<?php echo ASSET_VERSION; ?>"></script>

    <!-- Additional page-specific scripts -->
    <?php if (isset($additional_scripts)): echo $additional_scripts; endif; ?>
</body>
</html>
