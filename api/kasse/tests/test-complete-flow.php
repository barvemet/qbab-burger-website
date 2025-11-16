<?php
/**
 * Complete TSE Flow Test
 * Tests the entire flow: TSS init -> Client init -> Transaction start -> Transaction finish
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

define('ALLOW_INCLUDE', true);
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/tse-service.php';

$results = [];
$errors = [];

try {
    $tseService = getTSEService();

    // Step 1: Check if enabled
    $results['step1_enabled'] = $tseService->isEnabled();

    if (!$results['step1_enabled']) {
        throw new Exception('TSE service not configured. Check .env file.');
    }

    // Step 2: Health check
    $results['step2_health'] = $tseService->checkHealth();

    if (!$results['step2_health']['healthy']) {
        $errors[] = 'Health check failed: ' . $results['step2_health']['message'];
    }

    // Step 3: Initialize a transaction
    $results['step3_init_transaction'] = $tseService->initTransaction();

    if (!$results['step3_init_transaction']) {
        throw new Exception('Failed to initialize transaction');
    }

    $transactionId = $results['step3_init_transaction']['transaction_id'];

    // Step 4: Sign the transaction (finalize)
    $testAmount = 15.50;
    $testOrderId = 'TEST_' . time();

    $results['step4_sign_transaction'] = $tseService->signTransaction(
        $testOrderId,
        $testAmount,
        'Bar',
        $transactionId
    );

    if (!$results['step4_sign_transaction']) {
        throw new Exception('Failed to sign transaction');
    }

    // Success!
    echo json_encode([
        'success' => true,
        'message' => 'Complete TSE flow test successful!',
        'test_order_id' => $testOrderId,
        'test_amount' => $testAmount,
        'results' => $results,
        'errors' => $errors
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'partial_results' => $results,
        'errors' => $errors
    ], JSON_PRETTY_PRINT);
}
?>
