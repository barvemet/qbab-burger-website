<?php
/**
 * Initialize Fiskaly TSS using Management API
 * Based on Fiskaly documentation: https://developer.fiskaly.com/
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

define('ALLOW_INCLUDE', true);
require_once __DIR__ . '/../../includes/config.php';

try {
    $tssId = getenv('FISKALY_TSS_ID') ?: $_ENV['FISKALY_TSS_ID'] ?? $_SERVER['FISKALY_TSS_ID'] ?? '';
    $apiKey = getenv('FISKALY_API_KEY') ?: $_ENV['FISKALY_API_KEY'] ?? $_SERVER['FISKALY_API_KEY'] ?? '';
    $apiSecret = getenv('FISKALY_API_SECRET') ?: $_ENV['FISKALY_API_SECRET'] ?? $_SERVER['FISKALY_API_SECRET'] ?? '';
    
    if (empty($tssId) || empty($apiKey) || empty($apiSecret)) {
        throw new Exception('TSS credentials not configured');
    }
    
    $steps = [];
    
    // Step 1: Check current TSS state via Middleware API
    // Note: Management API (api.fiskaly.com) is not accessible from all servers
    // Using Middleware API instead: kassensichv-middleware.fiskaly.com
    $managementUrl = "https://kassensichv-middleware.fiskaly.com/api/v2/tss/{$tssId}";
    
    // Use API key directly as Bearer token for middleware
    $authHeader = 'Bearer ' . $apiKey;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $managementUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: ' . $authHeader,
        'Accept: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    $currentState = json_decode($response, true);
    $steps['check_state'] = [
        'http_code' => $httpCode,
        'response' => $currentState,
        'current_state' => $currentState['state'] ?? 'UNKNOWN',
        'curl_error' => $curlError,
        'raw_response' => $response
    ];
    
    // Step 2: Initialize TSS if needed
    if (isset($currentState['state']) && $currentState['state'] === 'CREATED') {
        // TSS needs to be initialized
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $managementUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: ' . $authHeader,
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'state' => 'INITIALIZED'
        ]));
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $initResult = json_decode($response, true);
        $steps['initialize'] = [
            'http_code' => $httpCode,
            'response' => $initResult
        ];
        
        if ($httpCode >= 200 && $httpCode < 300) {
            echo json_encode([
                'success' => true,
                'message' => 'TSS successfully initialized!',
                'tss_id' => $tssId,
                'final_state' => $initResult['state'] ?? 'INITIALIZED',
                'steps' => $steps
            ], JSON_PRETTY_PRINT);
            exit;
        }
    } elseif (isset($currentState['state']) && $currentState['state'] === 'INITIALIZED') {
        echo json_encode([
            'success' => true,
            'message' => 'TSS is already initialized!',
            'tss_id' => $tssId,
            'current_state' => 'INITIALIZED',
            'steps' => $steps
        ], JSON_PRETTY_PRINT);
        exit;
    }
    
    // If we're here, something went wrong
    throw new Exception('Failed to initialize TSS. Current state: ' . ($currentState['state'] ?? 'UNKNOWN') . '. Steps: ' . json_encode($steps));
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'steps' => $steps ?? []
    ], JSON_PRETTY_PRINT);
}
?>

