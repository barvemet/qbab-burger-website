<?php
/**
 * TSE Health Check Endpoint
 * Checks connectivity and status of Fiskaly TSE integration
 *
 * @author Q-Bab Kasse System
 * @version 1.0
 */

// Define constant for includes
define('ALLOW_INCLUDE', true);

// Set JSON header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include config and TSE service
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/tse-service.php';

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Only GET requests are allowed'
    ]);
    exit;
}

try {
    $tseService = getTSEService();

    // Get health status
    $health = $tseService->checkHealth();

    // Get configuration info
    $config = $tseService->getConfig();

    // Build response
    $response = [
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'health' => $health,
        'configuration' => $config,
        'system' => [
            'php_version' => PHP_VERSION,
            'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'timezone' => date_default_timezone_get()
        ]
    ];

    // Set appropriate HTTP status code
    if ($health['healthy']) {
        http_response_code(200);
    } else {
        http_response_code(503); // Service Unavailable
    }

    echo json_encode($response, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log('Health check error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Health check failed',
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
}
?>
