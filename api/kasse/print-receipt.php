<?php
/**
 * TSE-Compliant Receipt Generator
 * Generates printable receipt with TSE signature and QR code
 *
 * @author Q-Bab Kasse System
 */

define('ALLOW_INCLUDE', true);

header('Content-Type: text/html; charset=UTF-8');

require_once __DIR__ . '/../../includes/config.php';

// Get order ID from parameter
$orderId = $_GET['order_id'] ?? null;

if (!$orderId) {
    die('Order ID required');
}

// Get order data from database
$db = getDBConnection();
$stmt = $db->prepare("
    SELECT
        o.*,
        GROUP_CONCAT(
            CONCAT(oi.product_name, '|', oi.quantity, '|', oi.product_price, '|', COALESCE(oi.extras_json, ''))
            SEPARATOR ';;'
        ) as items_data
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE o.id = ?
    GROUP BY o.id
");
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die('Order not found');
}

// Parse items
$items = [];
if ($order['items_data']) {
    $itemsRaw = explode(';;', $order['items_data']);
    foreach ($itemsRaw as $itemRaw) {
        $parts = explode('|', $itemRaw);
        if (count($parts) >= 3) {
            $items[] = [
                'name' => $parts[0],
                'quantity' => $parts[1],
                'price' => $parts[2],
                'extras' => isset($parts[3]) ? json_decode($parts[3], true) : []
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beleg - <?php echo htmlspecialchars($order['order_number']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
        }

        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            line-height: 1.4;
            padding: 20px;
            background: #f5f5f5;
        }

        .receipt {
            width: 300px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px dashed #000;
            padding-bottom: 15px;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .company-info {
            font-size: 10px;
            margin-bottom: 2px;
        }

        .receipt-title {
            font-size: 16px;
            font-weight: bold;
            margin: 15px 0 10px 0;
            text-align: center;
        }

        .order-info {
            margin-bottom: 15px;
            font-size: 11px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
        }

        .items {
            margin: 15px 0;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 10px 0;
        }

        .item {
            margin-bottom: 8px;
        }

        .item-line {
            display: flex;
            justify-content: space-between;
        }

        .item-name {
            flex: 1;
        }

        .item-qty {
            width: 30px;
            text-align: right;
        }

        .item-price {
            width: 60px;
            text-align: right;
        }

        .item-extras {
            font-size: 10px;
            margin-left: 10px;
            color: #666;
        }

        .totals {
            margin: 15px 0;
        }

        .total-line {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }

        .total-line.grand-total {
            font-size: 14px;
            font-weight: bold;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 2px solid #000;
        }

        .payment-info {
            margin: 15px 0;
            padding: 10px 0;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
        }

        .tse-section {
            margin: 20px 0;
            padding: 15px;
            background: #f9f9f9;
            border: 2px solid #000;
        }

        .tse-title {
            font-weight: bold;
            text-align: center;
            margin-bottom: 10px;
            font-size: 13px;
        }

        .tse-field {
            font-size: 9px;
            margin-bottom: 5px;
            word-break: break-all;
        }

        .tse-label {
            font-weight: bold;
        }

        .qr-code {
            text-align: center;
            margin: 15px 0;
        }

        .qr-placeholder {
            width: 200px;
            height: 200px;
            margin: 0 auto;
            background: white;
            border: 2px solid #000;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px dashed #000;
            font-size: 10px;
        }

        .footer-message {
            margin: 10px 0;
            font-style: italic;
        }

        .print-button {
            margin: 20px auto;
            display: block;
            padding: 12px 24px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        .print-button:hover {
            background: #388E3C;
        }
    </style>
</head>
<body>
    <button class="print-button no-print" onclick="window.print()">🖨️ Beleg Drucken</button>

    <div class="receipt">
        <!-- Header -->
        <div class="header">
            <div class="company-name">Q-BAB BURGER</div>
            <div class="company-info">Musterstraße 123</div>
            <div class="company-info">80331 München</div>
            <div class="company-info">Tel: +49 89 1234567</div>
            <div class="company-info">www.q-bab.de</div>
        </div>

        <!-- Receipt Title -->
        <div class="receipt-title">KASSENBELEG</div>

        <!-- Order Info -->
        <div class="order-info">
            <div class="info-row">
                <span>Beleg-Nr:</span>
                <span><?php echo htmlspecialchars($order['order_number']); ?></span>
            </div>
            <div class="info-row">
                <span>Datum:</span>
                <span><?php echo date('d.m.Y H:i:s', strtotime($order['created_at'])); ?></span>
            </div>
            <div class="info-row">
                <span>Kassierer:</span>
                <span><?php echo htmlspecialchars($order['cashier_name'] ?? 'Kasse'); ?></span>
            </div>
        </div>

        <!-- Items -->
        <div class="items">
            <?php foreach ($items as $item): ?>
            <div class="item">
                <div class="item-line">
                    <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                    <div class="item-qty"><?php echo $item['quantity']; ?>x</div>
                    <div class="item-price"><?php echo number_format($item['price'] * $item['quantity'], 2, ',', '.'); ?> €</div>
                </div>
                <?php if (!empty($item['extras'])): ?>
                    <?php foreach ($item['extras'] as $extra): ?>
                    <div class="item-extras">
                        + <?php echo htmlspecialchars($extra['name']); ?>
                        (<?php echo number_format($extra['price'], 2, ',', '.'); ?> €)
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Totals -->
        <div class="totals">
            <div class="total-line">
                <span>Zwischensumme:</span>
                <span><?php echo number_format($order['subtotal'], 2, ',', '.'); ?> €</span>
            </div>
            <div class="total-line">
                <span>MwSt. (19%):</span>
                <span><?php echo number_format($order['tax'], 2, ',', '.'); ?> €</span>
            </div>
            <div class="total-line grand-total">
                <span>GESAMT:</span>
                <span><?php echo number_format($order['total_amount'], 2, ',', '.'); ?> €</span>
            </div>
        </div>

        <!-- Payment Info -->
        <div class="payment-info">
            <div class="info-row">
                <span>Zahlungsart:</span>
                <span><?php echo $order['payment_method'] === 'CASH' ? 'Bargeld' : 'Karte'; ?></span>
            </div>
            <?php if ($order['payment_method'] === 'CASH' && $order['cash_given']): ?>
            <div class="info-row">
                <span>Gegeben:</span>
                <span><?php echo number_format($order['cash_given'], 2, ',', '.'); ?> €</span>
            </div>
            <div class="info-row">
                <span>Rückgeld:</span>
                <span><?php echo number_format($order['cash_change'], 2, ',', '.'); ?> €</span>
            </div>
            <?php endif; ?>
        </div>

        <!-- TSE Section -->
        <?php if ($order['tse_transaction_id']): ?>
        <div class="tse-section">
            <div class="tse-title">TSE-SIGNATUR (KassenSichV)</div>

            <div class="tse-field">
                <span class="tse-label">TSE-Seriennummer:</span><br>
                <?php echo htmlspecialchars(substr($order['tse_transaction_id'], 0, 40)) . '...'; ?>
            </div>

            <div class="tse-field">
                <span class="tse-label">Signatur:</span><br>
                <?php echo htmlspecialchars(substr($order['tse_signature'], 0, 50)) . '...'; ?>
            </div>

            <div class="tse-field">
                <span class="tse-label">TSE-Transaktion:</span><br>
                <?php echo htmlspecialchars($order['tse_transaction_id']); ?>
            </div>

            <?php if ($order['tse_qr_code']): ?>
            <div class="qr-code">
                <div class="tse-label" style="margin-bottom: 10px;">QR-Code für Finanzamt:</div>
                <div class="qr-placeholder">
                    <canvas id="qrcode"></canvas>
                </div>
                <div style="font-size: 8px; margin-top: 10px;">
                    Scan mit Finanzamt-App
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="tse-section">
            <div class="tse-title">⚠️ OFFLINE-MODUS</div>
            <div style="text-align: center; font-size: 10px;">
                Dieser Beleg wurde im Offline-Modus erstellt<br>
                und ist NICHT TSE-signiert.
            </div>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-message">
                Vielen Dank für Ihren Besuch!<br>
                Wir freuen uns auf Ihren nächsten Besuch!
            </div>
            <div style="margin-top: 10px;">
                Steuernummer: DE123456789<br>
                USt-IdNr: DE987654321
            </div>
        </div>
    </div>

    <?php if ($order['tse_qr_code']): ?>
    <!-- QR Code Generator -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
        // Generate QR code
        const qrData = <?php echo json_encode($order['tse_qr_code']); ?>;

        if (qrData) {
            new QRCode(document.getElementById("qrcode"), {
                text: qrData,
                width: 200,
                height: 200,
                colorDark: "#000000",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.M
            });
        }
    </script>
    <?php endif; ?>
</body>
</html>
