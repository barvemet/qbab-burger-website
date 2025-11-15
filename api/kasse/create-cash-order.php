<?php
/**
 * Create Cash Order API - Kasse System
 * Handles cash payments with TSE signature
 * Nakit ödeme işlemleri ve TSE imzalama
 */

session_start();

// Define constant for includes
define('ALLOW_INCLUDE', true);

// Set JSON header
header('Content-Type: application/json');

// Include config and TSE service
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/tse-service.php';

// CORS headers for cross-origin requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
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

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Ungültige JSON-Daten'
    ]);
    exit;
}

// Validate required fields
$required = ['items', 'totalAmount', 'paymentMethod'];
foreach ($required as $field) {
    if (!isset($input[$field])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => "Feld '$field' ist erforderlich."
        ]);
        exit;
    }
}

// Validate cart items
if (empty($input['items']) || !is_array($input['items'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Warenkorb ist leer.'
    ]);
    exit;
}

try {
    $db = getDBConnection();
    $tseService = getTSEService();

    // Extract order data
    $items = $input['items'];
    $totalAmount = floatval($input['totalAmount']);
    $discountAmount = floatval($input['discountAmount'] ?? 0);
    $paymentMethod = $input['paymentMethod']; // 'CASH', 'CARD', etc.
    $cashGiven = isset($input['cashAmount']) ? floatval($input['cashAmount']) : null;
    $cashChange = isset($input['changeAmount']) ? floatval($input['changeAmount']) : null;
    $cashierName = $input['cashierName'] ?? 'Kasse';
    $orderNotes = $input['orderNotes'] ?? '';
    $isSynced = isset($input['isOffline']) && $input['isOffline'] === true ? 0 : 1;

    // Calculate subtotal and tax
    $subtotal = $totalAmount - ($totalAmount * 0.19); // Assuming 19% tax included
    $tax = $totalAmount - $subtotal;

    // Generate unique order number
    $orderNumber = 'KASSE-' . date('Ymd') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);

    // Check if order number exists
    $stmt = $db->prepare("SELECT id FROM orders WHERE order_number = ?");
    $stmt->execute([$orderNumber]);
    if ($stmt->fetch()) {
        // Regenerate if exists
        $orderNumber = 'KASSE-' . date('Ymd') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
    }

    // Get IP and User Agent
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    // TSE signature (only if online and enabled)
    $tseData = null;
    error_log('TSE: isSynced=' . ($isSynced ? 'true' : 'false') . ', isEnabled=' . ($tseService->isEnabled() ? 'true' : 'false'));
    
    if ($isSynced && $tseService->isEnabled()) {
        error_log('TSE: Attempting to sign transaction for order: ' . $orderNumber);
        $tseData = $tseService->signTransaction(
            $orderNumber,
            $totalAmount,
            $paymentMethod === 'CASH' ? 'Bar' : 'Karte'
        );

        if ($tseData) {
            error_log('TSE: Signature successful! TX ID: ' . ($tseData['transaction_id'] ?? 'null'));
        } else {
            error_log('TSE: Signature failed for order: ' . $orderNumber);
        }
    } else {
        error_log('TSE: Skipped (not synced or not enabled)');
    }

    // Start transaction
    $db->beginTransaction();

    try {
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
                ip_address,
                user_agent
            ) VALUES (
                ?, 'KASSE', 
                'Kasse', 'Kunde', 'kasse@q-bab.de', '-',
                'Vor Ort', 'München', '80331', 'DE',
                ?,
                ?, ?, 0.00, ?,
                ?, 'completed', 'completed',
                ?, ?, ?,
                ?, ?, ?,
                ?,
                ?, ?
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
            $isSynced,
            $ipAddress,
            $userAgent
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
            // Get product info if ID provided
            $productId = isset($item['id']) ? intval($item['id']) : null;
            $productName = $item['name_de'] ?? $item['name'] ?? 'Unbekanntes Produkt';
            $productPrice = floatval($item['price']);
            $productImage = $item['image'] ?? null;
            $quantity = intval($item['quantity']);
            
            // Calculate extras total
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

        // Commit transaction
        $db->commit();

        // Success response
        echo json_encode([
            'success' => true,
            'message' => 'Bestellung erfolgreich erstellt!',
            'data' => [
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'total_amount' => $totalAmount,
                'payment_method' => $paymentMethod,
                'cash_change' => $cashChange,
                'tse_data' => $tseData,
                'created_at' => date('Y-m-d H:i:s')
            ]
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log('Kasse order creation error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Fehler bei der Bestellerstellung: ' . $e->getMessage()
    ]);
}
?>

