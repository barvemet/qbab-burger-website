<?php
/**
 * Q-Bab Burger - Home Page
 * Modern design with animated header
 */

// Start session first if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle language change via URL parameter
if (isset($_GET['lang']) && in_array($_GET['lang'], ['de', 'en', 'tr'])) {
    $_SESSION['language'] = $_GET['lang'];
    // Force save session
    session_write_close();
    session_start();
    // Redirect to clean URL without parameters
    header('Location: https://www.q-bab.de/index.php');
    exit;
}

// Debug: Show current language (remove this in production)
// echo "Current Language: " . getCurrentLanguage() . "<br>";

// Set page title for SEO
$page_title = 'Home';

// Include header template
include 'includes/header.php';

// Get popular items from database
$popularItems = [];
try {
    $db = getDBConnection();
    $stmt = $db->query("
        SELECT * FROM menu_items
        WHERE is_active = 1 AND is_popular = 1
        ORDER BY display_order
        LIMIT 4
    ");
    $popularItems = $stmt->fetchAll();
} catch (Exception $e) {
    // Fallback to empty array if error
    $popularItems = [];
}
?>

<!-- Login/Register Popup CSS -->
<link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/auth-modal.css?v=<?php echo ASSET_VERSION; ?>">

<!-- Hero Header Section -->
<style>
@import url('https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap');

/* Hide Navbar */
.header {
    display: none !important;
}

body {
    padding-top: 0 !important;
}

/* Prevent horizontal scroll on all devices */
html, body {
    max-width: 100%;
    overflow-x: hidden;
}

* {
    box-sizing: border-box;
}

/* Logo inside navbar */
.site-logo {
    position: absolute;
    left: 30px;
    top: 50%;
    transform: translateY(-50%);
    z-index: 101;
    opacity: 0;
    animation: fadeInLogo 1s ease-out 0.5s forwards;
    transition: all 0.3s ease;
}

.site-logo img {
    height: 70px;
    width: auto;
    display: block;
    filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.4)) drop-shadow(0 2px 4px rgba(0, 0, 0, 0.3));
    transition: all 0.3s ease;
}

.top-navbar.scrolled .site-logo img {
    height: 50px;
}

.site-logo:hover img {
    filter: drop-shadow(0 6px 12px rgba(0, 0, 0, 0.5)) drop-shadow(0 3px 6px rgba(0, 0, 0, 0.4));
    transform: scale(1.05);
}

@keyframes fadeInLogo {
    to {
        opacity: 1;
    }
}

/* Transparent Navbar */
.top-navbar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 30px 50px 30px 150px;
    z-index: 1000;
    opacity: 0;
    animation: fadeInLogo 1s ease-out 0.5s forwards;
    background: transparent;
    transition: all 0.3s ease;
    min-height: 100px;
}

.top-navbar.scrolled {
    background: rgba(0, 0, 0, 0.95);
    padding: 15px 50px 15px 150px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    min-height: 70px;
}

.nav-menu {
    display: flex;
    align-items: center;
    gap: 25px;
    list-style: none;
    margin: 0;
    margin-right: auto;
    padding: 0;
}

.nav-menu li {
    position: relative;
}

.nav-menu a {
    color: #ffffff;
    text-decoration: none;
    font-family: 'Bebas Neue', 'Arial Narrow', sans-serif;
    font-size: 1.1rem;
    letter-spacing: 1px;
    text-transform: uppercase;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    transition: all 0.3s ease;
    position: relative;
}

.nav-menu a::after {
    content: '';
    position: absolute;
    bottom: -5px;
    left: 0;
    width: 0;
    height: 2px;
    background: #e10000;
    transition: width 0.3s ease;
}

.nav-menu a:hover {
    color: #e10000;
    text-shadow: 0 3px 6px rgba(0, 0, 0, 0.4);
    transform: translateY(-2px);
}

.nav-menu a:hover::after {
    width: 100%;
}

/* Language Switcher */
.language-switcher {
    display: flex;
    gap: 8px;
    margin-left: 15px;
    padding-left: 15px;
    border-left: 2px solid rgba(255, 255, 255, 0.3);
}

