<?php
/**
 * Initialize Fiskaly TSS with Admin PIN
 * Some TSS require admin PIN to be set during initialization
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

    // Check if admin_puk.json file exists
    $adminPukFile = __DIR__ . "/../../tss_{$tssId}_adminpuk.json";
    $adminPuk = null;

    if (file_exists($adminPukFile)) {
        $adminPukContent = file_get_contents($adminPukFile);
        $adminPukData = json_decode($adminPukContent, true);
        if (isset($adminPukData['admin_puk'])) {
            $adminPuk = $adminPukData['admin_puk'];
        }
    }

    // Step 1: Try to initialize with admin PIN
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url . '/admin');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $authToken,
        'Accept: application/json'
    ]);

    $payload = [
        'admin_pin' => '12345'  // Default PIN for test TSS
    ];

    if ($adminPuk) {
        $payload['admin_puk'] = $adminPuk;
    }

    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

    $response1 = curl_exec($ch);
    $httpCode1 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result1 = json_decode($response1, true);

    // Step 2: Now try to change state to INITIALIZED
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

    $response2 = curl_exec($ch);
    $httpCode2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result2 = json_decode($response2, true);

    echo json_encode([
        'success' => true,
        'message' => 'TSS initialization attempted',
        'tss_id' => $tssId,
        'step1_admin_pin' => [
            'http_code' => $httpCode1,
            'success' => $httpCode1 >= 200 && $httpCode1 < 300,
            'response' => $result1
        ],
        'step2_state_change' => [
            'http_code' => $httpCode2,
            'success' => $httpCode2 >= 200 && $httpCode2 < 300,
            'response' => $result2
        ],
        'final_state' => $result2['state'] ?? 'UNKNOWN'
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
