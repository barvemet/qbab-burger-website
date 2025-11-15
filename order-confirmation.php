<?php
/**
 * Order Confirmation Page
 * Shows order details after successful order placement
 */
session_start();

// Define constant for includes
define('ALLOW_INCLUDE', true);

// Include config
require_once __DIR__ . '/includes/config.php';

// Get language
$lang = getCurrentLanguage();

// Get order number from URL
$orderNumber = $_GET['order'] ?? null;

if (!$orderNumber) {
    header('Location: /menu.php');
    exit;
}

// Get order details
try {
    $db = getDBConnection();
    $stmt = $db->prepare("
        SELECT
            o.*,
            COUNT(oi.id) as item_count
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.order_number = ?
        GROUP BY o.id
    ");
    $stmt->execute([$orderNumber]);
    $order = $stmt->fetch();

    if (!$order) {
        header('Location: /menu.php');
        exit;
    }

    // Get order items
    $stmt = $db->prepare("
        SELECT * FROM order_items WHERE order_id = ? ORDER BY id
    ");
    $stmt->execute([$order['id']]);
    $orderItems = $stmt->fetchAll();

} catch (Exception $e) {
    error_log('Order confirmation error: ' . $e->getMessage());
    header('Location: /menu.php');
    exit;
}

// Clear cart after successful order
if (isset($_SESSION['cart'])) {
    unset($_SESSION['cart']);
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bestellbestätigung - Q-Bab Burger</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
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
        }

        /* Navbar */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.95);
            padding: 20px 50px;
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 30px;
        }

        .logo img {
            height: 50px;
        }

        .back-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            color: white;
            text-decoration: none;
            font-size: 1.1rem;
            letter-spacing: 1px;
            padding: 10px 20px;
            border-radius: 5px;
            transition: all 0.3s;
        }

        .back-btn:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        /* Container */
        .container {
            max-width: 900px;
            margin: 120px auto 60px;
            padding: 0 40px;
        }

        /* Success Message */
        .success-box {
            background: white;
            padding: 60px 40px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            text-align: center;
            margin-bottom: 40px;
        }

        .success-icon {
            width: 100px;
            height: 100px;
            background: #28a745;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
        }

        .success-icon svg {
            width: 60px;
            height: 60px;
            stroke: white;
            stroke-width: 3;
            fill: none;
        }

        .success-box h1 {
            font-size: 3rem;
            color: #1a1a1a;
            margin-bottom: 20px;
            letter-spacing: 2px;
        }

        .success-box p {
            font-family: Arial, sans-serif;
            font-size: 1.1rem;
            color: #666;
            margin-bottom: 15px;
            line-height: 1.6;
        }

        .order-number {
            font-size: 1.5rem;
            color: #e74c3c;
            font-family: 'Bebas Neue', sans-serif;
            letter-spacing: 2px;
            margin: 20px 0;
        }

        /* Order Details */
        .order-details {
            background: white;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 40px;
        }

        .order-details h2 {
            font-size: 2rem;
            color: #1a1a1a;
            margin-bottom: 30px;
            letter-spacing: 2px;
            border-bottom: 3px solid #f9a825;
            padding-bottom: 15px;
        }

        .detail-row {
            display: grid;
            grid-template-columns: 200px 1fr;
            padding: 15px 0;
            border-bottom: 1px solid #e0e0e0;
            font-family: Arial, sans-serif;
        }

        .detail-label {
            font-weight: bold;
            color: #1a1a1a;
        }

        .detail-value {
            color: #666;
        }

        /* Order Items */
        .order-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            border-bottom: 1px solid #e0e0e0;
            font-size: 1.1rem;
        }

        .item-info {
            flex: 1;
        }

        .item-name {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .item-details {
            font-family: Arial, sans-serif;
            font-size: 0.9rem;
            color: #666;
        }

        .item-price {
            font-weight: bold;
            color: #1a1a1a;
            min-width: 100px;
            text-align: right;
        }

        /* Summary */
        .order-summary {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #1a1a1a;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-size: 1.1rem;
        }

        .summary-row.total {
            font-size: 1.5rem;
            color: #e74c3c;
            margin-top: 10px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
        }

        /* Actions */
        .action-buttons {
            display: flex;
            gap: 20px;
            margin-top: 40px;
            justify-content: center;
        }

        .btn {
            padding: 18px 40px;
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.3rem;
            letter-spacing: 2px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #e74c3c;
            color: white;
        }

        .btn-primary:hover {
            background: #c0392b;
        }

        .btn-secondary {
            background: white;
            color: #1a1a1a;
            border: 2px solid #1a1a1a;
        }

        .btn-secondary:hover {
            background: #1a1a1a;
            color: white;
        }

        @media (max-width: 768px) {
            .detail-row {
                grid-template-columns: 1fr;
                gap: 5px;
            }

            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="index.php" class="logo">
            <img src="assets/images/logo.png" alt="Q-Bab Burger">
        </a>
        <a href="menu.php" class="back-btn">
            ← Zurück zum Menü
        </a>
    </nav>

    <div class="container">
        <!-- Success Message -->
        <div class="success-box">
            <div class="success-icon">
                <svg viewBox="0 0 24 24">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
            </div>
            <h1>Vielen Dank!</h1>
            <p>Ihre Bestellung wurde erfolgreich aufgegeben.</p>
            <p>Sie erhalten in Kürze eine Bestätigungs-E-Mail an <strong><?php echo htmlspecialchars($order['customer_email']); ?></strong></p>
            <div class="order-number">Bestellnummer: <?php echo htmlspecialchars($order['order_number']); ?></div>
        </div>

        <!-- Order Details -->
        <div class="order-details">
            <h2>Bestelldetails</h2>

            <div class="detail-row">
                <div class="detail-label">Bestellnummer:</div>
                <div class="detail-value"><?php echo htmlspecialchars($order['order_number']); ?></div>
            </div>

            <div class="detail-row">
                <div class="detail-label">Bestelldatum:</div>
                <div class="detail-value"><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?> Uhr</div>
            </div>

            <div class="detail-row">
                <div class="detail-label">Status:</div>
                <div class="detail-value">
                    <?php
                    $statusLabels = [
                        'pending' => 'Ausstehend',
                        'confirmed' => 'Bestätigt',
                        'preparing' => 'In Vorbereitung',
                        'ready' => 'Bereit zur Abholung',
                        'delivered' => 'Geliefert',
                        'cancelled' => 'Storniert'
                    ];
                    echo $statusLabels[$order['order_status']] ?? $order['order_status'];
                    ?>
                </div>
            </div>

            <div class="detail-row">
                <div class="detail-label">Zahlungsmethode:</div>
                <div class="detail-value">
                    <?php
                    $paymentLabels = [
                        'cash' => 'Barzahlung bei Lieferung',
                        'creditcard' => 'Kreditkarte',
                        'paypal' => 'PayPal',
                        'googlepay' => 'Google Pay',
                        'applepay' => 'Apple Pay',
                        'klarna' => 'Klarna'
                    ];
                    echo $paymentLabels[$order['payment_method']] ?? $order['payment_method'];
                    ?>
                </div>
            </div>
        </div>

        <!-- Delivery Address -->
        <div class="order-details">
            <h2>Lieferadresse</h2>

            <div class="detail-row">
                <div class="detail-label">Name:</div>
                <div class="detail-value"><?php echo htmlspecialchars($order['customer_firstname'] . ' ' . $order['customer_lastname']); ?></div>
            </div>

            <?php if (!empty($order['customer_company'])): ?>
            <div class="detail-row">
                <div class="detail-label">Firma:</div>
                <div class="detail-value"><?php echo htmlspecialchars($order['customer_company']); ?></div>
            </div>
            <?php endif; ?>

            <div class="detail-row">
                <div class="detail-label">Adresse:</div>
                <div class="detail-value"><?php echo htmlspecialchars($order['delivery_address']); ?></div>
            </div>

            <div class="detail-row">
                <div class="detail-label">Stadt:</div>
                <div class="detail-value"><?php echo htmlspecialchars($order['delivery_postal_code'] . ' ' . $order['delivery_city']); ?></div>
            </div>

            <div class="detail-row">
                <div class="detail-label">Telefon:</div>
                <div class="detail-value"><?php echo htmlspecialchars($order['customer_phone']); ?></div>
            </div>

            <div class="detail-row">
                <div class="detail-label">E-Mail:</div>
                <div class="detail-value"><?php echo htmlspecialchars($order['customer_email']); ?></div>
            </div>

            <?php if (!empty($order['order_notes'])): ?>
            <div class="detail-row">
                <div class="detail-label">Anmerkungen:</div>
                <div class="detail-value"><?php echo nl2br(htmlspecialchars($order['order_notes'])); ?></div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Order Items -->
        <div class="order-details">
            <h2>Bestellte Produkte</h2>

            <?php foreach ($orderItems as $item): ?>
            <div class="order-item">
                <div class="item-info">
                    <div class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                    <div class="item-details">
                        Menge: <?php echo $item['quantity']; ?> ×
                        €<?php echo number_format($item['product_price'], 2, ',', '.'); ?>
                    </div>
                </div>
                <div class="item-price">€<?php echo number_format($item['subtotal'], 2, ',', '.'); ?></div>
            </div>
            <?php endforeach; ?>

            <!-- Order Summary -->
            <div class="order-summary">
                <div class="summary-row">
                    <span>Zwischensumme:</span>
                    <span>€<?php echo number_format($order['subtotal'], 2, ',', '.'); ?></span>
                </div>

                <div class="summary-row">
                    <span>MwSt. (19%):</span>
                    <span>€<?php echo number_format($order['tax'], 2, ',', '.'); ?></span>
                </div>

                <div class="summary-row">
                    <span>Versandkosten:</span>
                    <span>€<?php echo number_format($order['shipping_cost'], 2, ',', '.'); ?></span>
                </div>

                <div class="summary-row total">
                    <span>Gesamt:</span>
                    <span>€<?php echo number_format($order['total_amount'], 2, ',', '.'); ?></span>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="menu.php" class="btn btn-primary">Weitere Bestellungen aufgeben</a>
            <a href="index.php" class="btn btn-secondary">Zur Startseite</a>
        </div>
    </div>

    <script>
        // Clear localStorage cart
        localStorage.removeItem('cart');
    </script>
</body>
</html>
