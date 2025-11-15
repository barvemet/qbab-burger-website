<!-- Transparent Navbar -->
<nav class="top-navbar">
    <!-- Site Logo -->
    <a href="<?php echo SITE_URL; ?>" class="site-logo">
        <img src="<?php echo ASSETS_URL; ?>/images/logo.png" alt="Q-Bab Burger Logo">
    </a>
    
    <ul class="nav-menu">
        <li><a href="<?php echo SITE_URL; ?>"><?php echo t('nav_home'); ?></a></li>
        <li><a href="<?php echo SITE_URL; ?>/angebot.php"><?php echo t('nav_menu'); ?></a></li>
        <li><a href="<?php echo SITE_URL; ?>/menu.php"><?php echo t('nav_order'); ?></a></li>
        <li><a href="<?php echo SITE_URL; ?>/about.php"><?php echo t('nav_about'); ?></a></li>
        <li><a href="<?php echo SITE_URL; ?>/locations.php"><?php echo t('nav_locations'); ?></a></li>
        <li><a href="<?php echo SITE_URL; ?>/contact.php"><?php echo t('nav_contact'); ?></a></li>
        <li><a href="<?php echo SITE_URL; ?>/faq.php"><?php echo t('nav_faq'); ?></a></li>
        <li class="language-switcher">
            <a href="?lang=de" class="<?php echo getCurrentLanguage() == 'de' ? 'active' : ''; ?>">DE</a>
            <a href="?lang=en" class="<?php echo getCurrentLanguage() == 'en' ? 'active' : ''; ?>">EN</a>
            <a href="?lang=tr" class="<?php echo getCurrentLanguage() == 'tr' ? 'active' : ''; ?>">TR</a>
        </li>
    </ul>

    <!-- Hamburger Menu Button (Mobile Only) -->
    <button class="menu-dots-btn" aria-label="Menu" onclick="var menu = document.getElementById('mobileMenuSidebar'); var overlay = document.getElementById('mobileMenuOverlay'); menu.style.left = '0px'; menu.style.zIndex = '99999999'; menu.style.display = 'flex'; menu.style.position = 'fixed'; overlay.style.opacity = '1'; overlay.style.visibility = 'visible'; overlay.style.zIndex = '99999998'; overlay.style.pointerEvents = 'auto'; document.body.style.overflow = 'hidden';">
        <span class="line"></span>
        <span class="line"></span>
        <span class="line"></span>
    </button>

    <!-- Header Right Icons -->
    <div class="header-icons">
        <?php if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in']): ?>
        <!-- Profile Dropdown (Logged In) -->
        <div class="profile-dropdown">
            <button class="header-icon profile-icon" title="<?php echo htmlspecialchars($_SESSION['user_firstname'] . ' ' . $_SESSION['user_lastname']); ?>">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
            </button>
            <div class="profile-dropdown-menu">
                <div class="profile-user-info">
                    <strong><?php echo htmlspecialchars($_SESSION['user_firstname'] . ' ' . $_SESSION['user_lastname']); ?></strong>
                    <span><?php echo htmlspecialchars($_SESSION['user_email']); ?></span>
                </div>
                <a href="profile.php" class="profile-menu-item">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    Mein Profil
                </a>
                <a href="cart.php" class="profile-menu-item">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="9" cy="21" r="1"></circle>
                        <circle cx="20" cy="21" r="1"></circle>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                    </svg>
                    Meine Bestellungen
                </a>
                <a href="javascript:void(0);" class="profile-menu-item logout-btn">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    Abmelden
                </a>
            </div>
        </div>
        <?php else: ?>
        <!-- Login/Register Icon (Not Logged In) -->
        <a href="javascript:void(0);" class="header-icon login-icon auth-modal-trigger" title="Login / Register">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
        </a>
        <?php endif; ?>

        <!-- Instagram Icon -->
        <a href="https://www.instagram.com/qbabburger" class="header-icon instagram-icon" target="_blank" rel="noopener" title="Instagram">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
            </svg>
        </a>

        <!-- Cart Icon - Direct Link to Cart Page -->
        <a href="cart.php" class="header-icon cart-icon-header" id="cartIconBtn" title="<?php echo t('cart_title') ?: 'Shopping Cart'; ?>">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="9" cy="21" r="1"></circle>
                <circle cx="20" cy="21" r="1"></circle>
                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
            </svg>
            <span class="cart-badge">0</span>
        </a>
    </div>
</nav>