.language-switcher a {
    color: #ffffff;
    text-decoration: none;
    font-family: 'Bebas Neue', 'Arial Narrow', sans-serif;
    font-size: 1.1rem;
    font-weight: 600;
    letter-spacing: 0.5px;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
    padding: 5px 10px;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.language-switcher a:hover {
    background: rgba(255, 255, 255, 0.1);
    color: #e10000;
}

.language-switcher a.active {
    background: #e10000;
    color: #ffffff;
}

/* Header Icons (Login, Cart, Menu Dots) */
.header-icons {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-left: 30px;
    opacity: 0;
    animation: fadeInLogo 1s ease-out 0.5s forwards;
}

.header-icon {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    color: #ffffff;
    background: transparent;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
}

.header-icon:hover {
    background: rgba(225, 0, 0, 0.2);
    transform: scale(1.1);
}

.header-icon svg {
    width: 20px;
    height: 20px;
}

/* Hamburger Menu Button */
.menu-dots-btn {
    display: none; /* Hidden on desktop */
    flex-direction: column;
    justify-content: center;
    align-items: center;
    gap: 5px;
    width: 44px;
    height: 44px;
    background: transparent;
    border: none;
    cursor: pointer;
    padding: 10px;
    z-index: 10000002;
    opacity: 0;
    animation: fadeInLogo 1s ease-out 0.5s forwards;
    position: relative;
}

.menu-dots-btn .line {
    width: 24px;
    height: 2px;
    background: #ffffff;
    border-radius: 2px;
    transition: all 0.3s ease;
}

.menu-dots-btn:hover .line {
    background: #e10000;
}

/* Sidebar Menu */
.sidebar-overlay {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100% !important;
    height: 100% !important;
    background: rgba(0, 0, 0, 0.7) !important;
    opacity: 0 !important;
    visibility: hidden !important;
    transition: all 0.3s ease !important;
    z-index: 10000000 !important;
    pointer-events: none !important;
}

.sidebar-overlay.active {
    opacity: 1 !important;
    visibility: visible !important;
    pointer-events: auto !important;
}

.sidebar-menu {
    position: fixed !important;
    top: 0 !important;
    right: -100% !important;
    width: 300px !important;
    max-width: 85vw !important;
    height: 100vh !important;
    background: #1a1a1a !important;
    box-shadow: -5px 0 15px rgba(0, 0, 0, 0.3) !important;
    z-index: 10000001 !important;
    overflow-y: auto !important;
    transition: right 0.3s ease !important;
    display: flex !important;
    flex-direction: column !important;
}

.sidebar-menu.active {
    right: 0 !important;
    transform: translateX(0) !important;
}

.sidebar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.sidebar-logo img {
    height: 50px;
    width: auto;
}

.sidebar-close {
    background: transparent;
    border: none;
    color: #ffffff;
    cursor: pointer;
    padding: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.sidebar-close:hover {
    color: #e10000;
    transform: rotate(90deg);
}

.sidebar-nav {
    list-style: none;
    padding: 0;
    margin: 0;
    flex: 1;
}

.sidebar-nav li {
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.sidebar-nav a {
    display: block;
    padding: 18px 20px;
    color: #ffffff;
    text-decoration: none;
    font-family: 'Bebas Neue', sans-serif;
    font-size: 1.2rem;
    letter-spacing: 1px;
    text-transform: uppercase;
    transition: all 0.3s ease;
}

.sidebar-nav a:hover {
    background: rgba(248, 150, 40, 0.1);
    color: #f89628;
    padding-left: 30px;
}

.sidebar-language {
    padding: 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.sidebar-language p {
    color: #888;
    font-size: 0.85rem;
    margin: 0 0 12px 0;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.sidebar-lang-buttons {
    display: flex;
    gap: 10px;
}

.sidebar-lang-buttons a {
    flex: 1;
    padding: 10px;
    text-align: center;
    background: rgba(255, 255, 255, 0.05);
    color: #ffffff;
    text-decoration: none;
    border-radius: 4px;
    font-family: 'Bebas Neue', sans-serif;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.sidebar-lang-buttons a:hover {
    background: rgba(248, 150, 40, 0.2);
    color: #f89628;
}

.sidebar-lang-buttons a.active {
    background: #e10000;
    color: #ffffff;
}

/* Sidebar Actions Section */
.sidebar-actions {
    padding: 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.sidebar-user-info {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 15px;
    background: rgba(248, 150, 40, 0.1);
    border-radius: 8px;
    margin-bottom: 15px;
}

.sidebar-user-info svg {
    color: #f89628;
    flex-shrink: 0;
}

.sidebar-user-info div {
    display: flex;
    flex-direction: column;
    gap: 4px;
    overflow: hidden;
}

.sidebar-user-info strong {
    color: #ffffff;
    font-size: 1rem;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.sidebar-user-info span {
    color: #888;
    font-size: 0.85rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.sidebar-action-btn {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 15px;
    margin-bottom: 8px;
    background: rgba(255, 255, 255, 0.05);
    color: #ffffff;
    text-decoration: none;
    border-radius: 6px;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    position: relative;
}

.sidebar-action-btn:hover {
    background: rgba(248, 150, 40, 0.15);
    color: #f89628;
    padding-left: 20px;
}

.sidebar-action-btn svg {
    flex-shrink: 0;
}

.sidebar-cart-badge {
    margin-left: auto;
    background: #e10000;
    color: #ffffff;
    font-size: 11px;
    font-weight: bold;
    min-width: 20px;
    height: 20px;
    border-radius: 10px;
    display: none; /* Hidden by default, shown by JS when cart has items */
    align-items: center;
    justify-content: center;
    padding: 0 6px;
}

.sidebar-logout:hover {
    background: rgba(225, 0, 0, 0.1);
    color: #e10000;
}

/* Cart Badge */
.cart-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #e10000;
    color: #ffffff;
    font-size: 12px;
    font-weight: bold;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.4);
    z-index: 9999;
    pointer-events: none;
    line-height: 1;
}

/* Cart Dropdown/Popup */
.cart-dropdown-wrapper {
    position: relative;
}

.cart-popup {
    position: fixed !important;
    top: 80px !important;
    right: 20px !important;
    width: 400px;
    background: #1a1a1a;
    border-radius: 8px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    z-index: 999999 !important;
    max-height: 500px;
    display: none; /* Hidden by default - CRITICAL! */
    flex-direction: column;
    overflow: hidden;
}

/* Mobile cart popup - responsive width */
@media (max-width: 480px) {
    .cart-popup {
        width: calc(100vw - 40px) !important;
        right: 20px !important;
        left: 20px !important;
        max-width: 350px !important;
        top: 70px !important;
    }
}

.cart-popup.active {
    display: flex !important; /* Show when active - CRITICAL! */
    opacity: 1 !important;
    visibility: visible !important;
    transform: translateY(0) !important;
}

.cart-popup::before {
    content: '';
    position: absolute;
    top: -8px;
    right: 20px;
    width: 0;
    height: 0;
    border-left: 8px solid transparent;
    border-right: 8px solid transparent;
    border-bottom: 8px solid #1a1a1a;
}

.cart-popup-items {
    max-height: 300px;
    overflow-y: auto;
    padding: 20px;
}

.cart-popup-empty {
    text-align: center;
    padding: 40px 20px;
    color: #999;
}

.cart-popup-empty svg {
    stroke: #444;
    margin-bottom: 15px;
}

.cart-popup-empty p {
    margin: 0;
    color: #999;
}

.cart-popup-item {
    display: flex;
    gap: 15px;
    padding: 15px 0;
    border-bottom: 1px solid #333;
}

.cart-popup-item:last-child {
    border-bottom: none;
}

.cart-popup-item-image {
    width: 70px;
    height: 70px;
    border-radius: 8px;
    overflow: hidden;
    flex-shrink: 0;
}

.cart-popup-item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.cart-popup-item-details {
    flex: 1;
}

.cart-popup-item-name {
    color: white;
    font-size: 0.95rem;
    font-weight: 600;
    margin-bottom: 5px;
}

.cart-popup-item-quantity {
    color: #999;
    font-size: 0.85rem;
}

.cart-popup-item-price {
    color: #ff6b35;
    font-weight: 600;
    font-size: 1rem;
}

.cart-popup-footer {
    background: #0f0f0f;
    padding: 20px;
    border-top: 1px solid #333;
}

.cart-popup-subtotal {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
    color: white;
    font-size: 1.1rem;
}

.cart-popup-subtotal span:first-child {
    text-transform: uppercase;
    font-weight: 400;
    letter-spacing: 1px;
}

.cart-popup-total {
    font-weight: 700;
    color: white;
}

.cart-popup-buttons {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

.cart-popup-btn {
    padding: 12px 20px;
    text-align: center;
    text-transform: uppercase;
    font-weight: 600;
    font-size: 0.85rem;
    letter-spacing: 1px;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
}

.view-cart-btn {
    background: #e10000;
    color: white;
}

.view-cart-btn:hover {
    background: #c20000;
}

.checkout-btn {
    background: transparent;
    color: white;
    border: 2px solid white;
}

.checkout-btn:hover {
    background: white;
    color: #1a1a1a;
}

.cart-popup-items::-webkit-scrollbar {
    width: 6px;
}

.cart-popup-items::-webkit-scrollbar-track {
    background: #222;
}

.cart-popup-items::-webkit-scrollbar-thumb {
    background: #444;
    border-radius: 3px;
}

.cart-popup-items::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* 3x3 Dots Grid Menu */
.dots-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 3px;
    width: 20px;
    height: 20px;
}

.dot {
    width: 5px;
    height: 5px;
    background: #ffffff;
    border-radius: 50%;
    transition: all 0.2s ease;
}

.menu-dots-btn:hover .dot {
    background: #e10000;
    transform: scale(1.2);
}

/* Instagram Icon */
.instagram-icon svg {
    transition: all 0.3s ease;
}

.instagram-icon:hover svg {
    transform: scale(1.15) rotate(5deg);
}

.instagram-icon:hover {
    background: linear-gradient(45deg, rgba(225, 48, 108, 0.15), rgba(193, 53, 132, 0.15), rgba(131, 58, 180, 0.15));
}

/* Profile Dropdown */
.profile-dropdown {
    position: relative;
}

.profile-dropdown-menu {
    position: absolute;
    top: calc(100% + 15px);
    right: 0;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    min-width: 250px;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: all 0.3s ease;
    z-index: 10000;
    overflow: hidden;
}

.profile-dropdown:hover .profile-dropdown-menu,
.profile-dropdown-menu:hover {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.profile-user-info {
    padding: 20px;
    background: linear-gradient(135deg, #e10000 0%, #c90000 100%);
    color: #ffffff;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.profile-user-info strong {
    display: block;
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 5px;
}

.profile-user-info span {
    display: block;
    font-size: 13px;
    opacity: 0.9;
}

.profile-menu-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 20px;
    color: #333;
    text-decoration: none;
    transition: all 0.2s ease;
    font-size: 14px;
}

.profile-menu-item:hover {
    background: #f5f5f5;
    color: #e10000;
}

.profile-menu-item svg {
    flex-shrink: 0;
}

.profile-menu-item:last-child {
    border-top: 1px solid #f0f0f0;
    color: #e10000;
}

.profile-menu-item:last-child:hover {
    background: #fff5f5;
}

/* Old Active Style */
.language-switcher a.active_old {
    background: rgba(225, 0, 0, 0.8);
    color: #ffffff;
}

/* Sidebar Overlay */
.sidebar-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(4px);
    z-index: 9998;
    opacity: 0;
    visibility: hidden;
    transition: all 0.4s ease;
}

.sidebar-overlay.active {
    opacity: 1;
    visibility: visible;
}

/* Sidebar Menu */
.sidebar-menu {
    position: fixed;
    top: 0;
    left: -350px;
    width: 350px;
    height: 100vh;
    background: linear-gradient(135deg, #1a1a2e 0%, #0f0f0f 100%);
    z-index: 9999;
    padding: 30px;
    overflow-y: auto;
    transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    box-shadow: 4px 0 20px rgba(0, 0, 0, 0.5);
}

.sidebar-menu.active {
    left: 0;
}

/* Sidebar Header */
.sidebar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 40px;
    padding-bottom: 20px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.sidebar-logo img {
    height: 60px;
    width: auto;
    filter: brightness(1.2);
}

.sidebar-close {
    background: rgba(255, 255, 255, 0.1);
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.2s ease;
    color: #ffffff;
}

.sidebar-close:hover {
    background: #e10000;
    transform: rotate(90deg);
}

.sidebar-close svg {
    width: 20px;
    height: 20px;
}

/* Sidebar Content */
.sidebar-content {
    display: flex;
    flex-direction: column;
    gap: 40px;
}

/* Social Media Links */
.sidebar-social {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.social-link {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px 20px;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    color: #ffffff;
    text-decoration: none;
    transition: all 0.2s ease;
    font-size: 16px;
}

.social-link:hover {
    background: #e10000;
    transform: translateX(10px);
}

.social-link svg {
    width: 20px;
    height: 20px;
}

/* Contact Info */
.sidebar-contact {
    display: flex;
    flex-direction: column;
    gap: 20px;
    padding-top: 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 15px;
    color: #ffffff;
    font-size: 14px;
}

.contact-item svg {
    color: #e10000;
}

/* Scrollbar Styling for Sidebar */
.sidebar-menu::-webkit-scrollbar {
    width: 6px;
}

.sidebar-menu::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
}

.sidebar-menu::-webkit-scrollbar-thumb {
    background: #e10000;
    border-radius: 3px;
}

.sidebar-menu::-webkit-scrollbar-thumb:hover {
    background: #c20000;
}

/* Hero Split Layout */
.hero-header {
    position: relative;
    display: flex;
    width: 100%;
    height: 100vh;
    background: #f89628;
    overflow: hidden;
    margin-bottom: 150px;
}

/* Hero Text Overlay */
.hero-text-overlay {
    position: absolute;
    top: 50%;
    left: 3%;
    transform: translateY(-50%);
    z-index: 3;
    color: #ffffff;
    text-align: left;
    opacity: 0;
    animation: fadeInText 1.5s ease-out 2.5s forwards;
    max-width: 45%;
    word-wrap: break-word;
}

.hero-text-overlay h1 {
    font-family: 'Bebas Neue', 'Arial Narrow', sans-serif;
    font-size: 1.8rem;
    font-weight: 400;
    letter-spacing: 3px;
    text-transform: uppercase;
    margin: 0 0 15px 0;
    text-shadow: 0 4px 8px rgba(0, 0, 0, 0.4);
    color: #ffffff;
}

.hero-text-overlay h2 {
    font-family: 'Bebas Neue', 'Arial Narrow', sans-serif;
    font-size: clamp(2rem, 4vw, 5rem);
    font-weight: 700;
    letter-spacing: 2px;
    text-transform: uppercase;
    margin: 0 0 40px 0;
    line-height: 1.1;
    text-shadow: 0 6px 12px rgba(0, 0, 0, 0.5);
    max-width: none;
    color: #ffffff;
}

/* Hero Action Icons */
.hero-action-icons {
    display: flex;
    gap: 60px;
    margin-top: 40px;
    opacity: 0;
    animation: fadeInText 1.5s ease-out 3s forwards;
}

.hero-icon-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-decoration: none;
    transition: transform 0.3s ease;
}

.hero-icon-item:hover {
    transform: translateY(-10px);
}

.hero-icon-circle {
    width: 100px;
    height: 100px;
    background: transparent;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 15px;
    transition: all 0.3s ease;
}

.hero-icon-item:hover .hero-icon-circle {
    transform: scale(1.1);
}

.hero-icon-circle svg {
    width: 70px;
    height: 70px;
    fill: none;
    stroke: #f89628;
    stroke-width: 2.5;
    stroke-linecap: round;
    stroke-linejoin: round;
    filter: drop-shadow(0 3px 8px rgba(0, 0, 0, 0.3));
    transition: all 0.3s ease;
}

.hero-icon-item:hover .hero-icon-circle svg {
    stroke: #e10000;
    filter: drop-shadow(0 5px 12px rgba(225, 0, 0, 0.4));
}

.hero-icon-label {
    font-family: 'Bebas Neue', 'Arial Narrow', sans-serif;
    font-size: 1.4rem;
    color: #ffffff;
    text-transform: uppercase;
    letter-spacing: 2px;
    text-shadow: 0 3px 6px rgba(0, 0, 0, 0.4);
}

/* About Us Button */
.hero-about-button {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    margin-top: 30px;
    padding: 10px 20px;
    background: #e10000;
    color: #ffffff;
    text-decoration: none;
    border-radius: 4px;
    border: 2px solid #e10000;
    font-family: 'Bebas Neue', 'Arial Narrow', sans-serif;
    font-size: 1.1rem;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    box-shadow: 0 4px 12px rgba(225, 0, 0, 0.3);
    transition: all 0.3s ease;
    opacity: 0;
    animation: fadeInText 1.5s ease-out 3.5s forwards;
}

.hero-about-button:hover {
    background: #c20000;
    border-color: #c20000;
    transform: translateX(10px);
    box-shadow: 0 6px 16px rgba(225, 0, 0, 0.5);
}

.hero-about-button svg {
    width: 18px;
    height: 18px;
    fill: #ffffff;
    transition: transform 0.3s ease;
}

.hero-about-button:hover svg {
    transform: translateX(5px);
}

/* View More Button (same style as About Us) */
.view-more-button {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    margin-top: 50px;
    padding: 10px 20px;
    background: #e10000;
    color: #ffffff;
    text-decoration: none;
    border-radius: 4px;
    border: 2px solid #e10000;
    font-family: 'Bebas Neue', 'Arial Narrow', sans-serif;
    font-size: 1.1rem;
    letter-spacing: 1.5px;
    text-transform: uppercase;
    box-shadow: 0 4px 12px rgba(225, 0, 0, 0.3);
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
    z-index: 1;
    -webkit-user-select: none; /* Safari */
    user-select: none;
}

.view-more-button:hover {
    background: #c20000;
    border-color: #c20000;
    transform: translateY(-3px);
    box-shadow: 0 6px 16px rgba(225, 0, 0, 0.5);
}

.view-more-button svg {
    width: 18px;
    height: 18px;
    fill: #ffffff;
    transition: transform 0.3s ease;
}

.view-more-button:hover svg {
    transform: translateX(5px);
}

/* Gourmet Section */
.gourmet-section {
    background: #f89628;
    margin-top: 150px;
    padding: 100px 0;
    position: relative;
    z-index: 0;
}

.gourmet-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 40px;
    display: grid;
    grid-template-columns: 35% 65%;
    gap: 170px;
    align-items: start;
}

/* Mobile Navbar Fixes */
@media (max-width: 768px) {
    .top-navbar {
        padding: 15px 20px 15px 100px !important;
        min-height: 60px !important;
    }

    .top-navbar.scrolled {
        padding: 10px 20px 10px 100px !important;
        min-height: 50px !important;
    }

    .nav-menu {
        display: none !important;
    }

    .site-logo {
        left: 15px !important;
    }

    .site-logo img {
        height: 50px !important;
    }
}

/* Mobile gourmet section - reduce padding */
@media (max-width: 768px) {
    .gourmet-section {
        margin-top: 60px !important;
        padding: 50px 0 !important;
    }

    .gourmet-container {
        grid-template-columns: 1fr !important;
        gap: 40px !important;
        padding: 0 20px !important;
        text-align: center !important;
    }

    /* Center and resize first image on mobile */
    .gourmet-image {
        width: 100% !important;
        max-width: 350px !important;
        height: auto !important;
        margin: 0 auto !important;
    }

    .gourmet-image img {
        width: 100% !important;
        height: auto !important;
        object-fit: contain !important;
    }

    /* Center content on mobile */
    .gourmet-content {
        text-align: center !important;
        padding-right: 0 !important;
    }

    .gourmet-content h2,
    .gourmet-content h3,
    .gourmet-content p {
        text-align: center !important;
        margin-left: auto !important;
        margin-right: auto !important;
    }

    .gourmet-content h2 {
        font-size: 1.3rem !important;
    }

    .gourmet-content h3 {
        font-size: 2.2rem !important;
        line-height: 1.1 !important;
        margin-bottom: 20px !important;
    }

    .gourmet-content p {
        font-size: 1rem !important;
        line-height: 1.6 !important;
        max-width: 100% !important;
    }

    /* Center and ENLARGE second image on mobile */
    .gourmet-bottom-image {
        width: 100% !important;
        max-width: 450px !important;
        height: auto !important;
        margin: 40px auto 0 auto !important;
        display: block !important;
    }

    .gourmet-bottom-image img {
        width: 100% !important;
        height: auto !important;
        object-fit: contain !important;
        display: block !important;
    }
}

.gourmet-image {
    width: 100%;
    height: 600px;
    overflow: hidden;
    border-radius: 0;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    position: relative;
    margin-left: 60px;
}

.gourmet-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

.gourmet-content {
    padding-right: 200px;
}

.gourmet-content h2 {
    font-family: 'Bebas Neue', 'Arial Narrow', sans-serif;
    font-size: 1.5rem;
    color: #ffffff;
    margin-bottom: 10px;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 400;
}

.gourmet-content h3 {
    font-family: 'Bebas Neue', 'Arial Narrow', sans-serif;
    font-size: 3rem;
    color: #ffffff;
    margin-bottom: 10px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 400;
    line-height: 1;
}

.gourmet-content p {
    font-family: Arial, sans-serif;
    font-size: 1.1rem;
    color: #ffffff;
    line-height: 1.8;
    margin: 0 0 30px 0;
}

.gourmet-bottom-image {
    width: 100%;
    margin-top: 80px;
    overflow: hidden;
}

.gourmet-bottom-image img {
    width: 100%;
    height: auto;
    display: block;
    object-fit: cover;
}

/* Customer Reviews Section */
.reviews-section {
    background: white;
    padding: 100px 0;
    overflow: hidden;
}

.reviews-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 40px;
}

.reviews-section h2 {
    font-family: 'Bebas Neue', 'Arial Narrow', sans-serif;
    font-size: 3rem;
    color: #2d3142;
    text-align: center;
    margin-bottom: 60px;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.reviews-slider {
    position: relative;
    width: 100%;
    overflow: hidden;
}

.reviews-track {
    display: flex;
    animation: slideReviews 20s linear infinite;
}

/* Pause animation on hover */
.reviews-slider:hover .reviews-track {
    animation-play-state: paused;
}

.review-card {
    min-width: 320px;
    max-width: 320px;
    flex-shrink: 0;
    margin: 0 15px;
    padding: 30px 25px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    position: relative;
    overflow: hidden;
}

/* Mobile reviews - responsive card width */
@media (max-width: 768px) {
    .reviews-container {
        padding: 0 20px !important;
    }

    .review-card {
        min-width: 280px !important;
        max-width: 280px !important;
        margin: 0 10px !important;
        padding: 25px 20px !important;
    }
}

@media (max-width: 480px) {
    .review-card {
        min-width: 260px !important;
        max-width: 260px !important;
    }
}

/* Gradient overlay animation */
.review-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(248, 150, 40, 0.1), transparent);
    transition: left 0.6s ease;
}

.review-card:hover::before {
    left: 100%;
}

.review-card:hover {
    transform: translateY(-15px) scale(1.05);
    box-shadow: 0 20px 50px rgba(248, 150, 40, 0.3);
    background: linear-gradient(135deg, #fff 0%, #fffaf5 100%);
}

.review-stars {
    font-size: 1.3rem;
    margin-bottom: 15px;
    color: #f89628;
    transition: all 0.3s ease;
}

.review-card:hover .review-stars {
    transform: scale(1.15);
    text-shadow: 0 2px 8px rgba(248, 150, 40, 0.4);
}

.review-text {
    font-family: Arial, sans-serif;
    font-size: 0.95rem;
    color: #666;
    line-height: 1.6;
    margin-bottom: 20px;
    font-style: italic;
    transition: color 0.3s ease;
}

.review-card:hover .review-text {
    color: #333;
}

.review-author {
    font-family: 'Bebas Neue', 'Arial Narrow', sans-serif;
    font-size: 1.1rem;
    color: #2d3142;
    text-transform: uppercase;
    letter-spacing: 1px;
    transition: all 0.3s ease;
}

.review-card:hover .review-author {
    color: #f89628;
    transform: translateX(5px);
}

@keyframes slideReviews {
    0% {
        transform: translateX(0);
    }
    100% {
        transform: translateX(-50%);
    }
}

/* Gallery Section */
.gallery-section {
    background: #f5f5f5;
    padding: 0;
    overflow: hidden;
    width: 100%;
    margin: 0;
}

.gallery-container {
    max-width: 100%;
    width: 100%;
    margin: 0;
    padding: 0;
    display: grid;
    grid-template-columns: 50% 50%;
    gap: 0;
    height: 980px;
}

.gallery-main {
    position: relative;
    overflow: hidden;
    width: 100%;
    height: 980px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #000;
}

.gallery-main img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    display: block;
    filter: brightness(0.9) contrast(1.05);
}

.gallery-main:hover img {
    transform: scale(1.08) rotate(1deg);
    filter: brightness(1) contrast(1.1);
}

.gallery-grid {
    display: grid;
    grid-template-columns: 50% 50%;
    grid-template-rows: 50% 50%;
    gap: 0;
    width: 100%;
    height: 980px;
}

/* Mobile gallery - single column with proper spacing */
@media (max-width: 768px) {
    .gallery-section {
        padding: 20px 0 !important;
    }

    .gallery-container {
        grid-template-columns: 1fr !important;
        height: auto !important;
        gap: 15px !important;
        padding: 0 15px !important;
    }

    .gallery-main {
        height: 300px !important;
        border-radius: 12px !important;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15) !important;
    }

    .gallery-grid {
        grid-template-columns: 1fr !important;
        grid-template-rows: auto !important;
        height: auto !important;
        gap: 15px !important;
    }

    .gallery-item {
        height: 250px !important;
        border-radius: 12px !important;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15) !important;
    }
}

.gallery-item {
    position: relative;
    overflow: hidden;
    width: 100%;
    height: 490px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #000;
}

.gallery-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
    transition: all 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    display: block;
    filter: brightness(0.9) contrast(1.05);
}

.gallery-item:hover img {
    transform: scale(1.12) rotate(-1deg);
    filter: brightness(1) contrast(1.1);
}

/* Gallery item overlay effect */
.gallery-item::after,
.gallery-main::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(45deg, rgba(248, 150, 40, 0.1), transparent);
    opacity: 0;
    transition: opacity 0.4s ease;
    pointer-events: none;
}

