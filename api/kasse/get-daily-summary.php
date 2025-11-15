<?php
/**
 * Get Daily Summary API - Kasse System
 * Returns daily sales statistics for dashboard
 * Günlük satış özeti
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
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Methode nicht erlaubt'
    ]);
    exit;
}

try {
    $db = getDBConnection();
    
    // Get date parameter (default: today)
    $date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
    
    // Validate date format
    $dateObj = DateTime::createFromFormat('Y-m-d', $date);
    if (!$dateObj || $dateObj->format('Y-m-d') !== $date) {
        throw new Exception('Ungültiges Datumsformat. Verwenden Sie YYYY-MM-DD.');
    }

    // Get daily sales summary
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_orders,
            COALESCE(SUM(total_amount), 0) as total_revenue,
            COALESCE(SUM(CASE WHEN payment_method = 'CASH' THEN total_amount ELSE 0 END), 0) as cash_revenue,
            COALESCE(SUM(CASE WHEN payment_method = 'CARD' THEN total_amount ELSE 0 END), 0) as card_revenue,
            COUNT(CASE WHEN payment_method = 'CASH' THEN 1 END) as cash_orders,
            COUNT(CASE WHEN payment_method = 'CARD' THEN 1 END) as card_orders,
            COALESCE(AVG(total_amount), 0) as average_order_value
        FROM orders
        WHERE order_source = 'KASSE'
        AND DATE(created_at) = ?
        AND payment_status = 'completed'
    ");
    $stmt->execute([$date]);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get hourly breakdown
    $stmt = $db->prepare("
        SELECT 
            HOUR(created_at) as hour,
            COUNT(*) as orders,
            SUM(total_amount) as revenue
        FROM orders
        WHERE order_source = 'KASSE'
        AND DATE(created_at) = ?
        AND payment_status = 'completed'
        GROUP BY HOUR(created_at)
        ORDER BY hour
    ");
    $stmt->execute([$date]);
    $hourlyBreakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get top products
    $stmt = $db->prepare("
        SELECT 
            oi.product_name,
            SUM(oi.quantity) as total_quantity,
            SUM(oi.subtotal) as total_revenue
        FROM order_items oi
        INNER JOIN orders o ON oi.order_id = o.id
        WHERE o.order_source = 'KASSE'
        AND DATE(o.created_at) = ?
        AND o.payment_status = 'completed'
        GROUP BY oi.product_name
        ORDER BY total_quantity DESC
        LIMIT 10
    ");
    $stmt->execute([$date]);
    $topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get payment methods breakdown
    $stmt = $db->prepare("
        SELECT 
            payment_method,
            COUNT(*) as count,
            SUM(total_amount) as total
        FROM orders
        WHERE order_source = 'KASSE'
        AND DATE(created_at) = ?
        AND payment_status = 'completed'
        GROUP BY payment_method
    ");
    $stmt->execute([$date]);
    $paymentMethods = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get recent orders
    $stmt = $db->prepare("
        SELECT 
            id,
            order_number,
            total_amount,
            payment_method,
            cashier_name,
            created_at,
            tse_transaction_id
        FROM orders
        WHERE order_source = 'KASSE'
        AND DATE(created_at) = ?
        ORDER BY created_at DESC
        LIMIT 20
    ");
    $stmt->execute([$date]);
    $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return response
    echo json_encode([
        'success' => true,
        'data' => [
            'date' => $date,
            'summary' => [
                'total_orders' => intval($summary['total_orders']),
                'total_revenue' => floatval($summary['total_revenue']),
                'cash_revenue' => floatval($summary['cash_revenue']),
                'card_revenue' => floatval($summary['card_revenue']),
                'cash_orders' => intval($summary['cash_orders']),
                'card_orders' => intval($summary['card_orders']),
                'average_order_value' => floatval($summary['average_order_value'])
            ],
            'hourly_breakdown' => $hourlyBreakdown,
            'top_products' => $topProducts,
            'payment_methods' => $paymentMethods,
            'recent_orders' => $recentOrders
        ]
    ]);

} catch (Exception $e) {
    error_log('Get daily summary error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Fehler beim Abrufen der Zusammenfassung: ' . $e->getMessage()
    ]);
}
?>

