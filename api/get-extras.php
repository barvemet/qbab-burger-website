<?php
/**
 * Get Menu Extras API
 * Returns active extras grouped by category
 */

// Start session for rate limiting
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Define constant for includes
define('ALLOW_INCLUDE', true);

// Include config
require_once __DIR__ . '/../includes/config.php';

// Rate limiting: 60 requests per minute per IP
if (!checkRateLimit('get_extras', 60, 60)) {
    http_response_code(429);
    echo json_encode([
        'success' => false,
        'message' => 'Too many requests. Please try again later.'
    ]);
    exit;
}

try {
    $db = getDBConnection();
    
    // Get active extras
    $stmt = $db->query("
        SELECT id, name_en, name_de, name_tr, price, category, display_order
        FROM menu_extras
        WHERE is_active = 1
        ORDER BY category, display_order, name_de
    ");
    
    $extras = $stmt->fetchAll();
    
    // Group by category
    $grouped = [
        'salatbar' => [],
        'toppings' => []
    ];
    
    foreach ($extras as $extra) {
        $category = $extra['category'];
        if (isset($grouped[$category])) {
            $grouped[$category][] = $extra;
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $grouped
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to load extras'
    ]);
}