.gallery-item:hover::after,
.gallery-main:hover::after {
    opacity: 1;
}

@keyframes fadeInText {
    to {
        opacity: 1;
    }
}

/* Google Maps Section */
.map-section {
    background: #1a1a1a;
    padding: 100px 40px;
    position: relative;
}

.map-container {
    max-width: 1400px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 60px;
    align-items: center;
}

.map-info {
    color: #ffffff;
}

.map-info h2 {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 3.5rem;
    letter-spacing: 2px;
    margin-bottom: 40px;
    color: #f9a825;
    text-transform: uppercase;
}

/* Mobile map section - responsive fonts */
@media (max-width: 768px) {
    .map-section {
        padding: 60px 20px !important;
    }

    .map-container {
        grid-template-columns: 1fr !important;
        gap: 40px !important;
    }

    .map-info h2 {
        font-size: 2.5rem !important;
        margin-bottom: 30px !important;
    }

    .map-detail-content h3 {
        font-size: 1.3rem !important;
    }

    .map-detail-content p {
        font-size: 0.95rem !important;
    }
}

.map-details {
    display: flex;
    flex-direction: column;
    gap: 30px;
}

.map-detail-item {
    display: flex;
    align-items: flex-start;
    gap: 20px;
}

.map-icon {
    width: 50px;
    height: 50px;
    background: #f9a825;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.map-icon svg {
    width: 24px;
    height: 24px;
    stroke: #1a1a1a;
}