<!-- Mobile Hamburger Menu (NEW) -->
<div class="mobile-menu-overlay" id="mobileMenuOverlay" style="z-index: 10000000 !important; position: fixed !important; opacity: 0 !important; visibility: hidden !important; pointer-events: none !important; top: 0 !important; left: 0 !important; width: 100vw !important; height: 100vh !important; background: rgba(0,0,0,0.7) !important; transition: opacity 0.3s ease !important;" onclick="var menu = document.getElementById('mobileMenuSidebar'); var overlay = document.getElementById('mobileMenuOverlay'); menu.style.left = '-100%'; overlay.style.opacity = '0'; overlay.style.visibility = 'hidden'; overlay.style.pointerEvents = 'none'; document.body.style.overflow = '';"></div>
<div class="mobile-menu-sidebar" id="mobileMenuSidebar" style="z-index: 10000001 !important; position: fixed !important; background: #1a1a1a !important; left: -100% !important; top: 0 !important; width: 280px !important; max-width: 80vw !important; height: 100vh !important; display: flex !important; flex-direction: column !important; transition: left 0.3s ease !important; overflow-y: auto !important;">
    <div class="sidebar-header">
        <a href="<?php echo SITE_URL; ?>" class="sidebar-logo">
            <img src="<?php echo ASSETS_URL; ?>/images/logo.png" alt="Q-Bab Burger">
        </a>
        <button class="sidebar-close" id="sidebarClose" aria-label="Close Menu" onclick="var menu = document.getElementById('mobileMenuSidebar'); var overlay = document.getElementById('mobileMenuOverlay'); menu.style.left = '-100%'; overlay.style.opacity = '0'; overlay.style.visibility = 'hidden'; overlay.style.pointerEvents = 'none'; document.body.style.overflow = '';">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    </div>

    <ul class="sidebar-nav">
        <li><a href="<?php echo SITE_URL; ?>"><?php echo t('nav_home'); ?></a></li>
        <li><a href="<?php echo SITE_URL; ?>/angebot.php"><?php echo t('nav_menu'); ?></a></li>
        <li><a href="<?php echo SITE_URL; ?>/menu.php"><?php echo t('nav_order'); ?></a></li>
        <li><a href="<?php echo SITE_URL; ?>/about.php"><?php echo t('nav_about'); ?></a></li>
        <li><a href="<?php echo SITE_URL; ?>/locations.php"><?php echo t('nav_locations'); ?></a></li>
        <li><a href="<?php echo SITE_URL; ?>/contact.php"><?php echo t('nav_contact'); ?></a></li>
        <li><a href="<?php echo SITE_URL; ?>/faq.php"><?php echo t('nav_faq'); ?></a></li>
    </ul>

    <!-- User & Actions Section -->
    <div class="sidebar-actions">
        <?php if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in']): ?>
            <div class="sidebar-user-info">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <div>
                    <strong><?php echo htmlspecialchars($_SESSION['user_firstname'] . ' ' . $_SESSION['user_lastname']); ?></strong>
                    <span><?php echo htmlspecialchars($_SESSION['user_email']); ?></span>
                </div>
            </div>
            <a href="profile.php" class="sidebar-action-btn">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                Mein Profil
            </a>
            <a href="cart.php" class="sidebar-action-btn">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="9" cy="21" r="1"></circle>
                    <circle cx="20" cy="21" r="1"></circle>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                </svg>
                Meine Bestellungen
            </a>
            <a href="javascript:void(0);" class="sidebar-action-btn sidebar-logout">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                Abmelden
            </a>
        <?php else: ?>
            <a href="javascript:void(0);" class="sidebar-action-btn sidebar-login" onclick="var menu = document.getElementById('mobileMenuSidebar'); var overlay = document.getElementById('mobileMenuOverlay'); menu.style.left = '-100%'; overlay.style.opacity = '0'; overlay.style.visibility = 'hidden'; overlay.style.pointerEvents = 'none'; document.body.style.overflow = ''; setTimeout(function() { document.getElementById('authModal').classList.add('active'); document.getElementById('authModalOverlay').classList.add('active'); document.getElementById('loginForm').classList.add('active'); document.getElementById('registerForm').classList.remove('active'); document.body.style.overflow='hidden'; }, 300);">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                Login / Registrieren
            </a>
        <?php endif; ?>

        <a href="https://www.instagram.com/qbabburger" class="sidebar-action-btn" target="_blank" rel="noopener">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
            </svg>
            Instagram
        </a>

        <a href="cart.php" class="sidebar-action-btn sidebar-cart">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="9" cy="21" r="1"></circle>
                <circle cx="20" cy="21" r="1"></circle>
                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
            </svg>
            Warenkorb
            <span class="cart-badge sidebar-cart-badge">0</span>
        </a>
    </div>

    <div class="sidebar-language">
        <p>Language / Sprache</p>
        <div class="sidebar-lang-buttons">
            <a href="?lang=de" class="<?php echo getCurrentLanguage() == 'de' ? 'active' : ''; ?>">Deutsch</a>
            <a href="?lang=en" class="<?php echo getCurrentLanguage() == 'en' ? 'active' : ''; ?>">English</a>
            <a href="?lang=tr" class="<?php echo getCurrentLanguage() == 'tr' ? 'active' : ''; ?>">Türkçe</a>
        </div>
    </div>
</div>

<?php
// Include Login/Register Popup
include __DIR__ . '/login-popup.php';
?>
