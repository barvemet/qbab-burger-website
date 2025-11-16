<?php
/**
 * Try Direct Transaction on CREATED TSS
 * Some Fiskaly TSS auto-initialize on first transaction
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

    $apiKey = getenv('FISKALY_API_KEY');
    $apiSecret = getenv('FISKALY_API_SECRET');
    $tssId = getenv('FISKALY_TSS_ID');

    // Get auth token
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
        throw new Exception('Authentication failed');
    }

    $authData = json_decode($authResponse, true);
    $authToken = $authData['access_token'];

    // Step 1: Create a client (deterministic UUID)
    $clientId = md5('qbab-pos-001');
    $clientId = sprintf('%08s-%04s-%04s-%04s-%012s',
        substr($clientId, 0, 8),
        substr($clientId, 8, 4),
        substr($clientId, 12, 4),
        substr($clientId, 16, 4),
        substr($clientId, 20, 12)
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://kassensichv.fiskaly.com/api/v2/tss/{$tssId}/client/{$clientId}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $authToken
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'serial_number' => $clientId
    ]));

    $clientResponse = curl_exec($ch);
    $clientHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Step 2: Try to create a transaction
    $txId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://kassensichv.fiskaly.com/api/v2/tss/{$tssId}/tx/{$txId}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $authToken
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'state' => 'ACTIVE',
        'client_id' => $clientId
    ]));

    $txResponse = curl_exec($ch);
    $txHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $txData = json_decode($txResponse, true);

    // Step 3: If transaction started, finish it
    $finishResponse = null;
    $finishHttpCode = null;

    if ($txHttpCode >= 200 && $txHttpCode < 300) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://kassensichv.fiskaly.com/api/v2/tss/{$tssId}/tx/{$txId}");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $authToken
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'state' => 'FINISHED',
            'client_id' => $clientId,
            'schema' => [
                'standard_v1' => [
                    'receipt' => [
                        'receipt_type' => 'RECEIPT',
                        'amounts_per_vat_rate' => [
                            [
                                'vat_rate' => 'NORMAL',
                                'amount' => '10.00'
                            ]
                        ],
                        'amounts_per_payment_type' => [
                            [
                                'payment_type' => 'CASH',
                                'amount' => '10.00'
                            ]
                        ]
                    ]
                ]
            ]
        ]));

        $finishResponse = curl_exec($ch);
        $finishHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    }

    // Step 4: Check TSS state again
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://kassensichv.fiskaly.com/api/v2/tss/{$tssId}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $authToken
    ]);

    $tssCheckResponse = curl_exec($ch);
    $tssCheckHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $tssCheckData = json_decode($tssCheckResponse, true);

    echo json_encode([
        'success' => true,
        'message' => 'Direct transaction attempt completed',
        'results' => [
            'step1_create_client' => [
                'client_id' => $clientId,
                'http_code' => $clientHttpCode,
                'success' => $clientHttpCode >= 200 && $clientHttpCode < 300,
                'response' => json_decode($clientResponse, true)
            ],
            'step2_start_transaction' => [
                'transaction_id' => $txId,
                'http_code' => $txHttpCode,
                'success' => $txHttpCode >= 200 && $txHttpCode < 300,
                'response' => $txData
            ],
            'step3_finish_transaction' => [
                'http_code' => $finishHttpCode,
                'success' => $finishHttpCode >= 200 && $finishHttpCode < 300,
                'response' => json_decode($finishResponse, true)
            ],
            'step4_tss_state_check' => [
                'http_code' => $tssCheckHttpCode,
                'current_state' => $tssCheckData['state'] ?? 'UNKNOWN',
                'number_registered_clients' => $tssCheckData['number_registered_clients'] ?? 0,
                'number_active_transactions' => $tssCheckData['number_active_transactions'] ?? 0
            ]
        ]
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
?>