.map-detail-content h3 {
    font-family: 'Bebas Neue', sans-serif;
    font-size: 1.5rem;
    letter-spacing: 1px;
    margin-bottom: 8px;
    color: #f9a825;
}

.map-detail-content p,
.map-detail-content a {
    font-size: 1.1rem;
    line-height: 1.6;
    color: #cccccc;
    text-decoration: none;
    transition: color 0.3s ease;
}

.map-detail-content a:hover {
    color: #f9a825;
}

.map-embed {
    position: relative;
    height: 500px;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
}

.map-embed iframe {
    width: 100%;
    height: 100%;
    border: none;
}

@media (max-width: 968px) {
    .map-container {
        grid-template-columns: 1fr;
        gap: 40px;
    }

    .map-info h2 {
        font-size: 2.5rem;
    }

    .map-embed {
        height: 400px;
    }
}


/* Center Circle - Animated */
.hero-circle {
    position: absolute;
    top: -15%;
    right: -5%;
    width: 600px;
    height: 600px;
    background: rgba(255, 175, 100, 0.7);
    border-radius: 50%;
    opacity: 0;
    animation: fadeInCircle 1.5s ease-out 2s forwards;
    z-index: 0;
}

@keyframes fadeInCircle {
    to {
        opacity: 0.85;
    }
}

@keyframes fadeInCircleRotating {
    to {
        opacity: 1;
    }
}

/* Bottom Right Circle - Animated */
.hero-circle-bottom {
    position: absolute;
    bottom: -15%;
    right: 30%;
    width: 500px;
    height: 500px;
    background: rgba(255, 175, 100, 0.7);
    border-radius: 50%;
    opacity: 0;
    animation: fadeInCircle 1.5s ease-out 2s forwards;
    z-index: 0;
}

/* Rotating Dashed Circle - Bottom Right */
.hero-circle-rotating {
    position: absolute;
    bottom: 5%;
    right: 2%;
    width: 200px;
    height: 200px;
    border: 5px dashed #e10000;
    border-radius: 50%;
    background: transparent;
    opacity: 0;
    animation: fadeInCircleRotating 1.5s ease-out 2s forwards, rotateCircle 90s linear 2s infinite;
    z-index: 0;
    stroke-dasharray: 10 8;
}

@keyframes rotateCircle {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

/* Rotating Dashed Circle 2 - Top Right */
.hero-circle-rotating-2 {
    position: absolute;
    top: 2%;
    right: 35%;
    width: 200px;
    height: 200px;
    border: 5px dashed #e10000;
    border-radius: 50%;
    background: transparent;
    opacity: 0;
    animation: fadeInCircleRotating 1.5s ease-out 2s forwards, rotateCircle 90s linear 2s infinite;
    z-index: 0;
    stroke-dasharray: 10 8;
}

/* Center Burger Image */
.hero-burger {
    position: absolute;
    top: 50%;
    right: 15%;
    transform: translate(0, -50%);
    max-width: 600px;
    width: 90%;
    opacity: 0;
    animation: fadeInBurger 1.5s ease-out 2s forwards;
    z-index: 10;
    transition: transform 0.3s ease-out;
}

@keyframes fadeInBurger {
    to {
        opacity: 1;
    }
}

/* Floating Circles Around Burger */
.floating-circle {
    position: absolute;
    border-radius: 50%;
    background: #e10000;
    opacity: 0;
    animation: fadeInCircle 1.5s ease-out 2s forwards;
    transition: transform 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    z-index: 0;
}

.floating-circle-1 {
    top: 25%;
    right: 27%;
    width: 12px;
    height: 12px;
}

.floating-circle-2 {
    top: 28%;
    right: 18%;
    width: 5px;
    height: 5px;
}

.floating-circle-3 {
    top: 26%;
    right: 10%;
    width: 14px;
    height: 14px;
}

.floating-circle-4 {
    top: 48%;
    right: 30%;
    width: 8px;
    height: 8px;
}

.floating-circle-5 {
    top: 50%;
    right: 8%;
    width: 10px;
    height: 10px;
}

.floating-circle-6 {
    bottom: 25%;
    right: 25%;
    width: 16px;
    height: 16px;
}

.floating-circle-7 {
    bottom: 28%;
    right: 16%;
    width: 6px;
    height: 6px;
}

.floating-circle-8 {
    bottom: 26%;
    right: 10%;
    width: 13px;
    height: 13px;
}

.floating-circle-9 {
    top: 35%;
    right: 25%;
    width: 4px;
    height: 4px;
}

.floating-circle-10 {
    top: 38%;
    right: 12%;
    width: 18px;
    height: 18px;
}

.floating-circle-11 {
    bottom: 35%;
    right: 20%;
    width: 3px;
    height: 3px;
}

.floating-circle-12 {
    bottom: 38%;
    right: 15%;
    width: 9px;
    height: 9px;
}

/* Additional circles on left side of burger */
.floating-circle-13 {
    top: 30%;
    right: 35%;
    width: 10px;
    height: 10px;
}

.floating-circle-14 {
    top: 42%;
    right: 38%;
    width: 13px;
    height: 13px;
}

.floating-circle-15 {
    bottom: 32%;
    right: 36%;
    width: 8px;
    height: 8px;
}

.floating-circle-16 {
    top: 52%;
    right: 33%;
    width: 5px;
    height: 5px;
}

.floating-circle-17 {
    bottom: 45%;
    right: 40%;
    width: 12px;
    height: 12px;
}

.floating-circle-18 {
    top: 38%;
    right: 42%;
    width: 6px;
    height: 6px;
}

/* Hand-drawn curved lines */
.hand-drawn-line {
    position: absolute;
    opacity: 0;
    animation: fadeInCircle 1.5s ease-out 2s forwards;
    transition: transform 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    z-index: 0;
}

.hand-drawn-line svg {
    width: 100%;
    height: 100%;
}

.hand-drawn-line path {
    fill: none;
    stroke: #e10000;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.line-1 {
    top: 20%;
    right: 18%;
    width: 80px;
    height: 40px;
}

.line-2 {
    top: 55%;
    right: 12%;
    width: 60px;
    height: 50px;
}

.line-3 {
    bottom: 30%;
    right: 22%;
    width: 70px;
    height: 35px;
}

.line-4 {
    top: 40%;
    right: 28%;
    width: 50px;
    height: 45px;
}

.line-5 {
    bottom: 40%;
    right: 10%;
    width: 65px;
    height: 40px;
}

.line-6 {
    top: 15%;
    right: 25%;
    width: 75px;
    height: 45px;
}

.line-7 {
    top: 60%;
    right: 30%;
    width: 55px;
    height: 50px;
}

.line-8 {
    bottom: 20%;
    right: 15%;
    width: 70px;
    height: 38px;
}

.line-9 {
    top: 32%;
    right: 8%;
    width: 60px;
    height: 42px;
}

.line-10 {
    bottom: 50%;
    right: 32%;
    width: 68px;
    height: 36px;
}

/* Additional lines on left side of burger */
.line-11 {
    top: 35%;
    right: 38%;
    width: 55px;
    height: 48px;
}

.line-12 {
    bottom: 38%;
    right: 40%;
    width: 62px;
    height: 40px;
}

.line-13 {
    top: 48%;
    right: 36%;
    width: 50px;
    height: 35px;
}

.line-14 {
    bottom: 50%;
    right: 42%;
    width: 58px;
    height: 42px;
}

/* Left Column - Image sliding from left */
.hero-left {
    position: absolute;
    left: 0;
    top: 0;
    width: 70%;
    height: 100%;
    background-image: url('<?php echo ASSETS_URL; ?>/images/background1.png');
    background-size: cover;
    background-position: center right;
    transform: translateX(-100%);
    animation: slideInLeft 1.5s ease-out 1s forwards;
    z-index: 2;
}

@keyframes slideInLeft {
    to {
        transform: translateX(0);
    }
}

/* Right Column - Hidden/Disabled */
.hero-right {
    display: none;
}

/* Responsive - Tablet & Medium Screens */
@media (max-width: 1400px) {
    .hero-text-overlay {
        left: 2%;
        max-width: 50%;
        top: 52%;
    }
    
    .hero-text-overlay h1 {
        font-size: 1.5rem;
    }
    
    .hero-text-overlay h2 {
        font-size: 4.5rem;
    }
    
    .hero-action-icons {
        gap: 40px;
    }
    
    .hero-icon-circle svg {
        width: 60px;
        height: 60px;
    }
    
    .hero-icon-label {
        font-size: 1.2rem;
    }
}

@media (max-width: 1200px) {
    .hero-text-overlay h1 {
        font-size: 1.3rem;
    }
    
    .hero-text-overlay h2 {
        font-size: 3.5rem;
    }
    
    .hero-action-icons {
        gap: 30px;
        flex-wrap: wrap;
    }
    
    .hero-icon-circle {
        width: 80px;
        height: 80px;
    }
    
    .hero-icon-circle svg {
        width: 50px;
        height: 50px;
    }
    
    /* Burger cards - 3 columns on tablet */
    .burger-cards {
        grid-template-columns: repeat(3, 1fr);
        gap: 25px;
    }
}

/* Responsive - Mobile */
@media (max-width: 968px) {
    .hero-header {
        height: 100vh;
    }
    
    .hero-left {
        width: 100%;
        height: 100%;
        background-size: cover;
    }
    
    /* Reduce burger size on mobile */
    .hero-burger {
        max-width: 400px;
        width: 80%;
    }
    
    /* Scale down circles on mobile */
    .hero-circle {
        width: 400px;
        height: 400px;
    }
    
    .hero-circle-bottom {
        width: 350px;
        height: 350px;
    }
    
    /* Hide rotating circles on mobile */
    .hero-circle-rotating,
    .hero-circle-rotating-2 {
        display: none;
    }
    
    /* Scale down and ensure proper z-index for circles and lines on mobile */
    .floating-circle {
        transform: scale(0.7);
        z-index: 4 !important;
    }
    
    .hand-drawn-line {
        transform: scale(0.7);
        z-index: 4 !important;
    }
    
    /* Ensure burger stays on top on mobile */
    .hero-burger {
        z-index: 10 !important;
    }
    
    /* Make logo smaller on mobile */
    .site-logo {
        position: absolute !important;
        left: 15px !important;
        top: 50% !important;
        transform: translateY(-50%) !important;
    }

    .site-logo img {
        height: 45px !important;
        filter: drop-shadow(0 3px 6px rgba(0, 0, 0, 0.4)) drop-shadow(0 2px 3px rgba(0, 0, 0, 0.3));
    }

    .site-logo:hover img {
        transform: scale(1.02) !important; /* Reduce scale effect on mobile */
    }

    /* Hero left background - reduce width on mobile */
    .hero-left {
        width: 65% !important; /* Reduce from 70% to 65% on mobile */
    }

    /* Burger cards - single column on mobile */
    .burger-cards {
        grid-template-columns: 1fr !important;
        gap: 20px !important;
        padding: 0 20px !important;
    }

    .burger-card {
        max-width: 100% !important;
    }

    /* Navbar mobile adjustments */
    .top-navbar {
        padding: 15px 20px 15px 100px !important;
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        overflow: visible !important;
        min-height: 60px !important;
    }

    .top-navbar.scrolled {
        padding: 10px 20px 10px 100px !important;
        min-height: 50px !important;
    }

    .site-logo {
        left: 15px !important;
    }

    .site-logo img {
        height: 50px !important;
    }

    .nav-menu {
        display: none !important; /* Hide nav menu, show hamburger instead */
    }

    .menu-dots-btn {
        display: flex !important; /* Show hamburger menu */
        position: relative;
        z-index: 10000002 !important;
    }

    .header-icons {
        display: flex !important; /* Show header icons on mobile */
        gap: 8px !important;
        margin-left: 0 !important;
        margin-right: 12px !important; /* add safe right spacing */
    }
    
    /* Cart badge adjustments for mobile */
    .cart-badge {
        width: 18px !important;
        height: 18px !important;
        font-size: 10px !important;
        top: -4px !important;
        right: 2px !important; /* move inside the icon */
        line-height: 18px !important;
    }

    /* Language switcher mobile - hide from navbar, shown in sidebar */
    .language-switcher {
        display: none !important;
    }

    /* Touch targets for mobile */
    .menu-dots-btn {
        width: 38px !important;
        height: 38px !important;
        padding: 7px !important;
    }
    
    .menu-dots-btn .line {
        width: 18px !important;
    }

    /* Prevent overflow on all sections */
    section, div {
        max-width: 100%;
        overflow-x: hidden;
    }

    /* Adjust burger section text for mobile */
    .burgers-section {
        padding: 60px 20px;
    }
    
    .burgers-section h2 {
        font-size: 1.5rem;
    }
    
    .burgers-section p {
        font-size: 2.2rem;
        line-height: 1;
        padding: 0 15px;
    }
    
    /* Hero text overlay responsive */
    .hero-text-overlay {
        left: 50%;
        transform: translate(-50%, -50%);
        width: 90%;
        max-width: 90%;
        text-align: center;
        top: 45%;
    }
    
    .hero-text-overlay h1 {
        font-size: 1.2rem;
        letter-spacing: 1.5px;
        margin-bottom: 10px;
    }
    
    .hero-text-overlay h2 {
        font-size: 2rem;
        max-width: 100%;
        margin-bottom: 25px;
        line-height: 1.2;
    }
    
    /* Hero action icons responsive */
    .hero-action-icons {
        gap: 25px;
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .hero-icon-circle {
        width: 60px;
        height: 60px;
    }
    
    .hero-icon-circle svg {
        width: 45px;
        height: 45px;
    }
    
    .hero-icon-label {
        font-size: 0.9rem;
        letter-spacing: 1px;
    }
    
    .hero-about-button {
        font-size: 1rem;
        padding: 8px 16px;
        margin-top: 25px;
        gap: 8px;
    }
    
    .hero-about-button svg {
        width: 16px;
        height: 16px;
    }
}

/* Extra Small Screens */
@media (max-width: 480px) {
    .hero-text-overlay {
        top: 40%;
        width: 95%;
    }
    
    .hero-text-overlay h1 {
        font-size: 1rem;
        letter-spacing: 1px;
        margin-bottom: 8px;
    }
    
    .hero-text-overlay h2 {
        font-size: 1.5rem;
        margin-bottom: 20px;
    }
    
    .hero-action-icons {
        gap: 15px;
    }
    
    .hero-icon-circle {
        width: 50px;
        height: 50px;
    }
    
    .hero-icon-circle svg {
        width: 35px;
        height: 35px;
    }
    
    .hero-icon-label {
        font-size: 0.75rem;
    }
    
    .hero-about-button {
        font-size: 0.9rem;
        padding: 8px 14px;
        margin-top: 20px;
        gap: 6px;
    }
    
    .hero-about-button svg {
        width: 14px;
        height: 14px;
    }
    
    .view-more-button {
        font-size: 0.9rem;
        padding: 8px 14px;
        margin-top: 30px;
    }
    
    .view-more-button svg {
        width: 14px;
        height: 14px;
    }
    
    .site-logo {
        left: 15px !important;
    }
    
    .site-logo img {
        height: 50px !important;
    }
    
    .top-navbar {
        padding: 15px 20px 15px 100px !important;
        overflow: visible !important;
    }

    .top-navbar.scrolled {
        padding: 10px 20px 10px 100px !important;
        min-height: 50px !important;
    }
    
    .header-icons {
        gap: 6px !important;
        margin-left: 0 !important;
        margin-right: 10px !important; /* add safe right spacing */
    }
    
    .menu-dots-btn {
        width: 36px !important;
        height: 36px !important;
        padding: 6px !important;
    }
    
    .menu-dots-btn .line {
        width: 16px !important;
    }
    
    .cart-badge {
        width: 16px !important;
        height: 16px !important;
        font-size: 9px !important;
        top: -3px !important;
        right: 1px !important; /* move inside the icon */
        line-height: 16px !important;
    }
        height: 18px !important;
    }
    
    /* Burger cards extra small */
    .burger-cards {
        grid-template-columns: 1fr;
        gap: 20px;
    }
}

/* Medium Screens - Tablet */
@media (max-width: 968px) {
    /* Burger cards responsive */
    .burger-cards {
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }
    
    .burger-card {
        padding: 20px 15px;
    }
    
    .burger-card-image {
        height: 180px;
    }
    
    .burger-card-name {
        font-size: 1.2rem;
    }
    
    .burger-card-price {
        font-size: 1.4rem;
    }
    
    .burger-card-buy {
        font-size: 1.4rem;
    }
    
    /* Gourmet section responsive */
    .gourmet-container {
        grid-template-columns: 1fr;
        gap: 40px;
    }
    
    .gourmet-image {
        height: 400px;
    }
    
    .gourmet-content h2 {
        font-size: 2rem;
    }
    
    .gourmet-content h3 {
        font-size: 1.4rem;
    }
    
    .gourmet-content p {
        font-size: 1rem;
    }
}

/* Burgers Section */
.burgers-section {
    background: white;
    margin-top: 50px;
    padding: 60px 20px;
    text-align: center;
    position: relative;
    z-index: 1;
}

.burgers-section .container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 20px;
    text-align: center;
    position: relative;
    z-index: 1;
}

