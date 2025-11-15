<?php
/**
 * Fiskaly Middleware Health Check
 * Tests middleware connectivity and status
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

define('ALLOW_INCLUDE', true);
require_once __DIR__ . '/../../includes/config.php';

$results = [];

// Check if middleware is configured
$useMiddleware = getenv('FISKALY_USE_MIDDLEWARE') ?: $_ENV['FISKALY_USE_MIDDLEWARE'] ?? $_SERVER['FISKALY_USE_MIDDLEWARE'] ?? 'false';
$middlewareUrl = getenv('FISKALY_MIDDLEWARE_URL') ?: $_ENV['FISKALY_MIDDLEWARE_URL'] ?? $_SERVER['FISKALY_MIDDLEWARE_URL'] ?? '';

$results['config'] = [
    'use_middleware' => $useMiddleware === 'true',
    'middleware_url' => $middlewareUrl ?: 'NOT SET'
];

if ($useMiddleware !== 'true') {
    echo json_encode([
        'success' => false,
        'message' => 'Middleware not enabled in configuration',
        'config' => $results['config'],
        'hint' => 'Set FISKALY_USE_MIDDLEWARE=true in .env file'
    ], JSON_PRETTY_PRINT);
    exit;
}

if (empty($middlewareUrl)) {
    echo json_encode([
        'success' => false,
        'message' => 'Middleware URL not configured',
        'config' => $results['config'],
        'hint' => 'Set FISKALY_MIDDLEWARE_URL in .env file (e.g., http://localhost:8000)'
    ], JSON_PRETTY_PRINT);
    exit;
}

// Test 1: Health endpoint
try {
    $healthUrl = rtrim($middlewareUrl, '/') . '/health';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $healthUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    $results['health_check'] = [
        'url' => $healthUrl,
        'http_code' => $httpCode,
        'success' => $httpCode === 200,
        'curl_error' => $curlError ?: null,
        'response' => json_decode($response, true) ?: $response
    ];
} catch (Exception $e) {
    $results['health_check'] = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

// Test 2: Version endpoint
try {
    $versionUrl = rtrim($middlewareUrl, '/') . '/version';

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $versionUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $results['version_check'] = [
        'url' => $versionUrl,
        'http_code' => $httpCode,
        'success' => $httpCode === 200,
        'response' => json_decode($response, true) ?: $response
    ];
} catch (Exception $e) {
    $results['version_check'] = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

// Test 3: API v2 endpoint (with auth)
$apiKey = getenv('FISKALY_API_KEY');
$apiSecret = getenv('FISKALY_API_SECRET');
$tssId = getenv('FISKALY_TSS_ID');

if ($apiKey && $apiSecret && $tssId) {
    try {
        // Get auth token
        $authUrl = rtrim($middlewareUrl, '/') . '/api/v2/auth';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $authUrl);
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

        $results['api_auth'] = [
            'url' => $authUrl,
            'http_code' => $authHttpCode,
            'success' => $authHttpCode === 200
        ];

        if ($authHttpCode === 200) {
            $authData = json_decode($authResponse, true);
            if (isset($authData['access_token'])) {
                $authToken = $authData['access_token'];

                // Test TSS endpoint
                $tssUrl = rtrim($middlewareUrl, '/') . "/api/v2/tss/{$tssId}";

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $tssUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: Bearer ' . $authToken
                ]);

                $tssResponse = curl_exec($ch);
                $tssHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                $results['tss_access'] = [
                    'url' => $tssUrl,
                    'http_code' => $tssHttpCode,
                    'success' => $tssHttpCode === 200,
                    'response' => json_decode($tssResponse, true) ?: $tssResponse
                ];
            }
        }
    } catch (Exception $e) {
        $results['api_test'] = [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

// Summary
$allSuccess = true;
foreach ($results as $key => $result) {
    if ($key !== 'config' && isset($result['success']) && !$result['success']) {
        $allSuccess = false;
        break;
    }
}

echo json_encode([
    'success' => $allSuccess,
    'message' => $allSuccess ? 'Middleware is healthy and operational!' : 'Middleware has issues',
    'results' => $results,
    'recommendations' => $allSuccess ? [] : [
        'Check if middleware is running: systemctl status fiskaly-middleware',
        'Check middleware logs: journalctl -u fiskaly-middleware -f',
        'Verify middleware URL is correct in .env',
        'Ensure port 8000 (or configured port) is open'
    ]
], JSON_PRETTY_PRINT);
?>
