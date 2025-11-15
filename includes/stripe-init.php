<?php
/**
 * Stripe Library Initialization
 * Manual include for Stripe PHP library
 */

if (!defined('ALLOW_INCLUDE')) {
    die('Direct access not permitted');
}

// Try to load Stripe via Composer first
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    // Manual Stripe library loading
    // Download from: https://github.com/stripe/stripe-php/releases/latest
    // Extract to: includes/stripe-php/

    $stripePath = __DIR__ . '/stripe-php/init.php';

    if (file_exists($stripePath)) {
        require_once $stripePath;
    } else {
        die('Stripe library not found. Please install via Composer or download manually.');
    }
}

// Set Stripe API key
\Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);