.burgers-section h2 {
    font-family: 'Bebas Neue', 'Arial Narrow', 'Impact', sans-serif;
    font-size: 1.8rem;
    color: #2d3142;
    margin-bottom: 1rem;
    font-weight: 400;
    text-transform: uppercase;
    letter-spacing: -0.5px;
    text-align: center !important;
    width: 100%;
    display: block;
}

.burgers-section p {
    font-family: 'Bebas Neue', 'Arial Narrow', 'Impact', sans-serif;
    font-size: 3.5rem;
    color: #2d3142;
    font-weight: 400;
    max-width: 900px;
    margin: 0 auto;
    text-align: center !important;
    text-transform: uppercase;
    line-height: 0.95;
    letter-spacing: -0.5px;
    margin-bottom: 60px;
    width: 100%;
    display: block;
}

/* Burger Cards Grid */
.burger-cards {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 35px;
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 20px;
}

.burger-card {
    background: white;
    border-radius: 8px;
    padding: 25px 20px;
    position: relative;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    cursor: pointer;
    overflow: hidden;
}

.burger-card:hover {
    transform: translateY(-15px);
    box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
}

.burger-card-image {
    position: relative;
    width: 100%;
    height: 250px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.burger-card-image img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
    transition: transform 0.4s ease;
}

