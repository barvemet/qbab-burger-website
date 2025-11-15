<?php
/**
 * Payment Configuration
 * Store payment gateway credentials
 */

if (!defined('ALLOW_INCLUDE')) {
    die('Direct access not permitted');
}

// ========================================
// PAYMENT GATEWAY SETTINGS
// ========================================

// Load environment variables
require_once __DIR__ . '/config.php';

// Stripe Configuration (from .env file)
if (!defined('STRIPE_PUBLISHABLE_KEY')) {
    define('STRIPE_PUBLISHABLE_KEY', getenv('STRIPE_PUBLISHABLE_KEY') ?: 'pk_test_YOUR_PUBLISHABLE_KEY_HERE');
}
if (!defined('STRIPE_SECRET_KEY')) {
    define('STRIPE_SECRET_KEY', getenv('STRIPE_SECRET_KEY') ?: 'sk_test_YOUR_SECRET_KEY_HERE');
}
if (!defined('STRIPE_WEBHOOK_SECRET')) {
    define('STRIPE_WEBHOOK_SECRET', getenv('STRIPE_WEBHOOK_SECRET') ?: 'whsec_YOUR_WEBHOOK_SECRET_HERE');
}
if (!defined('STRIPE_CURRENCY')) {
    define('STRIPE_CURRENCY', 'eur');
}

// PayPal Configuration (from .env file)
if (!defined('PAYPAL_CLIENT_ID')) {
    define('PAYPAL_CLIENT_ID', getenv('PAYPAL_CLIENT_ID') ?: 'YOUR_PAYPAL_CLIENT_ID_HERE');
}
if (!defined('PAYPAL_SECRET')) {
    define('PAYPAL_SECRET', getenv('PAYPAL_SECRET') ?: 'YOUR_PAYPAL_SECRET_HERE');
}
if (!defined('PAYPAL_MODE')) {
    define('PAYPAL_MODE', getenv('PAYPAL_MODE') ?: 'sandbox');
}

// Email Configuration (from .env file)
if (!defined('SMTP_HOST')) {
    define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
}
if (!defined('SMTP_PORT')) {
    define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
}
if (!defined('SMTP_USERNAME')) {
    define('SMTP_USERNAME', getenv('SMTP_USERNAME') ?: 'your-email@gmail.com');
}
if (!defined('SMTP_PASSWORD')) {
    define('SMTP_PASSWORD', getenv('SMTP_PASSWORD') ?: 'your-app-password');
}
if (!defined('SMTP_FROM_EMAIL')) {
    define('SMTP_FROM_EMAIL', getenv('SMTP_USERNAME') ?: 'noreply@q-bab.de');
}
if (!defined('SMTP_FROM_NAME')) {
    define('SMTP_FROM_NAME', 'Q-Bab Burger');
}

// Admin Notification Email (from .env file)
if (!defined('ADMIN_EMAIL')) {
    define('ADMIN_EMAIL', getenv('ADMIN_EMAIL') ?: 'admin@q-bab.de');
}

// Order Settings
define('ORDER_PREFIX', 'QB'); // Order number prefix (e.g., QB-2024-0001)
define('TAX_RATE', 0.19); // 19% VAT in Germany
define('SHIPPING_COST', 0.00); // Free shipping for now
define('MIN_ORDER_AMOUNT', 10.00); // Minimum order amount

// Payment Methods Available
define('PAYMENT_METHODS', [
    'cash' => [
        'enabled' => true,
        'name' => 'Barzahlung',
        'description' => 'Bar bei Lieferung',
        'fee' => 0
    ],
    'stripe' => [
        'enabled' => true,
        'name' => 'Kreditkarte',
        'description' => 'Visa, Mastercard, Amex',
        'fee' => 0
    ],
    'paypal' => [
        'enabled' => true,
        'name' => 'PayPal',
        'description' => 'Bezahlen mit PayPal',
        'fee' => 0
    ],
    'googlepay' => [
        'enabled' => false, // Will be enabled via Stripe
        'name' => 'Google Pay',
        'description' => 'Schnelle Zahlung mit Google Pay',
        'fee' => 0
    ],
    'applepay' => [
        'enabled' => false, // Will be enabled via Stripe
        'name' => 'Apple Pay',
        'description' => 'Schnelle Zahlung mit Apple Pay',
        'fee' => 0
    ],
    'klarna' => [
        'enabled' => false, // Requires separate Klarna account
        'name' => 'Klarna',
        'description' => 'Kauf auf Rechnung',
        'fee' => 0
    ]
]);

// Helper function to get payment method info
function getPaymentMethod($method) {
    $methods = PAYMENT_METHODS;
    return $methods[$method] ?? null;
}

// Helper function to check if payment method is enabled
function isPaymentMethodEnabled($method) {
    $paymentMethod = getPaymentMethod($method);
    return $paymentMethod && $paymentMethod['enabled'];
}
