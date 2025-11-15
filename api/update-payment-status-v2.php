<?php
/**
 * Update Payment Status API v2
 * Updates order payment status after successful Stripe payment
 */
session_start();

// Define constant for includes
define('ALLOW_INCLUDE', true);

// Include config
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validate input
if (!isset($data['order_number']) || !isset($data['payment_intent_id']) || !isset($data['payment_status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$orderNumber = $data['order_number'];
$paymentIntentId = $data['payment_intent_id'];
$paymentStatus = $data['payment_status'];

try {
    $db = getDBConnection();

    // Get order
    $stmt = $db->prepare("SELECT * FROM orders WHERE order_number = ?");
    $stmt->execute([$orderNumber]);
    $order = $stmt->fetch();

    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }

    // Start transaction
    $db->beginTransaction();

    // Update order payment status
    $stmt = $db->prepare("
        UPDATE orders
        SET payment_status = ?,
            order_status = 'confirmed',
            updated_at = NOW()
        WHERE order_number = ?
    ");
    $stmt->execute([$paymentStatus, $orderNumber]);

    // Insert payment transaction
    $stmt = $db->prepare("
        INSERT INTO payment_transactions (
            order_id,
            transaction_id,
            payment_method,
            amount,
            currency,
            status,
            payment_data
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $order['id'],
        $paymentIntentId,
        'stripe',
        $order['total_amount'],
        'EUR',
        $paymentStatus,
        json_encode(['payment_intent_id' => $paymentIntentId])
    ]);

    // Insert status history - FIXED: using 'comment' instead of 'notes'
    $stmt = $db->prepare("
        INSERT INTO order_status_history (
            order_id,
            status,
            comment
        ) VALUES (?, ?, ?)
    ");
    $stmt->execute([
        $order['id'],
        'confirmed',
        'Payment completed via Stripe (Payment Intent: ' . $paymentIntentId . ')'
    ]);

    // Commit transaction
    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Payment status updated successfully',
        'order_number' => $orderNumber
    ]);

} catch (Exception $e) {
    // Rollback on error
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }

    error_log('Update payment status error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
