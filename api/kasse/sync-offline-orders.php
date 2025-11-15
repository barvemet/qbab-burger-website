<?php
/**
 * Sync Offline Orders API - Kasse System
 * Processes queued offline orders when connection is restored
 * Offline siparişleri senkronize et
 */

session_start();

// Define constant for includes
define('ALLOW_INCLUDE', true);

// Set JSON header
header('Content-Type: application/json');

// Include config and TSE service
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/tse-service.php';

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Methode nicht erlaubt'
    ]);
    exit;
}

// Get JSON input (array of orders)
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['orders']) || !is_array($input['orders'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Ungültige Daten. Erwartet: {"orders": [...]}'
    ]);
    exit;
}

try {
    $db = getDBConnection();
    $tseService = getTSEService();
    
    $successCount = 0;
    $failedCount = 0;
    $errors = [];
    $syncedOrders = [];

    // Process each offline order
    foreach ($input['orders'] as $orderData) {
        try {
            // Validate order data
            if (!isset($orderData['items']) || !isset($orderData['totalAmount'])) {
                throw new Exception('Fehlende erforderliche Felder');
            }

            $items = $orderData['items'];
            $totalAmount = floatval($orderData['totalAmount']);
            $discountAmount = floatval($orderData['discountAmount'] ?? 0);
            $paymentMethod = $orderData['paymentMethod'] ?? 'CASH';
            $cashGiven = isset($orderData['cashAmount']) ? floatval($orderData['cashAmount']) : null;
            $cashChange = isset($orderData['changeAmount']) ? floatval($orderData['changeAmount']) : null;
            $cashierName = $orderData['cashierName'] ?? 'Kasse';
            $orderNotes = $orderData['orderNotes'] ?? '';
            $offlineTimestamp = $orderData['createdAt'] ?? date('Y-m-d H:i:s');
            $offlineOrderId = $orderData['offlineOrderId'] ?? null; // Frontend's temp ID

            // Calculate tax
            $subtotal = $totalAmount - ($totalAmount * 0.19);
            $tax = $totalAmount - $subtotal;

            // Generate order number
            $orderNumber = 'KASSE-' . date('Ymd', strtotime($offlineTimestamp)) . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);

            // Check uniqueness
            $stmt = $db->prepare("SELECT id FROM orders WHERE order_number = ?");
            $stmt->execute([$orderNumber]);
            if ($stmt->fetch()) {
                $orderNumber = 'KASSE-' . date('YmdHis', strtotime($offlineTimestamp)) . '-' . rand(100, 999);
            }

            // TSE signature (now online)
            $tseData = null;
            if ($tseService->isEnabled()) {
                $tseData = $tseService->signTransaction(
                    $orderNumber,
                    $totalAmount,
                    $paymentMethod === 'CASH' ? 'Bar' : 'Karte'
                );
            }

            // Start transaction
            $db->beginTransaction();

            // Insert order
            $stmt = $db->prepare("
                INSERT INTO orders (
                    order_number,
                    order_source,
                    customer_firstname,
                    customer_lastname,
                    customer_email,
                    customer_phone,
                    delivery_address,
                    delivery_city,
                    delivery_postal_code,
                    delivery_country,
                    order_notes,
                    subtotal,
                    tax,
                    shipping_cost,
                    total_amount,
                    payment_method,
                    payment_status,
                    order_status,
                    cashier_name,
                    cash_given,
                    cash_change,
                    tse_transaction_id,
                    tse_signature,
                    tse_qr_code,
                    is_synced,
                    created_at
                ) VALUES (
                    ?, 'KASSE',
                    'Kasse', 'Kunde', 'kasse@q-bab.de', '-',
                    'Vor Ort', 'München', '80331', 'DE',
                    ?,
                    ?, ?, 0.00, ?,
                    ?, 'completed', 'completed',
                    ?, ?, ?,
                    ?, ?, ?,
                    1,
                    ?
                )
            ");

            $stmt->execute([
                $orderNumber,
                $orderNotes,
                $subtotal,
                $tax,
                $totalAmount,
                $paymentMethod,
                $cashierName,
                $cashGiven,
                $cashChange,
                $tseData['transaction_id'] ?? null,
                $tseData['signature'] ?? null,
                $tseData['qr_code_data'] ?? null,
                $offlineTimestamp
            ]);

            $orderId = $db->lastInsertId();

            // Insert order items
            $stmt = $db->prepare("
                INSERT INTO order_items (
                    order_id,
                    product_id,
                    product_name,
                    product_price,
                    product_image,
                    quantity,
                    subtotal,
                    extras_json
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");

            foreach ($items as $item) {
                $productId = isset($item['id']) ? intval($item['id']) : null;
                $productName = $item['name_de'] ?? $item['name'] ?? 'Unbekanntes Produkt';
                $productPrice = floatval($item['price']);
                $productImage = $item['image'] ?? null;
                $quantity = intval($item['quantity']);
                
                $extrasTotal = 0;
                $extrasData = [];
                if (isset($item['extras']) && is_array($item['extras'])) {
                    foreach ($item['extras'] as $extra) {
                        $extraPrice = floatval($extra['price'] ?? 0);
                        $extrasTotal += $extraPrice;
                        $extrasData[] = [
                            'id' => $extra['id'] ?? null,
                            'name' => $extra['name_de'] ?? $extra['name'] ?? '',
                            'price' => $extraPrice
                        ];
                    }
                }
                
                $itemSubtotal = ($productPrice + $extrasTotal) * $quantity;
                $extrasJson = !empty($extrasData) ? json_encode($extrasData) : null;

                $stmt->execute([
                    $orderId,
                    $productId,
                    $productName,
                    $productPrice,
                    $productImage,
                    $quantity,
                    $itemSubtotal,
                    $extrasJson
                ]);
            }

            $db->commit();

            $successCount++;
            $syncedOrders[] = [
                'offline_order_id' => $offlineOrderId,
                'order_number' => $orderNumber,
                'order_id' => $orderId,
                'tse_data' => $tseData
            ];

        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            
            $failedCount++;
            $errors[] = [
                'offline_order_id' => $offlineOrderId ?? 'unknown',
                'error' => $e->getMessage()
            ];
            
            error_log('Sync failed for offline order: ' . $e->getMessage());
        }
    }

    // Return results
    echo json_encode([
        'success' => true,
        'message' => "$successCount Bestellung(en) erfolgreich synchronisiert, $failedCount fehlgeschlagen",
        'data' => [
            'synced_count' => $successCount,
            'failed_count' => $failedCount,
            'synced_orders' => $syncedOrders,
            'errors' => $errors
        ]
    ]);

} catch (Exception $e) {
    error_log('Sync offline orders error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Fehler bei der Synchronisierung: ' . $e->getMessage()
    ]);
}
?>