/* Discount Badge */
.discount-badge {
    position: absolute;
    top: 15px;
    left: 15px;
    background: linear-gradient(135deg, #e10000 0%, #ff4444 100%);
    color: white;
    padding: 8px 15px;
    border-radius: 25px;
    font-weight: bold;
    font-size: 16px;
    box-shadow: 0 4px 15px rgba(225, 0, 0, 0.4);
    z-index: 10;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
}

.burger-card:hover .burger-card-image img {
    transform: scale(1.1) rotate(5deg);
}

.add-to-cart-icon {
    position: absolute;
    top: 10px;
    right: 10px;
    width: 45px;
    height: 45px;
    background: #f89628;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    opacity: 0;
    transform: scale(0);
    transition: all 0.3s ease;
    cursor: pointer;
    box-shadow: 0 5px 15px rgba(248, 150, 40, 0.4);
}

.burger-card:hover .add-to-cart-icon {
    opacity: 1;
    transform: scale(1);
}

.add-to-cart-icon:hover {
    background: #e10000;
    transform: scale(1.15);
}

.add-to-cart-icon svg {
    width: 22px;
    height: 22px;
    fill: white;
}

.burger-card-content {
    text-align: center;
}

.burger-card-name {
    font-family: 'Bebas Neue', 'Arial Narrow', sans-serif;
    font-size: 1.4rem;
    color: #000000;
    margin-bottom: 15px;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 400;
}

.burger-card-footer {
    position: relative;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.burger-card-price {
    font-family: 'Bebas Neue', 'Arial Narrow', sans-serif;
    font-size: 1.6rem;
    color: #000000;
    font-weight: 400;
    transition: all 0.3s ease;
    position: absolute;
    opacity: 1;
}

.burger-card:hover .burger-card-price {
    opacity: 0;
    transform: translateY(20px);
}

.burger-card-buy {
    font-family: 'Bebas Neue', 'Arial Narrow', sans-serif;
    font-size: 1.6rem;
    color: #000000;
    font-weight: 400;
    text-transform: uppercase;
    letter-spacing: 0;
    position: absolute;
    opacity: 0;
    transform: translateY(20px);
    transition: all 0.3s ease;
}

.burger-card:hover .burger-card-buy {
    opacity: 1;
    transform: translateY(0);
}
</style>

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
        <a href="javascript:void(0);" class="header-icon login-icon" title="Login / Register" onclick="event.stopPropagation(); document.getElementById('authModal').classList.add('active'); document.getElementById('authModalOverlay').classList.add('active'); document.getElementById('loginForm').classList.add('active'); document.getElementById('registerForm').classList.remove('active'); document.body.style.overflow='hidden';">
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
            <span class="sidebar-cart-badge">0</span>
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
include 'includes/login-popup.php';
?>

<div class="hero-header">
    <!-- Hero Text Overlay -->
    <div class="hero-text-overlay">
        <h1><?php echo t('hero_main_title'); ?></h1>
        <h2><?php echo str_replace(',', ',<br>', t('hero_main_subtitle')); ?></h2>
        
        <!-- Hero Action Icons -->
        <div class="hero-action-icons">
            <!-- Angebot Icon (Hand-drawn burger) -->
            <a href="<?php echo SITE_URL; ?>/angebot.php" class="hero-icon-item">
                <div class="hero-icon-circle">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 60">
                        <!-- Burger layers - hand-drawn style -->
                        <path d="M 8,15 Q 7,14 10,13 L 50,13 Q 53,14 52,15 Q 51,16 48,16 L 12,16 Q 9,16 8,15" />
                        <path d="M 10,22 Q 9,21 12,20 L 48,20 Q 51,21 50,22 L 10,22" />
                        <circle cx="16" cy="26" r="1.5" />
                        <circle cx="24" cy="27" r="1.5" />
                        <circle cx="32" cy="26" r="1.5" />
                        <circle cx="40" cy="27" r="1.5" />
                        <circle cx="48" cy="26" r="1.5" />
                        <path d="M 8,32 Q 7,31 10,30 L 50,30 Q 53,31 52,32 L 8,32" />
                        <path d="M 10,38 Q 9,37 12,36 L 48,36 Q 51,37 50,38 L 10,38" />
                        <path d="M 7,45 Q 6,43 12,42 L 48,42 Q 54,43 53,45 Q 52,47 48,48 L 12,48 Q 8,47 7,45" />
                    </svg>
                </div>
                <span class="hero-icon-label"><?php echo t('nav_menu'); ?></span>
            </a>
            
            <!-- Online Bestellen Icon (Hand-drawn list/menu) -->
            <a href="<?php echo SITE_URL; ?>/menu.php" class="hero-icon-item">
                <div class="hero-icon-circle">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 60">
                        <!-- Menu list - hand-drawn style -->
                        <rect x="10" y="8" width="40" height="44" rx="2" />
                        <path d="M 16,16 Q 15,15 18,15 L 44,15 Q 46,15 45,16" />
                        <path d="M 16,23 Q 15,22 18,22 L 42,22 Q 44,22 43,23" />
                        <path d="M 16,30 Q 15,29 18,29 L 44,29 Q 46,29 45,30" />
                        <path d="M 16,37 Q 15,36 18,36 L 40,36 Q 42,36 41,37" />
                        <path d="M 16,44 Q 15,43 18,43 L 44,43 Q 46,43 45,44" />
                    </svg>
                </div>
                <span class="hero-icon-label"><?php echo t('nav_order'); ?></span>
            </a>
            
            <!-- Kontakt Icon (Hand-drawn phone) -->
            <a href="<?php echo SITE_URL; ?>/contact.php" class="hero-icon-item">
                <div class="hero-icon-circle">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 60 60">
                        <!-- Phone - hand-drawn style -->
                        <path d="M 20,12 Q 18,10 22,10 L 38,10 Q 42,10 40,12 L 40,48 Q 42,50 38,50 L 22,50 Q 18,50 20,48 Z" />
                        <path d="M 24,15 Q 23,14 26,14 L 34,14 Q 37,14 36,15" />
                        <circle cx="30" cy="44" r="2.5" />
                        <rect x="23" y="19" width="14" height="18" rx="1" />
                    </svg>
                </div>
                <span class="hero-icon-label"><?php echo t('hero_icon_contact'); ?></span>
            </a>
        </div>
        
        <!-- About Us Button -->
        <a href="<?php echo SITE_URL; ?>/about.php" class="hero-about-button">
            <?php echo t('hero_about_button'); ?>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/>
            </svg>
        </a>
    </div>
    
    <!-- Animated Top Circle -->
    <div class="hero-circle"></div>
    
    <!-- Animated Bottom Circle -->
    <div class="hero-circle-bottom"></div>
    
    <!-- Rotating Dashed Circle (Bottom Right) -->
    <div class="hero-circle-rotating"></div>
    
    <!-- Rotating Dashed Circle 2 (Top Left) -->
    <div class="hero-circle-rotating-2"></div>
    
    <!-- Center Burger Image -->
    <img src="<?php echo ASSETS_URL; ?>/images/burger.png" alt="Q-Bab Burger" class="hero-burger">
    
    <!-- Floating Circles -->
    <div class="floating-circle floating-circle-1"></div>
    <div class="floating-circle floating-circle-2"></div>
    <div class="floating-circle floating-circle-3"></div>
    <div class="floating-circle floating-circle-4"></div>
    <div class="floating-circle floating-circle-5"></div>
    <div class="floating-circle floating-circle-6"></div>
    <div class="floating-circle floating-circle-7"></div>
    <div class="floating-circle floating-circle-8"></div>
    <div class="floating-circle floating-circle-9"></div>
    <div class="floating-circle floating-circle-10"></div>
    <div class="floating-circle floating-circle-11"></div>
    <div class="floating-circle floating-circle-12"></div>
    <div class="floating-circle floating-circle-13"></div>
    <div class="floating-circle floating-circle-14"></div>
    <div class="floating-circle floating-circle-15"></div>
    <div class="floating-circle floating-circle-16"></div>
    <div class="floating-circle floating-circle-17"></div>
    <div class="floating-circle floating-circle-18"></div>
    
    <!-- Hand-drawn curved lines -->
    <div class="hand-drawn-line line-1">
        <svg viewBox="0 0 80 40" xmlns="http://www.w3.org/2000/svg">
            <path d="M5,20 Q20,8 40,18 T75,22" />
        </svg>
    </div>
    
    <div class="hand-drawn-line line-2">
        <svg viewBox="0 0 60 50" xmlns="http://www.w3.org/2000/svg">
            <path d="M10,25 Q25,10 45,28 Q50,35 55,30" />
        </svg>
    </div>
    
    <div class="hand-drawn-line line-3">
        <svg viewBox="0 0 70 35" xmlns="http://www.w3.org/2000/svg">
            <path d="M8,18 Q30,5 50,20 Q60,28 65,15" />
        </svg>
    </div>
    
    <div class="hand-drawn-line line-4">
        <svg viewBox="0 0 50 45" xmlns="http://www.w3.org/2000/svg">
            <path d="M10,10 Q20,25 30,15 Q40,5 45,20" />
        </svg>
    </div>
    
    <div class="hand-drawn-line line-5">
        <svg viewBox="0 0 65 40" xmlns="http://www.w3.org/2000/svg">
            <path d="M5,25 Q25,35 40,18 Q55,8 60,22" />
        </svg>
    </div>
    
    <div class="hand-drawn-line line-6">
        <svg viewBox="0 0 75 45" xmlns="http://www.w3.org/2000/svg">
            <path d="M10,22 Q30,10 50,25 Q65,35 70,20" />
        </svg>
    </div>
    
    <div class="hand-drawn-line line-7">
        <svg viewBox="0 0 55 50" xmlns="http://www.w3.org/2000/svg">
            <path d="M8,15 Q20,30 35,20 Q45,10 50,28" />
        </svg>
    </div>
    
    <div class="hand-drawn-line line-8">
        <svg viewBox="0 0 70 38" xmlns="http://www.w3.org/2000/svg">
            <path d="M5,20 Q25,8 45,22 Q60,30 65,18" />
        </svg>
    </div>
    
    <div class="hand-drawn-line line-9">
        <svg viewBox="0 0 60 42" xmlns="http://www.w3.org/2000/svg">
            <path d="M10,20 Q25,35 40,15 Q50,5 55,25" />
        </svg>
    </div>
    
    <div class="hand-drawn-line line-10">
        <svg viewBox="0 0 68 36" xmlns="http://www.w3.org/2000/svg">
            <path d="M8,18 Q28,28 45,12 Q58,8 63,20" />
        </svg>
    </div>
    
    <div class="hand-drawn-line line-11">
        <svg viewBox="0 0 55 48" xmlns="http://www.w3.org/2000/svg">
            <path d="M10,24 Q20,12 35,26 Q45,38 50,20" />
        </svg>
    </div>
    
    <div class="hand-drawn-line line-12">
        <svg viewBox="0 0 62 40" xmlns="http://www.w3.org/2000/svg">
            <path d="M8,20 Q25,32 42,18 Q55,10 58,25" />
        </svg>
    </div>
    
    <div class="hand-drawn-line line-13">
        <svg viewBox="0 0 50 35" xmlns="http://www.w3.org/2000/svg">
            <path d="M5,18 Q20,8 35,20 Q45,28 48,15" />
        </svg>
    </div>
    
    <div class="hand-drawn-line line-14">
        <svg viewBox="0 0 58 42" xmlns="http://www.w3.org/2000/svg">
            <path d="M10,21 Q25,35 40,20 Q50,8 55,25" />
        </svg>
    </div>
    
    <!-- Left Image - slides in from left -->
    <div class="hero-left"></div>
    
    <!-- Right Image - slides in from right -->
    <div class="hero-right"></div>
</div>

<!-- Burgers Section -->
<section id="burgers" class="burgers-section scroll-reveal">
    <div class="container">
        <h2 class="scroll-animate fade-in-up"><?php echo t('home_burgers_title'); ?></h2>
        <p class="scroll-animate fade-in-up" style="animation-delay: 0.2s;"><?php echo t('home_burgers_subtitle'); ?></p>

        <!-- Burger Cards Grid -->
        <div class="burger-cards">
            <?php
            $currentLang = getCurrentLanguage();
            foreach ($popularItems as $index => $item):
                $nameField = 'name_' . $currentLang;
                $itemName = $item[$nameField] ?? $item['name_de'];
                $imageUrl = !empty($item['image']) ? UPLOADS_URL . '/' . $item['image'] : ASSETS_URL . '/images/cards/' . ($index + 1) . '.png';
            ?>
            <div class="burger-card scroll-animate fade-in hover-lift parallax-card">
                <div class="burger-card-image">
                    <?php if (isset($item['discount_percent']) && $item['discount_percent'] > 0): ?>
                    <div class="discount-badge">-<?php echo $item['discount_percent']; ?>%</div>
                    <?php endif; ?>
                    <img src="<?php echo $imageUrl; ?>" alt="<?php echo htmlspecialchars($itemName); ?>" onerror="this.src='<?php echo ASSETS_URL; ?>/images/cards/default.png'">
                    <div class="add-to-cart-icon" title="<?php echo t('burger_add_to_cart'); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                            <path d="M7 18c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm10 0c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm-9.8-3.2c0 .1.1.2.2.2h9.5c.7 0 1.3-.5 1.5-1.2l1.9-7c.1-.3 0-.6-.2-.8-.2-.2-.5-.3-.8-.3H5.2L4.3 2H1v2h2l3.6 7.6-1.4 2.6c-.4.7-.1 1.6.6 2 .2.1.4.2.6.2h12.3v-2H6.6l1.1-2h7.5l1.5-2H6.4l-.9-2h11.2l-1.5 5.5c-.1.3-.4.5-.7.5H7.7l-.5 1z"/>
                        </svg>
                    </div>
                </div>
                <div class="burger-card-content">
                    <h3 class="burger-card-name"><?php echo htmlspecialchars($itemName); ?></h3>
                    <div class="burger-card-footer">
                        <div class="burger-card-price">
                            <?php if (isset($item['discount_percent']) && $item['discount_percent'] > 0):
                                $originalPrice = $item['price'];
                                $discountedPrice = $originalPrice * (1 - $item['discount_percent'] / 100);
                            ?>
                                <span style="text-decoration: line-through; color: #999; font-size: 0.9em; margin-right: 8px;">
                                    <?php echo formatPrice($originalPrice); ?>
                                </span>
                                <span style="color: #e10000; font-weight: bold;">
                                    <?php echo formatPrice($discountedPrice); ?>
                                </span>
                            <?php else: ?>
                                <?php echo formatPrice($item['price']); ?>
                            <?php endif; ?>
                        </div>
                        <div class="burger-card-buy"><?php echo t('burger_buy_now'); ?></div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if (empty($popularItems)): ?>
            <!-- Fallback to default cards if no popular items -->
            <div class="burger-card scroll-animate fade-in" style="transition-delay: 0.1s;">
                <div class="burger-card-image">
                    <img src="<?php echo ASSETS_URL; ?>/images/cards/1.png" alt="Sample Burger">
                </div>
                <div class="burger-card-content">
                    <h3 class="burger-card-name">Sample Burger</h3>
                    <div class="burger-card-footer">
                        <div class="burger-card-price">€8.99</div>
                        <div class="burger-card-buy"><?php echo t('burger_buy_now'); ?></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- View More Button -->
        <div style="text-align: center; position: relative; z-index: 1; margin-top: 50px; pointer-events: auto;">
            <a href="menu.php" class="view-more-button" style="pointer-events: auto;">
                <?php echo t('view_more_button'); ?>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/>
                </svg>
            </a>
        </div>
    </div>
</section>

<!-- Gourmet Section -->
<?php
// Get homepage about settings
$aboutImage1 = '';
$aboutImage2 = '';
$aboutTitle = t('gourmet_title');
$aboutSubtitle = t('gourmet_subtitle');
$aboutDescription = t('gourmet_description');

try {
    $stmt = $db->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key LIKE 'homepage_about_%'");
    $aboutSettings = [];
    while ($row = $stmt->fetch()) {
        $aboutSettings[$row['setting_key']] = $row['setting_value'];
    }

    $currentLang = getCurrentLanguage();
    $aboutImage1 = $aboutSettings['homepage_about_image_1'] ?? '';
    $aboutImage2 = $aboutSettings['homepage_about_image_2'] ?? '';
    $aboutTitle = $aboutSettings['homepage_about_title_' . $currentLang] ?? $aboutTitle;
    $aboutSubtitle = $aboutSettings['homepage_about_subtitle_' . $currentLang] ?? $aboutSubtitle;
    $aboutDescription = $aboutSettings['homepage_about_description_' . $currentLang] ?? $aboutDescription;
} catch (Exception $e) {
    // Use default translation values
}

$image1Url = !empty($aboutImage1) ? UPLOADS_URL . '/' . $aboutImage1 : ASSETS_URL . '/images/gourmet-burger.jpg';
$image2Url = !empty($aboutImage2) ? UPLOADS_URL . '/' . $aboutImage2 : ASSETS_URL . '/images/gourmet-burger-horizontal.jpg';
?>
<section class="gourmet-section scroll-reveal">
    <div class="gourmet-container">
        <!-- Left Image -->
        <div class="gourmet-image animate-fadeInLeft">
            <img src="<?php echo $image1Url; ?>" alt="<?php echo htmlspecialchars($aboutTitle); ?>" class="hover-scale">
        </div>

        <!-- Right Content -->
        <div class="gourmet-content animate-fadeInRight">
            <h2><?php echo htmlspecialchars($aboutTitle); ?></h2>
            <h3><?php echo htmlspecialchars($aboutSubtitle); ?></h3>
            <p><?php echo nl2br(htmlspecialchars($aboutDescription)); ?></p>

            <!-- Horizontal Burger Image -->
            <div class="gourmet-bottom-image">
                <img src="<?php echo $image2Url; ?>" alt="<?php echo htmlspecialchars($aboutTitle); ?>">
            </div>
        </div>
    </div>
</section>

<!-- Customer Reviews Section -->
<?php
// Get approved reviews from database (latest first)
$dbReviews = [];
try {
    $stmt = $db->query("
        SELECT * FROM reviews
        WHERE is_approved = 1
        ORDER BY created_at DESC
        LIMIT 12
    ");
    $dbReviews = $stmt->fetchAll();
} catch (Exception $e) {
    // If no reviews in database, fallback to translations
}

// If we have reviews from database, use them. Otherwise use translations as fallback
$hasDbReviews = !empty($dbReviews);
?>
<section class="reviews-section">
    <div class="reviews-container">
        <h2><?php echo t('reviews_section_title'); ?></h2>
        <div class="reviews-slider">
        <div class="reviews-track">
            <?php if ($hasDbReviews): ?>
                <?php
                // Display database reviews once for continuous loop
                foreach ($dbReviews as $review) {
                    $stars = (int)$review['rating'];
                    $fullStars = str_repeat('★', $stars);
                    $emptyStars = str_repeat('☆', 5 - $stars);
                ?>
                <div class="review-card">
                    <div class="review-stars"><?php echo $fullStars . $emptyStars; ?></div>
                    <p class="review-text">"<?php echo htmlspecialchars($review['review_text']); ?>"</p>
                    <div class="review-author">- <?php echo htmlspecialchars($review['customer_name']); ?></div>
                </div>
                <?php
                }
                ?>
            <?php else: ?>
                <?php
                // Fallback: Display translation reviews once for continuous loop
                for ($i = 1; $i <= 8; $i++) {
                    $stars = (int)t("review_{$i}_stars");
                    $fullStars = str_repeat('★', $stars);
                    $emptyStars = str_repeat('☆', 5 - $stars);
                ?>
                <div class="review-card">
                    <div class="review-stars"><?php echo $fullStars . $emptyStars; ?></div>
                    <p class="review-text">"<?php echo t("review_{$i}_text"); ?>"</p>
                    <div class="review-author">- <?php echo t("review_{$i}_author"); ?></div>
                </div>
                <?php
                }
                ?>
            <?php endif; ?>
        </div>
        </div>
    </div>
</section>

<!-- Gallery Section -->
<section class="gallery-section">
    <div class="gallery-container">
        <!-- Large Image - Left Side -->
        <div class="gallery-main">
            <img src="<?php echo ASSETS_URL; ?>/images/gallery/main.jpg" alt="Q-Bab Burger Gallery">
        </div>

        <!-- Grid of 4 Images - Right Side -->
        <div class="gallery-grid">
            <div class="gallery-item">
                <img src="<?php echo ASSETS_URL; ?>/images/gallery/1.jpg" alt="Gallery Image 1">
            </div>
            <div class="gallery-item">
                <img src="<?php echo ASSETS_URL; ?>/images/gallery/2.jpg" alt="Gallery Image 2">
            </div>
            <div class="gallery-item">
                <img src="<?php echo ASSETS_URL; ?>/images/gallery/3.jpg" alt="Gallery Image 3">
            </div>
            <div class="gallery-item">
                <img src="<?php echo ASSETS_URL; ?>/images/gallery/4.jpg" alt="Gallery Image 4">
            </div>
        </div>
    </div>
</section>

<!-- Google Maps Section -->
<section class="map-section">
    <div class="map-container">
        <!-- Left Side - Contact Info -->
        <div class="map-info">
            <h2>Besuchen Sie uns</h2>
            <div class="map-details">
                <!-- Address -->
                <div class="map-detail-item">
                    <div class="map-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                            <circle cx="12" cy="10" r="3"></circle>
                        </svg>
                    </div>
                    <div class="map-detail-content">
                        <h3>Adresse</h3>
                        <p>
                            Mühlweg 1<br>
                            86559 Adelzhausen<br>
                            Deutschland
                        </p>
                    </div>
                </div>

                <!-- Phone -->
                <div class="map-detail-item">
                    <div class="map-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                        </svg>
                    </div>
                    <div class="map-detail-content">
                        <h3>Telefon</h3>
                        <p><a href="tel:<?php echo ADMIN_PHONE; ?>">0152 / 05 700 600</a></p>
                    </div>
                </div>

                <!-- Email -->
                <div class="map-detail-item">
                    <div class="map-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                            <polyline points="22,6 12,13 2,6"></polyline>
                        </svg>
                    </div>
                    <div class="map-detail-content">
                        <h3>E-Mail</h3>
                        <p><a href="mailto:info@q-bab.de">info@q-bab.de</a></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side - Google Map -->
        <div class="map-embed">
            <iframe
                src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2663.5!2d11.1!3d48.4!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zNDjCsDI0JzAwLjAiTiAxMcKwMDYnMDAuMCJF!5e0!3m2!1sde!2sde!4v1234567890!5m2!1sde!2sde&q=Mühlweg+1,+86559+Adelzhausen,+Deutschland"
                allowfullscreen=""
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade">
            </iframe>
        </div>
    </div>
</section>

<script>
// Navbar scroll effect - add black background on scroll
const navbar = document.querySelector('.top-navbar');

window.addEventListener('scroll', function() {
    if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
    } else {
        navbar.classList.remove('scrolled');
    }
});

// Inverse mouse parallax effect for burger image, floating circles, and hand-drawn lines
const burger = document.querySelector('.hero-burger');
const floatingCircles = document.querySelectorAll('.floating-circle');
const handDrawnLines = document.querySelectorAll('.hand-drawn-line');
const heroHeader = document.querySelector('.hero-header');

if (burger && heroHeader) {
    heroHeader.addEventListener('mousemove', function(e) {
        const rect = heroHeader.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        
        // Calculate center position
        const centerX = rect.width / 2;
        const centerY = rect.height / 2;
        
        // Burger movement (inverted) - reduced intensity
        const moveX = -(x - centerX) / 35;
        const moveY = -(y - centerY) / 35;
        burger.style.transform = `translate(${moveX}px, calc(-50% + ${moveY}px))`;
        
        // Floating circles movement (inverted, with different speeds) - less movement
        // Bigger circles move less (higher speed value), smaller circles move more (lower speed value)
        floatingCircles.forEach((circle, index) => {
            // Get circle size from computed style
            const circleSize = parseInt(window.getComputedStyle(circle).width);
            // Map size to speed: 3px = speed 50 (slow), 18px = speed 80 (slower)
            const speed = 50 + ((circleSize - 3) * (80 - 50) / (18 - 3));
            const circleMoveX = -(x - centerX) / speed;
            const circleMoveY = -(y - centerY) / speed;
            circle.style.transform = `translate(${circleMoveX}px, ${circleMoveY}px)`;
        });
        
        // Hand-drawn lines movement (inverted, moderate speed) - less movement
        handDrawnLines.forEach((line, index) => {
            const speed = 60 + (index * 3); // Slower, different speed for each line
            const lineMoveX = -(x - centerX) / speed;
            const lineMoveY = -(y - centerY) / speed;
            line.style.transform = `translate(${lineMoveX}px, ${lineMoveY}px)`;
        });
    });
    
    // Reset position when mouse leaves
    heroHeader.addEventListener('mouseleave', function() {
        burger.style.transform = 'translate(0, -50%)';
        floatingCircles.forEach(circle => {
            circle.style.transform = 'translate(0, 0)';
        });
        handDrawnLines.forEach(line => {
            line.style.transform = 'translate(0, 0)';
        });
    });
}

// Menu Section Parallax Effect
const menuSection = document.querySelector('.menu-list-section');
const menuIcons = document.querySelectorAll('.menu-parallax-icon');

if (menuSection && menuIcons.length > 0) {
    menuSection.addEventListener('mousemove', function(e) {
        const rect = menuSection.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;

        // Calculate center position
        const centerX = rect.width / 2;
        const centerY = rect.height / 2;

        // Move icons in opposite direction of mouse (parallax effect)
        menuIcons.forEach((icon, index) => {
            const speed = 40 + (index * 5); // Different speed for each icon
            const moveX = -(x - centerX) / speed;
            const moveY = -(y - centerY) / speed;
            icon.style.transform = `translate(${moveX}px, ${moveY}px)`;
        });
    });

    // Reset position when mouse leaves
    menuSection.addEventListener('mouseleave', function() {
        menuIcons.forEach(icon => {
            icon.style.transform = 'translate(0, 0)';
        });
    });
}
</script>

<!-- Login/Register Modal JavaScript -->
<script src="<?php echo ASSETS_URL; ?>/js/auth-modal.js?v=<?php echo ASSET_VERSION; ?>"></script>

<!-- Translations for Cart -->
<script>
// Check if translations already exists, if not create it
if (typeof translations === 'undefined') {
    var translations = {};
}

// Add cart translations
Object.assign(translations, {
    cart_item_added: <?php echo json_encode([
        'en' => 'Item added to cart!',
        'de' => 'Artikel in den Warenkorb gelegt!',
        'tr' => 'Ürün sepete eklendi!'
    ][getCurrentLanguage()]); ?>,
    cart_item_removed: <?php echo json_encode([
        'en' => 'Item removed from cart',
        'de' => 'Artikel aus dem Warenkorb entfernt',
        'tr' => 'Ürün sepetten çıkarıldı'
    ][getCurrentLanguage()]); ?>,
    cart_empty: <?php echo json_encode([
        'en' => 'Your cart is empty',
        'de' => 'Ihr Warenkorb ist leer',
        'tr' => 'Sepetiniz boş'
    ][getCurrentLanguage()]); ?>,
    cart_total: <?php echo json_encode([
        'en' => 'Total',
        'de' => 'Gesamt',
        'tr' => 'Toplam'
    ][getCurrentLanguage()]); ?>,
    cart_checkout: <?php echo json_encode([
        'en' => 'Proceed to Checkout',
        'de' => 'Zur Kasse',
        'tr' => 'Ödemeye Geç'
    ][getCurrentLanguage()]); ?>,
    cart_continue_shopping: <?php echo json_encode([
        'en' => 'Continue Shopping',
        'de' => 'Weiter einkaufen',
        'tr' => 'Alışverişe Devam Et'
    ][getCurrentLanguage()]); ?>,
    cart_item: <?php echo json_encode([
        'en' => 'Items',
        'de' => 'Artikel',
        'tr' => 'Ürünler'
    ][getCurrentLanguage()]); ?>,
    cart_remove: <?php echo json_encode([
        'en' => 'Remove',
        'de' => 'Entfernen',
        'tr' => 'Kaldır'
    ][getCurrentLanguage()]); ?>,
    checkout_order_summary: <?php echo json_encode([
        'en' => 'Order Summary',
        'de' => 'Bestellübersicht',
        'tr' => 'Sipariş Özeti'
    ][getCurrentLanguage()]); ?>
});
</script>

<!-- Cart JavaScript - Required for add-to-cart functionality -->
<script src="<?php echo ASSETS_URL; ?>/js/cart.js?v=<?php echo ASSET_VERSION; ?>"></script>

<!-- Add to Cart Handler for Burger Cards -->
<script>
(function() {
    function initAddToCartIcons() {
        const addToCartIcons = document.querySelectorAll('.add-to-cart-icon');
        
        addToCartIcons.forEach(function(icon, index) {
            const card = icon.closest('.burger-card');
            if (!card) return;
            
            // Get item data from card
            const nameElement = card.querySelector('.burger-card-name');
            const priceElement = card.querySelector('.burger-card-price');
            const imageElement = card.querySelector('.burger-card-image img');
            
            if (!nameElement || !priceElement) return;
            
            // Extract price (remove currency symbols and parse)
            let priceText = priceElement.textContent.trim();
            // Handle discount prices (get the last price shown)
            const priceSpans = priceElement.querySelectorAll('span');
            if (priceSpans.length > 0) {
                priceText = priceSpans[priceSpans.length - 1].textContent.trim();
            }
            const price = parseFloat(priceText.replace(/[^0-9,\.]/g, '').replace(',', '.'));
            
            const itemData = {
                id: Date.now() + index,
                name: nameElement.textContent.trim(),
                price: price,
                image: imageElement ? imageElement.src : ''
            };
            
            // Add click event
            icon.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Add item to cart using the global cart object
                if (typeof cart !== 'undefined' && cart.addItem) {
                    cart.addItem(itemData);
                }
            });
        });
    }
    
    // Run when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAddToCartIcons);
    } else {
        // DOM already loaded, wait a bit for cart.js to load
        setTimeout(initAddToCartIcons, 500);
    }
})();
</script>

