<?php
// Cart Page - Uses Config File
session_start();

// Define constant for includes
define('ALLOW_INCLUDE', true);

// Include config
require_once __DIR__ . '/includes/config.php';

// Get language from session or default to current language
$lang = getCurrentLanguage();

// Get logged-in user data
$user = null;
if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] && isset($_SESSION['user_id'])) {
    try {
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT firstname, lastname, email, phone, address, postal_code, city FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
    } catch (PDOException $e) {
        // Silent fail - form will be empty
        error_log("Cart: Failed to load user data: " . $e->getMessage());
        $user = null;
    }
}

// Cart-specific translations (use global t() function from config.php)
// These translations are already in the global translations file
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('your_order'); ?> - Q-Bab Burger</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/navbar.css?v=<?php echo ASSET_VERSION; ?>">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/auth-modal.css?v=<?php echo ASSET_VERSION; ?>">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f9a825;
            font-family: 'Bebas Neue', Arial, sans-serif;
            padding-top: 0; /* Navbar is fixed, no padding needed */
        }

        /* Navbar styles moved to navbar.css */
        /* Ensure navbar is visible on yellow background */
        .top-navbar {
            background: rgba(0, 0, 0, 0.95) !important;
        }

        /* Container */
        .container {
            max-width: 1400px;
            margin: 120px auto 60px;
            padding: 0 40px;
            display: grid;
            grid-template-columns: 1fr 500px;
            gap: 60px;
        }

        /* Form */
        .billing h1 {
            font-size: 3rem;
            color: #1a1a1a;
            margin-bottom: 40px;
            letter-spacing: 2px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 30px;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            font-size: 1rem;
            color: #1a1a1a;
            margin-bottom: 10px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .form-group label .req {
            color: #e74c3c;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 15px;
            border: none;
            border-bottom: 2px solid #1a1a1a;
            background: transparent;
            font-size: 1rem;
            color: #1a1a1a;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-bottom-color: #e74c3c;
        }

        .form-group textarea {
            width: 100%;
            padding: 15px;
            border: 2px solid #1a1a1a;
            background: white;
            font-size: 1rem;
            color: #1a1a1a;
            font-family: Arial, sans-serif;
            resize: vertical;
            min-height: 100px;
        }

        .section-title {
            font-size: 1.8rem;
            color: #1a1a1a;
            margin: 40px 0 25px 0;
            padding-bottom: 15px;
            border-bottom: 3px solid #f9a825;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        /* Order Box */
        .order-box {
            background: white;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .order-box h2 {
            font-size: 2rem;
            color: #1a1a1a;
            margin-bottom: 30px;
            text-align: center;
            letter-spacing: 2px;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            border-bottom: 1px solid #e0e0e0;
            font-size: 1.1rem;
            gap: 15px;
        }

        .order-item-name {
            flex: 1;
        }

        .order-item-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .qty-btn {
            width: 30px;
            height: 30px;
            border: 2px solid #e10000;
            background: white;
            color: #e10000;
            font-size: 1.2rem;
            font-weight: bold;
            cursor: pointer;
            border-radius: 4px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }

        .qty-btn:hover {
            background: #e10000;
            color: white;
        }

        .qty-display {
            min-width: 40px;
            text-align: center;
            font-weight: bold;
        }

        .remove-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .remove-btn:hover {
            background: #c82333;
        }

        .order-item-price {
            min-width: 80px;
            text-align: right;
            font-weight: bold;
        }

        .summary {
            border-top: 2px solid #1a1a1a;
            margin-top: 20px;
            padding-top: 20px;
            display: flex;
            justify-content: space-between;
            font-size: 1.3rem;
        }

        .summary .amount {
            color: #e74c3c;
        }

        /* Payment */
        .payment {
            background: white;
            padding: 40px;
            margin-top: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .payment h2 {
            font-size: 2rem;
            text-align: center;
            margin-bottom: 30px;
        }

        .payment-opt {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            margin-bottom: 12px;
            border: 2px solid #e0e0e0;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .payment-opt:hover {
            border-color: #f9a825;
            background: #fffbf0;
        }

        .payment-opt input[type="radio"] {
            margin-right: 15px;
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .payment-opt label {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 1;
            cursor: pointer;
            font-size: 1.1rem;
            letter-spacing: 1px;
        }

        .payment-icon {
            height: 30px;
            width: auto;
            object-fit: contain;
        }

        .payment-icon.small {
            height: 24px;
        }

        .submit-btn {
            width: 100%;
            padding: 20px;
            background: #e74c3c;
            color: white;
            border: none;
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.5rem;
            letter-spacing: 2px;
            cursor: pointer;
            margin-top: 20px;
        }

        .submit-btn:hover {
            background: #c0392b;
        }

        .privacy-notice {
            font-family: Arial, sans-serif;
            font-size: 0.75rem;
            color: #666;
            line-height: 1.5;
            margin-top: 15px;
            padding: 12px;
            background: #f5f5f5;
            border-left: 3px solid #f9a825;
            text-align: left;
        }

        .privacy-notice a {
            color: #f9a825;
            text-decoration: underline;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .privacy-notice a:hover {
            color: #e74c3c;
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

        @media (max-width: 1200px) {
            .container {
                grid-template-columns: 1fr;
            }
            .order-box {
                position: static;
            }
        }
    </style>
</head>
<body>
    <!-- Include Navbar -->
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <div class="container">
        <div class="billing">
            <h1><?php echo t('billing_details'); ?></h1>
            <form id="checkoutForm">
                <div class="form-row">
                    <div class="form-group">
                        <label><?php echo t('first_name'); ?> <span class="req">*</span></label>
                        <input type="text" name="first_name" required value="<?php echo $user ? htmlspecialchars($user['firstname']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label><?php echo t('last_name'); ?> <span class="req">*</span></label>
                        <input type="text" name="last_name" required value="<?php echo $user ? htmlspecialchars($user['lastname']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group full">
                    <label><?php echo t('company_name'); ?> (<?php echo t('optional'); ?>)</label>
                    <input type="text" name="company">
                </div>

                <div class="form-group full">
                    <label><?php echo t('country'); ?> <span class="req">*</span></label>
                    <select name="country" required>
                        <option value="DE">Germany (DE)</option>
                        <option value="US">United States (US)</option>
                        <option value="TR">Turkey (TR)</option>
                    </select>
                </div>

                <div class="form-group full">
                    <label><?php echo t('street_address'); ?> <span class="req">*</span></label>
                    <input type="text" name="address" required value="<?php echo $user ? htmlspecialchars($user['address']) : ''; ?>">
                </div>

                <div class="form-group full">
                    <label><?php echo t('city'); ?> <span class="req">*</span></label>
                    <input type="text" name="city" required value="<?php echo $user ? htmlspecialchars($user['city']) : ''; ?>">
                </div>

                <div class="form-group full">
                    <label><?php echo t('zip_code'); ?> <span class="req">*</span></label>
                    <input type="text" name="zip" required value="<?php echo $user ? htmlspecialchars($user['postal_code']) : ''; ?>">
                </div>

                <div class="form-group full">
                    <label><?php echo t('phone'); ?> <span class="req">*</span></label>
                    <input type="tel" name="phone" required value="<?php echo $user ? htmlspecialchars($user['phone']) : ''; ?>">
                </div>

                <div class="form-group full">
                    <label><?php echo t('email'); ?> <span class="req">*</span></label>
                    <input type="email" name="email" required value="<?php echo $user ? htmlspecialchars($user['email']) : ''; ?>">
                </div>

                <!-- Zusätzliche Informationen / Additional Information -->
                <h3 class="section-title"><?php echo t('additional_info'); ?></h3>
                
                <div class="form-group full">
                    <label><?php echo t('order_notes'); ?> (<?php echo t('optional'); ?>)</label>
                    <textarea name="order_notes" placeholder="<?php echo t('order_notes_placeholder'); ?>"></textarea>
                </div>
            </form>
        </div>

        <div>
            <div class="order-box">
                <h2><?php echo t('your_order'); ?></h2>
                <div id="orderItems"></div>
                <div class="summary">
                    <span><?php echo t('total'); ?></span>
                    <span class="amount" id="total">€0.00</span>
                </div>
            </div>

            <div class="payment">
                <h2><?php echo t('payment'); ?></h2>

                <!-- Barzahlung (Cash) -->
                <div class="payment-opt">
                    <input type="radio" name="payment" value="cash" id="cash" checked>
                    <label for="cash">
                        <img class="payment-icon" src="assets/images/payment/barzahlung.png" alt="Barzahlung">
                        <span>Barzahlung</span>
                    </label>
                </div>

                <!-- Kreditkarte (Credit Card) -->
                <div class="payment-opt">
                    <input type="radio" name="payment" value="stripe" id="stripe">
                    <label for="stripe">
                        <img class="payment-icon" src="assets/images/payment/creditcard.png" alt="Kreditkarte">
                        <span>Kreditkarte</span>
                    </label>
                </div>

                <!-- PayPal -->
                <div class="payment-opt">
                    <input type="radio" name="payment" value="paypal" id="paypal">
                    <label for="paypal">
                        <img class="payment-icon" src="assets/images/payment/paypal.png" alt="PayPal">
                        <span>PayPal</span>
                    </label>
                </div>

                <!-- Google Pay -->
                <div class="payment-opt">
                    <input type="radio" name="payment" value="googlepay" id="googlepay">
                    <label for="googlepay">
                        <img class="payment-icon" src="assets/images/payment/googlepay.png" alt="Google Pay">
                        <span>Google Pay</span>
                    </label>
                </div>

                <!-- Apple Pay -->
                <div class="payment-opt">
                    <input type="radio" name="payment" value="applepay" id="applepay">
                    <label for="applepay">
                        <img class="payment-icon" src="assets/images/payment/applepay.png" alt="Apple Pay">
                        <span>Apple Pay</span>
                    </label>
                </div>

                <!-- Klarna -->
                <div class="payment-opt">
                    <input type="radio" name="payment" value="klarna" id="klarna">
                    <label for="klarna">
                        <img class="payment-icon" src="assets/images/payment/klarna.png" alt="Klarna">
                        <span>Klarna</span>
                    </label>
                </div>

                <!-- Privacy Notice -->
                <p class="privacy-notice">
                    <?php 
                    $privacy_text = t('privacy_notice');
                    
                    // Replace privacy policy text with link based on language
                    if ($lang == 'de') {
                        $privacy_link = '<a href="privacy.php" target="_blank">Datenschutzerklärung</a>';
                        $privacy_text = str_replace('Datenschutzerklärung', $privacy_link, $privacy_text);
                    } elseif ($lang == 'en') {
                        $privacy_link = '<a href="privacy.php" target="_blank">privacy policy</a>';
                        $privacy_text = str_replace('privacy policy', $privacy_link, $privacy_text);
                    } elseif ($lang == 'tr') {
                        $privacy_link = '<a href="privacy.php" target="_blank">gizlilik politikamızda</a>';
                        $privacy_text = str_replace('gizlilik politikamızda', $privacy_link, $privacy_text);
                    }
                    
                    echo $privacy_text;
                    ?>
                </p>

                <button class="submit-btn" onclick="handleOrder()"><?php echo t('place_order'); ?></button>
            </div>
        </div>
    </div>

    <script src="assets/js/cart.js"></script>
    <script>
        // CSRF token for API requests
        const CSRF_TOKEN = '<?php echo generateCSRFToken(); ?>';

        function displayOrderSummary() {
            const div = document.getElementById('orderItems');
            const totalEl = document.getElementById('total');

            if (!cart || cart.items.length === 0) {
                div.innerHTML = '<p style="text-align:center;color:#666;padding:20px;"><?php echo t('cart_empty'); ?></p>';
                totalEl.textContent = '€0.00';
                return;
            }

            let html = '';
            cart.items.forEach((item, index) => {
                // Calculate item price with extras
                let itemPrice = parseFloat(item.price);
                let extrasHTML = '';
                
                if (item.extras && item.extras.length > 0) {
                    extrasHTML = '<div style="font-size: 0.85rem; color: #666; margin-top: 8px; padding-left: 12px; border-left: 2px solid #f9a825;">';
                    item.extras.forEach(extra => {
                        extrasHTML += `<div style="margin: 4px 0;">+ ${extra.name} (${cart.formatPrice(extra.price)})</div>`;
                        itemPrice += parseFloat(extra.price);
                    });
                    extrasHTML += '</div>';
                }
                
                const itemTotal = itemPrice * item.quantity;
                
                html += `
                    <div class="order-item" data-index="${index}">
                        <div class="order-item-name">
                            ${item.name}
                            ${extrasHTML}
                        </div>
                        <div class="order-item-controls">
                            <button class="qty-btn" onclick="decreaseQuantity(${index})">−</button>
                            <span class="qty-display">${item.quantity}</span>
                            <button class="qty-btn" onclick="increaseQuantity(${index})">+</button>
                            <button class="remove-btn" onclick="removeItem(${index})">×</button>
                        </div>
                        <div class="order-item-price">${cart.formatPrice(itemTotal)}</div>
                    </div>
                `;
            });
            div.innerHTML = html;
            totalEl.textContent = cart.formatPrice(cart.getTotal());
        }

        function increaseQuantity(index) {
            const item = cart.items[index];
            cart.updateQuantity(item.id, item.quantity + 1);
            displayOrderSummary();
        }

        function decreaseQuantity(index) {
            const item = cart.items[index];
            if (item.quantity > 1) {
                cart.updateQuantity(item.id, item.quantity - 1);
                displayOrderSummary();
            } else {
                removeItem(index);
            }
        }

        function removeItem(index) {
            const item = cart.items[index];

            // Remove item from cart
            cart.items = cart.items.filter(i => i.id !== item.id);
            cart.saveCart();
            cart.updateCartDisplay();

            // Update display immediately
            displayOrderSummary();
        }

        async function handleOrder() {
            event.preventDefault();

            // Validate form
            const form = document.getElementById('checkoutForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            // Check cart
            if (!cart || cart.items.length === 0) {
                alert('<?php echo addslashes(t('cart_empty')); ?>');
                return;
            }

            // Get payment method
            const paymentMethod = document.querySelector('input[name="payment"]:checked');
            if (!paymentMethod) {
                alert('Bitte wählen Sie eine Zahlungsmethode.');
                return;
            }

            // Get form data
            const formData = new FormData(form);
            const orderData = {
                firstname: formData.get('first_name'),
                lastname: formData.get('last_name'),
                company: formData.get('company') || '',
                email: formData.get('email'),
                phone: formData.get('phone'),
                address: formData.get('address'),
                city: formData.get('city'),
                zip: formData.get('zip'),
                country: formData.get('country'),
                order_notes: formData.get('order_notes') || '',
                payment_method: paymentMethod.value,
                cart_items: cart.items
            };

            try {
                // Disable submit button
                const submitBtn = event.target;
                submitBtn.disabled = true;
                submitBtn.textContent = 'Bestellung wird bearbeitet...';

                // Create order
                const response = await fetch('/api/create-order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({...orderData, csrf_token: CSRF_TOKEN})
                });

                const result = await response.json();

                if (result.success) {
                    // Handle different payment methods
                    if (result.payment_method === 'cash') {
                        // Cash payment - redirect directly to confirmation
                        cart.clearCart();
                        window.location.href = result.redirect_url;
                    } else if (result.payment_method === 'stripe') {
                        // Stripe payment - redirect to payment page
                        window.location.href = `/payment-stripe.php?order=${result.order_number}`;
                    } else if (result.payment_method === 'paypal') {
                        // PayPal payment - redirect to payment page
                        window.location.href = `/payment-paypal.php?order=${result.order_number}`;
                    } else if (result.payment_method === 'googlepay' || result.payment_method === 'applepay') {
                        // Digital wallets via Stripe
                        window.location.href = `/payment-stripe.php?order=${result.order_number}&method=${result.payment_method}`;
                    } else if (result.payment_method === 'klarna') {
                        // Klarna payment
                        window.location.href = `/payment-klarna.php?order=${result.order_number}`;
                    }
                } else {
                    // Error
                    alert('Fehler: ' + result.message);
                    submitBtn.disabled = false;
                    submitBtn.textContent = '<?php echo addslashes(t('place_order')); ?>';
                }
            } catch (error) {
                console.error('Order error:', error);
                alert('Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.');
                const submitBtn = event.target;
                submitBtn.disabled = false;
                submitBtn.textContent = '<?php echo addslashes(t('place_order')); ?>';
            }
        }

        document.addEventListener('DOMContentLoaded', displayOrderSummary);
    </script>
    
    <!-- Auth Modal JS -->
    <script src="<?php echo ASSETS_URL; ?>/js/auth-modal.js?v=<?php echo ASSET_VERSION; ?>"></script>

    <!-- Navbar scroll effect -->
    <script>
        // Add black background to navbar on scroll
        const navbar = document.querySelector('.top-navbar');
        
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    </script>
</body>
</html>
