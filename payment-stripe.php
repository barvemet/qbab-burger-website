<?php
/**
 * Stripe Payment Page
 * Handles credit card payments via Stripe
 */
session_start();

// Define constant for includes
define('ALLOW_INCLUDE', true);

// Include config
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/payment-config.php';
require_once __DIR__ . '/includes/stripe-init.php';

// Get language
$lang = getCurrentLanguage();

// Get order number from URL
$orderNumber = $_GET['order'] ?? null;
$paymentMethod = $_GET['method'] ?? 'card'; // card, googlepay, applepay

if (!$orderNumber) {
    header('Location: /cart.php');
    exit;
}

// Get order details
try {
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT * FROM orders WHERE order_number = ?");
    $stmt->execute([$orderNumber]);
    $order = $stmt->fetch();

    if (!$order) {
        header('Location: /cart.php');
        exit;
    }

    // Check if already paid
    if ($order['payment_status'] === 'completed') {
        header('Location: /order-confirmation.php?order=' . $orderNumber);
        exit;
    }

    // Create Stripe Payment Intent
    $paymentIntent = \Stripe\PaymentIntent::create([
        'amount' => round($order['total_amount'] * 100), // Convert to cents
        'currency' => 'eur',
        'payment_method_types' => ['card'],
        'metadata' => [
            'order_number' => $orderNumber,
            'customer_email' => $order['customer_email']
        ]
    ]);

} catch (Exception $e) {
    error_log('Stripe payment error: ' . $e->getMessage());
    header('Location: /cart.php?error=payment_failed');
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zahlung - Q-Bab Burger</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
    <script src="https://js.stripe.com/v3/"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f9a825;
            font-family: 'Bebas Neue', Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .payment-container {
            max-width: 500px;
            width: 100%;
            background: white;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo img {
            height: 60px;
        }

        h1 {
            font-size: 2.5rem;
            color: #1a1a1a;
            margin-bottom: 10px;
            letter-spacing: 2px;
            text-align: center;
        }

        .order-info {
            background: #f5f5f5;
            padding: 20px;
            margin-bottom: 30px;
            font-family: Arial, sans-serif;
        }

        .order-info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 0.95rem;
        }

        .order-info-row.total {
            font-size: 1.2rem;
            font-weight: bold;
            color: #e74c3c;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 2px solid #ddd;
        }

        .payment-method-info {
            text-align: center;
            margin-bottom: 30px;
            font-family: Arial, sans-serif;
            color: #666;
        }

        .payment-method-info svg {
            height: 30px;
            margin-bottom: 10px;
        }

        #card-element {
            background: white;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        #card-element.StripeElement--focus {
            border-color: #f9a825;
        }

        #card-errors {
            color: #e74c3c;
            margin-top: 10px;
            font-family: Arial, sans-serif;
            font-size: 0.9rem;
            min-height: 20px;
        }

        .submit-btn {
            width: 100%;
            padding: 18px;
            background: #e74c3c;
            color: white;
            border: none;
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.5rem;
            letter-spacing: 2px;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 20px;
        }

        .submit-btn:hover:not(:disabled) {
            background: #c0392b;
        }

        .submit-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        .spinner {
            display: none;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .processing .spinner {
            display: block;
        }

        .processing .btn-text {
            display: none;
        }

        .security-notice {
            font-family: Arial, sans-serif;
            font-size: 0.75rem;
            color: #666;
            text-align: center;
            margin-top: 20px;
            padding: 15px;
            background: #f5f5f5;
            border-left: 3px solid #28a745;
        }

        .security-notice svg {
            width: 16px;
            height: 16px;
            vertical-align: middle;
            margin-right: 5px;
        }

        .cancel-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #666;
            text-decoration: none;
            font-family: Arial, sans-serif;
            font-size: 0.9rem;
        }

        .cancel-link:hover {
            color: #1a1a1a;
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="logo">
            <img src="assets/images/logo.png" alt="Q-Bab Burger">
        </div>

        <h1>Sichere Zahlung</h1>

        <!-- Order Information -->
        <div class="order-info">
            <div class="order-info-row">
                <span>Bestellnummer:</span>
                <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
            </div>
            <div class="order-info-row">
                <span>Zwischensumme:</span>
                <span>€<?php echo number_format($order['subtotal'], 2, ',', '.'); ?></span>
            </div>
            <div class="order-info-row">
                <span>MwSt. (19%):</span>
                <span>€<?php echo number_format($order['tax'], 2, ',', '.'); ?></span>
            </div>
            <div class="order-info-row total">
                <span>Gesamtbetrag:</span>
                <span>€<?php echo number_format($order['total_amount'], 2, ',', '.'); ?></span>
            </div>
        </div>

        <!-- Payment Method Info -->
        <div class="payment-method-info">
            <svg viewBox="0 0 48 32" fill="none">
                <rect width="48" height="32" rx="4" fill="#1434CB"></rect>
                <circle cx="18" cy="16" r="7" fill="#EB001B"></circle>
                <circle cx="30" cy="16" r="7" fill="#FF5F00"></circle>
            </svg>
            <p>Zahlung mit Kreditkarte über Stripe</p>
        </div>

        <!-- Payment Form -->
        <form id="payment-form">
            <div id="card-element"></div>
            <div id="card-errors"></div>

            <button type="submit" class="submit-btn" id="submit-btn">
                <span class="btn-text">Jetzt bezahlen €<?php echo number_format($order['total_amount'], 2, ',', '.'); ?></span>
                <div class="spinner"></div>
            </button>
        </form>

        <div class="security-notice">
            <svg fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path>
            </svg>
            Ihre Zahlung wird sicher über Stripe verarbeitet. Ihre Karteninformationen werden verschlüsselt übertragen.
        </div>

        <a href="/cart.php" class="cancel-link">← Zurück zum Warenkorb</a>
    </div>

    <script>
        // Initialize Stripe
        const stripe = Stripe('<?php echo STRIPE_PUBLISHABLE_KEY; ?>');
        const elements = stripe.elements();

        // Create card element
        const cardElement = elements.create('card', {
            style: {
                base: {
                    fontSize: '16px',
                    color: '#1a1a1a',
                    fontFamily: 'Arial, sans-serif',
                    '::placeholder': {
                        color: '#aab7c4',
                    },
                },
                invalid: {
                    color: '#e74c3c',
                },
            },
        });

        cardElement.mount('#card-element');

        // Handle real-time validation errors
        cardElement.on('change', function(event) {
            const displayError = document.getElementById('card-errors');
            if (event.error) {
                displayError.textContent = event.error.message;
            } else {
                displayError.textContent = '';
            }
        });

        // Handle form submission
        const form = document.getElementById('payment-form');
        const submitBtn = document.getElementById('submit-btn');

        form.addEventListener('submit', async function(event) {
            event.preventDefault();

            // Disable button and show loading
            submitBtn.disabled = true;
            submitBtn.classList.add('processing');

            try {
                // Confirm the payment
                const {error, paymentIntent} = await stripe.confirmCardPayment(
                    '<?php echo $paymentIntent->client_secret; ?>',
                    {
                        payment_method: {
                            card: cardElement,
                            billing_details: {
                                name: '<?php echo htmlspecialchars($order['customer_firstname'] . ' ' . $order['customer_lastname']); ?>',
                                email: '<?php echo htmlspecialchars($order['customer_email']); ?>'
                            }
                        }
                    }
                );

                if (error) {
                    // Show error
                    document.getElementById('card-errors').textContent = error.message;
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('processing');
                } else if (paymentIntent.status === 'succeeded') {
                    // Payment successful - update order and redirect
                    const response = await fetch('/api/update-payment-status-v2.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            order_number: '<?php echo $orderNumber; ?>',
                            payment_intent_id: paymentIntent.id,
                            payment_status: 'completed'
                        })
                    });

                    const result = await response.json();

                    if (result.success) {
                        // Clear cart and redirect
                        localStorage.removeItem('cart');
                        window.location.href = '/order-confirmation.php?order=<?php echo $orderNumber; ?>';
                    } else {
                        throw new Error('Failed to update order status');
                    }
                }
            } catch (err) {
                console.error('Payment error:', err);
                document.getElementById('card-errors').textContent = 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.';
                submitBtn.disabled = false;
                submitBtn.classList.remove('processing');
            }
        });
    </script>
</body>
</html>
