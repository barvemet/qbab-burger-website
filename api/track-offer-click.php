<?php
/**
 * Track Offer Click Analytics
 */

header('Content-Type: application/json');

// Start session for rate limiting
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include config
if (!defined('ALLOW_INCLUDE')) {
    define('ALLOW_INCLUDE', true);
}
require_once __DIR__ . '/../includes/config.php';

// Rate limiting
if (!checkRateLimit('track_click', 100, 60)) {
    http_response_code(429);
    echo json_encode([
        'success' => false,
        'message' => 'Too many requests'
    ]);
    exit;
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!isset($data['offer_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Missing offer_id'
    ]);
    exit;
}

$offerId = (int)$data['offer_id'];

try {
    $db = getDBConnection();
    
    // Update click count
    $stmt = $db->prepare("UPDATE special_offers SET click_count = click_count + 1 WHERE id = ?");
    $stmt->execute([$offerId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Click tracked'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error'
    ]);
}

