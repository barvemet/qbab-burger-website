<?php
/**
 * Fiskaly API Debug Tool
 * Provides detailed information about Fiskaly configuration and connectivity
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

define('ALLOW_INCLUDE', true);
require_once __DIR__ . '/../../includes/config.php';

$debug = [];

// 1. Check environment variables
$debug['environment'] = [
    'FISKALY_API_KEY' => [
        'loaded' => !empty(getenv('FISKALY_API_KEY')),
        'preview' => getenv('FISKALY_API_KEY') ? substr(getenv('FISKALY_API_KEY'), 0, 20) . '...' : 'NOT SET'
    ],
    'FISKALY_API_SECRET' => [
        'loaded' => !empty(getenv('FISKALY_API_SECRET')),
        'preview' => getenv('FISKALY_API_SECRET') ? substr(getenv('FISKALY_API_SECRET'), 0, 15) . '...' : 'NOT SET'
    ],
    'FISKALY_TSS_ID' => [
        'loaded' => !empty(getenv('FISKALY_TSS_ID')),
        'value' => getenv('FISKALY_TSS_ID') ?: 'NOT SET'
    ],
    'FISKALY_CLIENT_ID' => [
        'loaded' => !empty(getenv('FISKALY_CLIENT_ID')),
        'value' => getenv('FISKALY_CLIENT_ID') ?: 'NOT SET (will auto-generate)'
    ]
];

// 2. Test API connectivity
$apiKey = getenv('FISKALY_API_KEY');
$apiSecret = getenv('FISKALY_API_SECRET');
$tssId = getenv('FISKALY_TSS_ID');

if ($apiKey && $apiSecret) {
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

    $debug['api_tests']['auth'] = [
        'endpoint' => 'POST /auth',
        'http_code' => $authHttpCode,
        'success' => $authHttpCode === 200
    ];

    $authToken = null;
    if ($authHttpCode === 200) {
        $authData = json_decode($authResponse, true);
        if (isset($authData['access_token'])) {
            $authToken = $authData['access_token'];
            $debug['api_tests']['auth']['token_obtained'] = true;
        }
    }

    if ($authToken) {
        // Test 2: Get TSS info
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://kassensichv.fiskaly.com/api/v2/tss/{$tssId}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $authToken,
            'Accept: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $debug['api_tests']['get_tss'] = [
            'endpoint' => "GET /tss/{$tssId}",
            'http_code' => $httpCode,
            'success' => $httpCode >= 200 && $httpCode < 300,
            'curl_error' => $curlError ?: null,
            'response' => json_decode($response, true) ?: $response
        ];

        // Test 3: List clients
        if ($httpCode >= 200 && $httpCode < 300) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "https://kassensichv.fiskaly.com/api/v2/tss/{$tssId}/client");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $authToken,
                'Accept: application/json'
            ]);

            $response2 = curl_exec($ch);
            $httpCode2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $debug['api_tests']['list_clients'] = [
                'endpoint' => "GET /tss/{$tssId}/client",
                'http_code' => $httpCode2,
                'success' => $httpCode2 >= 200 && $httpCode2 < 300,
                'response' => json_decode($response2, true) ?: $response2
            ];
        }
    } else {
        $debug['api_tests']['error'] = 'Failed to obtain auth token';
    }
} else {
    $debug['api_tests'] = [
        'error' => 'API credentials not configured'
    ];
}

// 3. Check TSE service
require_once __DIR__ . '/../../includes/tse-service.php';
$tseService = getTSEService();

$debug['tse_service'] = [
    'enabled' => $tseService->isEnabled(),
    'health_check' => $tseService->checkHealth()
];

// 4. Recommendations
$debug['recommendations'] = [];

if (!$debug['environment']['FISKALY_API_KEY']['loaded']) {
    $debug['recommendations'][] = 'Set FISKALY_API_KEY in .env file';
}
if (!$debug['environment']['FISKALY_API_SECRET']['loaded']) {
    $debug['recommendations'][] = 'Set FISKALY_API_SECRET in .env file';
}
if (!$debug['environment']['FISKALY_TSS_ID']['loaded']) {
    $debug['recommendations'][] = 'Set FISKALY_TSS_ID in .env file';
}

if (isset($debug['api_tests']['get_tss'])) {
    $tssData = $debug['api_tests']['get_tss']['response'];
    if (is_array($tssData) && isset($tssData['state'])) {
        if ($tssData['state'] === 'CREATED' || $tssData['state'] === 'UNINITIALIZED') {
            $debug['recommendations'][] = 'TSS is in state "' . $tssData['state'] . '" - Run initialize-tss.php to activate it';
        } elseif ($tssData['state'] === 'INITIALIZED') {
            $debug['recommendations'][] = 'TSS is ready! You can start creating transactions.';
        }
    }
}

if (empty($debug['recommendations'])) {
    $debug['recommendations'][] = 'All checks passed! System is ready.';
}

echo json_encode($debug, JSON_PRETTY_PRINT);
?>
