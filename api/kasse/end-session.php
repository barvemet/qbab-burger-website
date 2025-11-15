<?php
/**
 * End Session API - Kasse System
 * Closes a cashier shift/session with final cash count
 * Vardiya sonlandırma
 */

session_start();

// Define constant for includes
define('ALLOW_INCLUDE', true);

// Set JSON header
header('Content-Type: application/json');

// Include config
require_once __DIR__ . '/../../includes/config.php';

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
if (!isset($input['sessionId'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Sitzungs-ID ist erforderlich'
    ]);
    exit;
}

try {
    $db = getDBConnection();

    $sessionId = intval($input['sessionId']);
    $endingCash = isset($input['endingCash']) ? floatval($input['endingCash']) : 0.00;
    $closingNotes = trim($input['closingNotes'] ?? '');

    // Get session details
    $stmt = $db->prepare("
        SELECT 
            id,
            session_number,
            cashier_name,
            start_time,
            starting_cash,
            status
        FROM kasse_sessions
        WHERE id = ?
    ");
    $stmt->execute([$sessionId]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$session) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Sitzung nicht gefunden'
        ]);
        exit;
    }

    if ($session['status'] === 'CLOSED') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Diese Schicht wurde bereits geschlossen'
        ]);
        exit;
    }

    // Calculate session statistics
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_orders,
            COALESCE(SUM(total_amount), 0) as total_sales,
            COUNT(CASE WHEN payment_method = 'CASH' THEN 1 END) as cash_orders,
            COUNT(CASE WHEN payment_method = 'CARD' THEN 1 END) as card_orders,
            COALESCE(SUM(CASE WHEN payment_method = 'CASH' THEN total_amount ELSE 0 END), 0) as cash_revenue
        FROM orders
        WHERE order_source = 'KASSE'
        AND cashier_name = ?
        AND created_at >= ?
        AND payment_status = 'completed'
    ");
    $stmt->execute([$session['cashier_name'], $session['start_time']]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // Calculate expected cash (starting cash + cash revenue)
    $expectedCash = floatval($session['starting_cash']) + floatval($stats['cash_revenue']);
    $cashDifference = $endingCash - $expectedCash;

    // Update session
    $stmt = $db->prepare("
        UPDATE kasse_sessions SET
            end_time = NOW(),
            ending_cash = ?,
            expected_cash = ?,
            cash_difference = ?,
            total_sales = ?,
            total_orders = ?,
            cash_orders = ?,
            card_orders = ?,
            closing_notes = ?,
            status = 'CLOSED'
        WHERE id = ?
    ");

    $stmt->execute([
        $endingCash,
        $expectedCash,
        $cashDifference,
        $stats['total_sales'],
        $stats['total_orders'],
        $stats['cash_orders'],
        $stats['card_orders'],
        $closingNotes,
        $sessionId
    ]);

    // Clear session data
    unset($_SESSION['kasse_session_id']);
    unset($_SESSION['kasse_session_number']);
    unset($_SESSION['cashier_name']);

    // Return response
    echo json_encode([
        'success' => true,
        'message' => 'Schicht erfolgreich geschlossen',
        'data' => [
            'session_id' => $sessionId,
            'session_number' => $session['session_number'],
            'cashier_name' => $session['cashier_name'],
            'start_time' => $session['start_time'],
            'end_time' => date('Y-m-d H:i:s'),
            'starting_cash' => floatval($session['starting_cash']),
            'ending_cash' => $endingCash,
            'expected_cash' => $expectedCash,
            'cash_difference' => $cashDifference,
            'total_sales' => floatval($stats['total_sales']),
            'total_orders' => intval($stats['total_orders']),
            'cash_orders' => intval($stats['cash_orders']),
            'card_orders' => intval($stats['card_orders'])
        ]
    ]);

} catch (Exception $e) {
    error_log('End session error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Fehler beim Beenden der Schicht: ' . $e->getMessage()
    ]);
}
?>

