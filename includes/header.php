<?php
// Header template
if (!defined('ALLOW_INCLUDE')) {
    define('ALLOW_INCLUDE', true);
}

require_once __DIR__ . '/config.php';

// Get site settings from database (with error handling)
$settings = [];
$logo_path = '';
try {
    $db = getDBConnection();
    $stmt = $db->query("SELECT setting_key, setting_value FROM site_settings");
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    $logo_path = !empty($settings['logo_path']) ? UPLOADS_URL . '/logo/' . $settings['logo_path'] : '';
} catch (Exception $e) {
    // Fail silently - site will work without settings
    error_log('Header: Failed to load site settings: ' . $e->getMessage());
}

$current_lang = getCurrentLanguage();
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <!-- SEO Meta Tags -->
    <title><?php 
        echo isset($page_title) ? $page_title . ' - ' : ''; 
        echo 'Q-Bab Burger';
        try {
            echo ' | ' . t('home_hero_subtitle');
        } catch (Exception $e) {
            error_log('FATAL ERROR in t(\'home_hero_subtitle\'): ' . $e->getMessage());
            // Echo nothing more to the title to prevent further issues.
        }
    ?></title>
    <meta name="description" content="<?php echo t('home_about_text'); ?>">
    <meta name="keywords" content="burger, kebab, restaurant, Adelzhausen, Germany, Q-Bab, fast food, delivery">
    <meta name="author" content="Q-Bab Burger">
    
    <!-- Open Graph / Social Media -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="Q-Bab Burger - <?php echo t('home_hero_subtitle'); ?>">
    <meta property="og:description" content="<?php echo t('home_about_text'); ?>">
    <meta property="og:url" content="<?php echo SITE_URL; ?>">
    <?php if ($logo_path): ?>
    <meta property="og:image" content="<?php echo $logo_path; ?>">
    <?php endif; ?>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo ASSETS_URL; ?>/images/favicon.png">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/style-modern.css?v=<?php echo ASSET_VERSION; ?>">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/animations.css?v=<?php echo ASSET_VERSION; ?>">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    
    <!-- Additional page-specific head content -->
    <?php if (isset($additional_head)): echo $additional_head; endif; ?>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <nav class="nav-container">
            <!-- Logo -->
            <a href="index.php" class="logo">
                <?php if ($logo_path): ?>
                    <img src="<?php echo $logo_path; ?>" alt="Q-Bab Burger Logo">
                <?php else: ?>
                    Q-Bab Burger
                <?php endif; ?>
            </a>
            
            <!-- Mobile Toggle -->
            <button class="mobile-toggle" aria-label="Toggle menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            
            <!-- Navigation Menu -->
            <ul class="nav-menu">
                <li><a href="index.php"><?php echo t('nav_home'); ?></a></li>
                <li><a href="menu.php"><?php echo t('nav_menu'); ?></a></li>
                <li><a href="about.php"><?php echo t('nav_about'); ?></a></li>
                <li><a href="locations.php"><?php echo t('nav_locations'); ?></a></li>
                <li><a href="contact.php"><?php echo t('nav_contact'); ?></a></li>
                <li><a href="faq.php"><?php echo t('nav_faq'); ?></a></li>

                <!-- Language Switcher -->
                <li class="lang-switcher">
                    <button class="lang-btn <?php echo $current_lang === 'en' ? 'active' : ''; ?>" data-lang="en">EN</button>
                    <button class="lang-btn <?php echo $current_lang === 'de' ? 'active' : ''; ?>" data-lang="de">DE</button>
                    <button class="lang-btn <?php echo $current_lang === 'tr' ? 'active' : ''; ?>" data-lang="tr">TR</button>
                </li>
            </ul>

            <!-- Header Right Icons -->
            <div class="header-icons">
                <!-- Login/Register Icon -->
                <?php if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in']): ?>
                    <a href="profile.php" class="header-icon login-icon" title="<?php echo t('nav_profile') ?: 'Profile'; ?>">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </a>
                <?php else: ?>
                    <button type="button" class="header-icon login-icon auth-modal-trigger" title="Login / Register">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </button>
                <?php endif; ?>

                <!-- Cart Icon with Badge and Popup -->
                <div class="cart-dropdown-wrapper">
                    <button class="header-icon cart-icon-header" id="cartIconBtn" title="<?php echo t('cart_title') ?: 'Shopping Cart'; ?>">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="9" cy="21" r="1"></circle>
                            <circle cx="20" cy="21" r="1"></circle>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                        </svg>
                        <span class="cart-badge">0</span>
                    </button>

                    <!-- Cart Popup -->
                    <div class="cart-popup" id="cartPopup">
                        <div class="cart-popup-items" id="cartPopupItems">
                            <!-- Items will be added by JavaScript -->
                            <div class="cart-popup-empty">
                                <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <circle cx="9" cy="21" r="1"></circle>
                                    <circle cx="20" cy="21" r="1"></circle>
                                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                                </svg>
                                <p><?php echo t('cart_empty') ?: 'Your cart is empty'; ?></p>
                            </div>
                        </div>

                        <div class="cart-popup-footer">
                            <div class="cart-popup-subtotal">
                                <span><?php echo t('cart_subtotal') ?: 'Subtotal'; ?>:</span>
                                <span class="cart-popup-total">$0.00</span>
                            </div>
                            <div class="cart-popup-buttons">
                                <a href="cart.php" class="cart-popup-btn view-cart-btn">
                                    <?php echo t('cart_view_cart') ?: 'VIEW CART'; ?>
                                </a>
                                <button onclick="handleCheckoutFromPopup()" class="cart-popup-btn checkout-btn">
                                    <?php echo t('cart_checkout') ?: 'CHECKOUT'; ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hamburger Menu (3x3 dots) -->
                <button class="header-icon menu-dots-btn" aria-label="Open Menu">
                    <div class="dots-grid">
                        <span class="dot"></span>
                        <span class="dot"></span>
                        <span class="dot"></span>
                        <span class="dot"></span>
                        <span class="dot"></span>
                        <span class="dot"></span>
                        <span class="dot"></span>
                        <span class="dot"></span>
                        <span class="dot"></span>
                    </div>
                </button>
            </div>
        </nav>
    </header>

    <!-- Sidebar Menu Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar Menu -->
    <aside class="sidebar-menu" id="sidebarMenu">
        <!-- Sidebar Header -->
        <div class="sidebar-header">
            <div class="sidebar-logo">
                <?php if ($logo_path): ?>
                    <img src="<?php echo $logo_path; ?>" alt="Q-Bab Burger">
                <?php else: ?>
                    <h2>Q-Bab</h2>
                <?php endif; ?>
            </div>
            <button class="sidebar-close" id="sidebarClose" aria-label="Close Menu">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        <!-- Sidebar Content -->
        <div class="sidebar-content">
            <!-- Social Media Links -->
            <div class="sidebar-social">
                <a href="https://facebook.com" class="social-link" target="_blank" rel="noopener">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                    <span>Facebook</span>
                </a>

                <a href="https://twitter.com" class="social-link" target="_blank" rel="noopener">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                    </svg>
                    <span>Twitter</span>
                </a>

                <a href="https://dribbble.com" class="social-link" target="_blank" rel="noopener">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 0C5.37 0 0 5.37 0 12s5.37 12 12 12 12-5.37 12-12S18.63 0 12 0zm7.77 6.88c1.37 1.68 2.19 3.81 2.22 6.12-.41-.09-4.46-.92-8.48-.4-.09-.21-.17-.42-.27-.64-.26-.63-.55-1.26-.85-1.88 4.39-1.79 6.2-4.37 6.38-4.6zM12 2.04c2.62 0 5.02 1.01 6.81 2.66-.15.2-1.78 2.61-5.98 4.19-1.88-3.45-3.96-6.26-4.27-6.68.79-.11 1.6-.17 2.44-.17zM6.59 3.26c.3.41 2.34 3.23 4.25 6.62-5.36 1.43-10.08 1.4-10.59 1.39.71-3.55 3.28-6.47 6.34-8.01zM2 12.01v-.32c.51.01 6.13.08 11.85-1.64.36.7.7 1.41 1.02 2.12-.15.04-.3.09-.44.13-5.96 1.92-9.14 7.18-9.35 7.52A9.964 9.964 0 012 12.01zm10 9.95c-2.32 0-4.45-.79-6.14-2.12.16-.33 2.61-5.12 9.18-7.39l.05-.02c1.54 4.01 2.17 7.37 2.33 8.31-1.66.84-3.54 1.22-5.42 1.22zm7.47-1.8c-.12-.73-.68-3.96-2.14-7.93 3.77-.6 7.07.38 7.46.49-.49 3.29-2.26 6.12-5.32 7.44z"/>
                    </svg>
                    <span>Dribbble</span>
                </a>

                <a href="https://instagram.com" class="social-link" target="_blank" rel="noopener">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                    </svg>
                    <span>Instagram</span>
                </a>
            </div>

            <!-- Contact Info -->
            <div class="sidebar-contact">
                <div class="contact-item">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                    </svg>
                    <span>+1 840 841 25 69</span>
                </div>

                <div class="contact-item">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                    <span>info@email.com</span>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
    
    <!-- Pass translations to JavaScript -->
    <script>
        const translations = <?php echo getTranslationsJSON($current_lang); ?>;

        // Cart Popup Toggle - Inline to ensure it works immediately
        (function() {
            function initCartPopup() {
                const cartIcon = document.getElementById('cartIconBtn');
                const popup = document.getElementById('cartPopup');

                if (cartIcon && popup) {
                    cartIcon.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        popup.classList.toggle('active');

                        // Update popup content if function exists
                        if (typeof updateCartPopup === 'function') {
                            updateCartPopup();
                        }
                    });

                    // Close when clicking outside
                    document.addEventListener('click', function(e) {
                        const wrapper = document.querySelector('.cart-dropdown-wrapper');
                        if (wrapper && !wrapper.contains(e.target)) {
                            popup.classList.remove('active');
                        }
                    });
                }
            }

            // Run when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initCartPopup);
            } else {
                initCartPopup();
            }
        })();
    </script>