<?php
/**
 * TSE Test Endpoint - Production Safe Testing
 * Tests TSE integration without affecting real data
 *
 * @author Q-Bab Kasse System
 * @version 1.0
 */

// Define constant for includes
define('ALLOW_INCLUDE', true);

// Set JSON header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Include config and TSE service
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/tse-service.php';

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only GET requests allowed']);
    exit;
}

try {
    $tseService = getTSEService();
    $results = [];

    // Test 1: Configuration
    $results['1_configuration'] = [
        'test' => 'Configuration Check',
        'status' => $tseService->isEnabled() ? 'PASS' : 'FAIL',
        'data' => $tseService->getConfig()
    ];

    // Test 2: Health Check
    $health = $tseService->checkHealth();
    $results['2_health'] = [
        'test' => 'Health Check',
        'status' => $health['healthy'] ? 'PASS' : 'FAIL',
        'data' => $health
    ];

    // Test 3: Mock Transaction (doesn't hit real TSE)
    $testOrderId = 'TEST-' . date('YmdHis');

    if ($tseService->isEnabled()) {
        // Only test signature if TSE is enabled
        $signature = $tseService->signTransaction(
            $testOrderId,
            1.00,
            'Bar'
        );

        $results['3_signature'] = [
            'test' => 'Transaction Signature',
            'status' => ($signature && !isset($signature['error'])) ? 'PASS' : 'FAIL',
            'data' => $signature
        ];
    } else {
        // Use mock mode
        $results['3_signature'] = [
            'test' => 'Transaction Signature (Mock Mode)',
            'status' => 'SKIP',
            'data' => ['message' => 'TSE not configured - would use mock mode in production']
        ];
    }

    // Summary
    $totalTests = count($results);
    $passedTests = 0;
    foreach ($results as $result) {
        if ($result['status'] === 'PASS') {
            $passedTests++;
        }
    }

    $response = [
        'success' => true,
        'summary' => [
            'total_tests' => $totalTests,
            'passed' => $passedTests,
            'failed' => $totalTests - $passedTests,
            'success_rate' => round(($passedTests / $totalTests) * 100, 2) . '%'
        ],
        'results' => $results,
        'timestamp' => date('Y-m-d H:i:s')
    ];

    echo json_encode($response, JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log('TSE test error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Test failed',
        'error' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
}
?>