<!-- Logout Handler -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const logoutBtn = document.querySelector('.logout-btn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Möchten Sie sich wirklich abmelden?')) {
                fetch('/api/logout.php')
                    .then(response => response.json())
                    .then(data => {
                        window.location.reload();
                    })
                    .catch(error => {
                        console.error('Logout error:', error);
                        window.location.reload();
                    });
            }
        });
    }
});
</script>

<?php
// Include footer (main.js is loaded in footer.php)
include 'includes/footer.php';
?>

<!-- Sidebar Menu JavaScript - Must load AFTER footer -->
<script>
(function() {
    // Wait a bit to ensure DOM is ready
    setTimeout(function() {
        const menuDotsBtn = document.querySelector('.menu-dots-btn');
        const sidebarMenu = document.getElementById('sidebarMenu');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const sidebarClose = document.getElementById('sidebarClose');

        if (!menuDotsBtn || !sidebarMenu || !sidebarOverlay) return;

        // Open sidebar
        menuDotsBtn.addEventListener('click', function(e) {
            e.preventDefault();

            // Add active classes
            sidebarMenu.classList.add('active');
            sidebarOverlay.classList.add('active');
            menuDotsBtn.classList.add('active');
            document.body.style.overflow = 'hidden';

            // Force styles directly
            sidebarMenu.style.right = '0';
            sidebarMenu.style.zIndex = '10000001';
            sidebarOverlay.style.zIndex = '10000000';
            sidebarOverlay.style.opacity = '1';
            sidebarOverlay.style.visibility = 'visible';
        });

        // Close sidebar when clicking close button
        if (sidebarClose) {
            sidebarClose.addEventListener('click', closeSidebar);
        }

        // Close sidebar when clicking overlay
        sidebarOverlay.addEventListener('click', closeSidebar);

        // Close sidebar with ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sidebarMenu.classList.contains('active')) {
                closeSidebar();
            }
        });

        function closeSidebar() {
            sidebarMenu.classList.remove('active');
            sidebarOverlay.classList.remove('active');
            menuDotsBtn.classList.remove('active');
            document.body.style.overflow = '';
        }

        // Sidebar login button
        const sidebarLoginBtn = document.querySelector('.sidebar-login');
        if (sidebarLoginBtn) {
            sidebarLoginBtn.addEventListener('click', function(e) {
                e.preventDefault();
                closeSidebar();
                // Open auth modal
                setTimeout(function() {
                    document.getElementById('authModal').classList.add('active');
                    document.getElementById('authModalOverlay').classList.add('active');
                    document.getElementById('loginForm').classList.add('active');
                    document.getElementById('registerForm').classList.remove('active');
                    document.body.style.overflow = 'hidden';
                }, 300);
            });
        }

        // Sidebar logout button
        const sidebarLogoutBtn = document.querySelector('.sidebar-logout');
        if (sidebarLogoutBtn) {
            sidebarLogoutBtn.addEventListener('click', function(e) {
                e.preventDefault();
                if (confirm('Möchten Sie sich wirklich abmelden?')) {
                    fetch('/api/logout.php')
                        .then(response => response.json())
                        .then(data => {
                            window.location.reload();
                        })
                        .catch(error => {
                            window.location.reload();
                        });
                }
            });
        }

        // Update cart badge in sidebar
        if (typeof window.cart !== 'undefined') {
            const updateSidebarCartBadge = function() {
                const sidebarCartBadge = document.querySelector('.sidebar-cart-badge');
                if (sidebarCartBadge) {
                    const itemCount = window.cart.getItemCount();
                    sidebarCartBadge.textContent = itemCount;
                    sidebarCartBadge.style.display = itemCount > 0 ? 'flex' : 'none';
                }
            };

            // Initial update
            updateSidebarCartBadge();

            // Listen for cart updates
            document.addEventListener('cartUpdated', updateSidebarCartBadge);
        }
    }, 100);
})();
</script>
