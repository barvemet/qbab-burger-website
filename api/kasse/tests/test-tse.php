<?php
/**
 * TSE Test Endpoint - Debug Fiskaly Integration
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Define ALLOW_INCLUDE constant FIRST (before any includes)
define('ALLOW_INCLUDE', true);

// Load environment
require_once __DIR__ . '/../../includes/config.php';

// Load TSE service
require_once __DIR__ . '/../../includes/tse-service.php';

try {
    $tseService = getTSEService();
    
    // Test 1: Check if TSE is enabled
    $isEnabled = $tseService->isEnabled();
    
    // Test 2: Check TSE health
    $health = $tseService->checkHealth();
    
    // Test 3: Try to initialize a transaction
    $init = null;
    $initError = null;
    try {
        $init = $tseService->initTransaction();
    } catch (Exception $e) {
        $initError = $e->getMessage();
    }
    
    // Test 4: Check environment variables
    $envCheck = [
        'FISKALY_API_KEY' => !empty(getenv('FISKALY_API_KEY')) ? 'SET (' . substr(getenv('FISKALY_API_KEY'), 0, 15) . '...)' : 'NOT SET',
        'FISKALY_API_SECRET' => !empty(getenv('FISKALY_API_SECRET')) ? 'SET (' . substr(getenv('FISKALY_API_SECRET'), 0, 10) . '...)' : 'NOT SET',
        'FISKALY_TSS_ID' => getenv('FISKALY_TSS_ID') ?: 'NOT SET',
        'FISKALY_CLIENT_ID' => getenv('FISKALY_CLIENT_ID') ?: 'NOT SET (will use default)'
    ];
    
    echo json_encode([
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'tse_enabled' => $isEnabled,
        'environment' => $envCheck,
        'health_check' => $health,
        'init_transaction_test' => [
            'success' => $init !== null,
            'data' => $init,
            'error' => $initError
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
?>

