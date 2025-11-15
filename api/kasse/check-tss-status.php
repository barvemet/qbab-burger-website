<?php
/**
 * Check Fiskaly TSS Status
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

    // Step 1: Get Bearer token
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
        throw new Exception('Authentication failed: HTTP ' . $authHttpCode);
    }

    $authData = json_decode($authResponse, true);
    if (!isset($authData['access_token'])) {
        throw new Exception('No access token in auth response');
    }

    $authToken = $authData['access_token'];

    // Step 2: Get TSS status
    $url = "https://kassensichv.fiskaly.com/api/v2/tss/{$tssId}";

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

    $result = json_decode($response, true);
    
    echo json_encode([
        'success' => true,
        'http_code' => $httpCode,
        'tss_id' => $tssId,
        'tss_data' => $result,
        'current_state' => $result['state'] ?? 'UNKNOWN',
        'explanation' => [
            'UNINITIALIZED' => 'TSS created but not initialized',
            'INITIALIZED' => 'TSS ready to use',
            'DISABLED' => 'TSS disabled',
            'CREATED' => 'TSS created (needs initialization)'
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>

