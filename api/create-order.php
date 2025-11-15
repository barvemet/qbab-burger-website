<?php
/**
 * Create Order API
 * Handles order creation for all payment methods
 */

session_start();

// Define constant for includes
define('ALLOW_INCLUDE', true);

// Set JSON header FIRST (before any output)
header('Content-Type: application/json');

// Include config
require_once __DIR__ . '/../includes/config.php';

// Define order constants if not already defined
if (!defined('TAX_RATE')) {
    define('TAX_RATE', 0.19); // 19% VAT
}
if (!defined('SHIPPING_COST')) {
    define('SHIPPING_COST', 0.00); // Free shipping
}
if (!defined('MIN_ORDER_AMOUNT')) {
    define('MIN_ORDER_AMOUNT', 5.00); // Minimum €5
}
if (!defined('ORDER_PREFIX')) {
    define('ORDER_PREFIX', 'QB'); // Order number prefix
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// CSRF check
$csrf = $input['csrf_token'] ?? '';
if (!verifyCSRFToken($csrf)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid CSRF token'
    ]);
    exit;
}

// Validate required fields
$required = ['firstname', 'lastname', 'email', 'phone', 'address', 'city', 'zip', 'country', 'payment_method', 'cart_items'];
foreach ($required as $field) {
    if (!isset($input[$field])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => "Feld '$field' ist erforderlich."
        ]);
        exit;
    }

    // Check if empty (skip arrays like cart_items)
    if (!is_array($input[$field]) && empty(trim($input[$field]))) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => "Feld '$field' ist erforderlich."
        ]);
        exit;
    }
}

// Validate email
if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Ungültige E-Mail-Adresse.'
    ]);
    exit;
}

// Validate payment method
$allowedPaymentMethods = ['cash', 'stripe', 'paypal', 'googlepay', 'applepay', 'klarna'];
if (!in_array($input['payment_method'], $allowedPaymentMethods)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Ungültige Zahlungsmethode.'
    ]);
    exit;
}

// Validate cart
if (empty($input['cart_items']) || !is_array($input['cart_items'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Warenkorb ist leer.'
    ]);
    exit;
}

try {
    $db = getDBConnection();

    // Calculate order totals
    $subtotal = 0;
    $validatedItems = [];

    foreach ($input['cart_items'] as $item) {
        // Validate item structure
        if (!isset($item['id']) || !isset($item['quantity']) || !isset($item['price'])) {
            throw new Exception('Ungültige Warenkorb-Daten.');
        }

        // Calculate item price with extras
        $itemPrice = floatval($item['price']);
        $extras = [];
        
        if (isset($item['extras']) && is_array($item['extras'])) {
            foreach ($item['extras'] as $extra) {
                $extraPrice = floatval($extra['price'] ?? 0);
                $itemPrice += $extraPrice;
                $extras[] = [
                    'id' => intval($extra['id'] ?? 0),
                    'name' => sanitize($extra['name'] ?? ''),
                    'price' => $extraPrice
                ];
            }
        }
        
        $itemTotal = $itemPrice * intval($item['quantity']);
        $subtotal += $itemTotal;

        $validatedItems[] = [
            'id' => intval($item['id']),
            'name' => $item['name'] ?? 'Unknown Product',
            'price' => floatval($item['price']),
            'quantity' => intval($item['quantity']),
            'subtotal' => $itemTotal,
            'image' => $item['image'] ?? null,
            'extras' => $extras
        ];
    }

    // Calculate tax and total
    $tax = $subtotal * TAX_RATE;
    $shipping = SHIPPING_COST;
    $total = $subtotal + $tax + $shipping;

    // Check minimum order amount
    if ($total < MIN_ORDER_AMOUNT) {
        throw new Exception('Mindestbestellwert: €' . number_format(MIN_ORDER_AMOUNT, 2));
    }

    // Generate unique order number
    $orderNumber = ORDER_PREFIX . '-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

    // Check if order number exists
    $stmt = $db->prepare("SELECT id FROM orders WHERE order_number = ?");
    $stmt->execute([$orderNumber]);
    if ($stmt->fetch()) {
        // Regenerate if exists
        $orderNumber = ORDER_PREFIX . '-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    }

    // Get user ID if logged in
    $userId = isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] && isset($_SESSION['user_id'])
        ? $_SESSION['user_id']
        : null;

    // Get IP and User Agent
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    // Start transaction
    $db->beginTransaction();

    try {
        // Insert order
        $stmt = $db->prepare("
            INSERT INTO orders (
                order_number, user_id,
                customer_firstname, customer_lastname, customer_email, customer_phone, customer_company,
                delivery_address, delivery_city, delivery_postal_code, delivery_country,
                order_notes,
                subtotal, tax, shipping_cost, total_amount,
                payment_method, payment_status, order_status,
                ip_address, user_agent
            ) VALUES (
                ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?,
                ?, ?, ?, ?,
                ?, 'pending', 'pending',
                ?, ?
            )
        ");

        $stmt->execute([
            $orderNumber, $userId,
            trim($input['firstname']), trim($input['lastname']), trim($input['email']),
            trim($input['phone']), trim($input['company'] ?? ''),
            trim($input['address']), trim($input['city']), trim($input['zip']), trim($input['country']),
            trim($input['order_notes'] ?? ''),
            $subtotal, $tax, $shipping, $total,
            $input['payment_method'],
            $ipAddress, $userAgent
        ]);

        $orderId = $db->lastInsertId();

        // Insert order items
        $stmt = $db->prepare("
            INSERT INTO order_items (
                order_id, product_id, product_name, product_price, product_image, quantity, subtotal, extras_json
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($validatedItems as $item) {
            // Check if product exists in menu_items
            $productId = null;
            $checkStmt = $db->prepare("SELECT id FROM menu_items WHERE id = ?");
            $checkStmt->execute([$item['id']]);
            if ($checkStmt->fetch()) {
                $productId = $item['id'];
            }

            // Convert extras to JSON
            $extrasJson = !empty($item['extras']) ? json_encode($item['extras']) : null;

            $stmt->execute([
                $orderId,
                $productId, // NULL if product doesn't exist
                $item['name'],
                $item['price'],
                $item['image'],
                $item['quantity'],
                $item['subtotal'],
                $extrasJson
            ]);
        }

        // Commit transaction
        $db->commit();

        // Success response
        echo json_encode([
            'success' => true,
            'message' => 'Bestellung erfolgreich aufgegeben!',
            'order_id' => $orderId,
            'order_number' => $orderNumber,
            'total' => $total,
            'payment_method' => $input['payment_method'],
            'redirect_url' => '/order-confirmation.php?order=' . $orderNumber
        ]);

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log('Order creation error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Fehler bei der Bestellerstellung: ' . $e->getMessage()
    ]);
}
