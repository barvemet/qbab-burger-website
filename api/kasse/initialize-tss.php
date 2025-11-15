<?php
/**
 * Initialize Fiskaly TSS
 * This should be run ONCE to activate the TSS
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

define('ALLOW_INCLUDE', true);
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/tse-service.php';

try {
    $tseService = getTSEService();
    
    if (!$tseService->isEnabled()) {
        throw new Exception('TSE service not configured');
    }
    
    // Get TSS ID from environment
    $tssId = getenv('FISKALY_TSS_ID') ?: $_ENV['FISKALY_TSS_ID'] ?? $_SERVER['FISKALY_TSS_ID'] ?? '';
    $apiKey = getenv('FISKALY_API_KEY') ?: $_ENV['FISKALY_API_KEY'] ?? $_SERVER['FISKALY_API_KEY'] ?? '';
    $apiSecret = getenv('FISKALY_API_SECRET') ?: $_ENV['FISKALY_API_SECRET'] ?? $_SERVER['FISKALY_API_SECRET'] ?? '';
    
    if (empty($tssId) || empty($apiKey) || empty($apiSecret)) {
        throw new Exception('TSS credentials not configured');
    }
    
    // Get JWT token first
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://kassensichv.fiskaly.com/api/v2/auth');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'api_key' => $apiKey,
        'api_secret' => $apiSecret
    ]));

    $authResponse = curl_exec($ch);
    $authHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($authHttpCode !== 200) {
        throw new Exception('Authentication failed: HTTP ' . $authHttpCode . ' - ' . $authResponse);
    }

    $authData = json_decode($authResponse, true);
    if (!isset($authData['access_token'])) {
        throw new Exception('No access token in auth response');
    }

    $authToken = $authData['access_token'];

    $url = "https://kassensichv.fiskaly.com/api/v2/tss/{$tssId}";
    
    // Step 1: Get current TSS state
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $authToken,
        'Accept: application/json'
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $currentState = json_decode($response, true);

    // Check current state
    if (isset($currentState['state'])) {
        $state = $currentState['state'];

        if ($state === 'INITIALIZED') {
            // Already initialized - no action needed
            echo json_encode([
                'success' => true,
                'message' => 'TSS is already initialized!',
                'tss_id' => $tssId,
                'state' => $state,
                'info' => 'No action needed. TSS is ready to use.'
            ], JSON_PRETTY_PRINT);
            exit;
        } elseif ($state === 'UNINITIALIZED' || $state === 'CREATED') {
            // Can initialize from UNINITIALIZED or CREATED
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $authToken,
                'Accept: application/json'
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'state' => 'INITIALIZED'
            ]));

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $result = json_decode($response, true);

            if ($httpCode >= 200 && $httpCode < 300) {
                echo json_encode([
                    'success' => true,
                    'message' => 'TSS successfully initialized!',
                    'tss_id' => $tssId,
                    'previous_state' => $state,
                    'new_state' => 'INITIALIZED',
                    'result' => $result
                ], JSON_PRETTY_PRINT);
            } else {
                throw new Exception('TSS initialization failed: HTTP ' . $httpCode . ' - ' . $response);
            }
        } else {
            // Other state (CREATED, DISABLED, etc.)
            throw new Exception("TSS is in state '{$state}'. Cannot initialize from this state. Contact Fiskaly support.");
        }
    } else {
        throw new Exception('Could not determine TSS state from response');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>

