<?php
/**
 * Raw Fiskaly API Test - Direct CURL
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

define('ALLOW_INCLUDE', true);
require_once __DIR__ . '/../../includes/config.php';

$apiKey = getenv('FISKALY_API_KEY') ?: $_ENV['FISKALY_API_KEY'] ?? $_SERVER['FISKALY_API_KEY'] ?? '';
$apiSecret = getenv('FISKALY_API_SECRET') ?: $_ENV['FISKALY_API_SECRET'] ?? $_SERVER['FISKALY_API_SECRET'] ?? '';
$tssId = getenv('FISKALY_TSS_ID') ?: $_ENV['FISKALY_TSS_ID'] ?? $_SERVER['FISKALY_TSS_ID'] ?? '';

if (empty($apiKey) || empty($apiSecret)) {
    echo json_encode([
        'error' => 'API credentials not loaded',
        'apiKey' => !empty($apiKey),
        'apiSecret' => !empty($apiSecret)
    ]);
    exit;
}

// Test 1: List all TSS
$authToken = base64_encode($apiKey . ':' . $apiSecret);

$tests = [];

// Test 1: GET /tss (List all TSS)
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://kassensichv.fiskaly.com/api/v2/tss');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Basic ' . $authToken,
    'Accept: application/json'
]);
$response1 = curl_exec($ch);
$httpCode1 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$tests['list_tss'] = [
    'endpoint' => 'GET /tss',
    'http_code' => $httpCode1,
    'response' => json_decode($response1, true) ?: $response1
];

// Test 2: GET specific TSS
if (!empty($tssId)) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://kassensichv.fiskaly.com/api/v2/tss/{$tssId}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . $authToken,
        'Accept: application/json'
    ]);
    $response2 = curl_exec($ch);
    $httpCode2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $tests['get_specific_tss'] = [
        'endpoint' => "GET /tss/{$tssId}",
        'http_code' => $httpCode2,
        'response' => json_decode($response2, true) ?: $response2
    ];
}

// Test 3: Check authentication endpoint
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://kassensichv.fiskaly.com/api/v2/auth');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Basic ' . $authToken,
    'Accept: application/json'
]);
$response3 = curl_exec($ch);
$httpCode3 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$tests['auth_check'] = [
    'endpoint' => 'GET /auth',
    'http_code' => $httpCode3,
    'response' => json_decode($response3, true) ?: $response3
];

echo json_encode([
    'success' => true,
    'credentials' => [
        'api_key' => substr($apiKey, 0, 15) . '...',
        'api_secret' => substr($apiSecret, 0, 10) . '...',
        'tss_id' => $tssId
    ],
    'tests' => $tests
], JSON_PRETTY_PRINT);
?>

