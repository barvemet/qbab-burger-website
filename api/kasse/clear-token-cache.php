<?php
/**
 * Clear Token Cache Endpoint
 * Forces new authentication on next request
 *
 * @author Q-Bab Kasse System
 */

session_start();
define('ALLOW_INCLUDE', true);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/tse-service.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only POST requests allowed']);
    exit;
}

try {
    $tseService = getTSEService();
    $tseService->clearTokenCache();

    echo json_encode([
        'success' => true,
        'message' => 'Token cache successfully cleared. New token will be requested on next API call.',
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    error_log('Clear token cache error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to clear token cache',
        'error' => $e->getMessage()
    ]);
}
?>
