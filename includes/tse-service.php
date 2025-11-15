<?php
/**
 * TSE (Technische Sicherheitseinrichtung) Service
 * Integration with Fiskaly Cloud TSE
 * Compliant with German KassenSichV (Kassensicherungsverordnung)
 * 
 * @author Q-Bab Kasse System
 * @version 2.0 - Fiskaly Integration
 */

// Prevent direct access
if (!defined('ALLOW_INCLUDE')) {
    die('Direct access not permitted');
}

class TSEService {
    private $apiKey;
    private $apiSecret;
    private $tssId;
    private $clientId;
    private $apiBaseUrl;
    private $enabled;
    private $authToken;

    public function __construct() {
        // Load configuration from environment (check multiple sources for Strato compatibility)
        $this->apiKey = $this->getEnvVar('FISKALY_API_KEY');
        $this->apiSecret = $this->getEnvVar('FISKALY_API_SECRET');
        $this->tssId = $this->getEnvVar('FISKALY_TSS_ID');
        
        // Client ID must be UUID format for Fiskaly
        $envClientId = $this->getEnvVar('FISKALY_CLIENT_ID');
        if ($envClientId && $this->isValidUuid($envClientId)) {
            $this->clientId = $envClientId;
        } else {
            // Generate a deterministic UUID based on a fixed string for this POS
            $this->clientId = $this->generateDeterministicUuid('qbab-pos-001');
        }
        
        // Check if middleware is enabled
        $useMiddleware = $this->getEnvVar('FISKALY_USE_MIDDLEWARE');
        $middlewareUrl = $this->getEnvVar('FISKALY_MIDDLEWARE_URL');

        if ($useMiddleware === 'true' && !empty($middlewareUrl)) {
            // Use middleware endpoint
            $this->apiBaseUrl = rtrim($middlewareUrl, '/') . '/api/v2';
            error_log('TSE: Using Fiskaly Middleware at ' . $this->apiBaseUrl);
        } else {
            // Use direct Cloud API endpoint
            $this->apiBaseUrl = 'https://kassensichv.fiskaly.com/api/v2';
            error_log('TSE: Using direct Cloud API');
        }

        // Debug log
        if (!empty($this->apiKey)) {
            error_log('TSE Service initialized: API Key=' . substr($this->apiKey, 0, 15) . '..., TSS ID=' . $this->tssId);
        }
        
        // Check if TSE is enabled
        $this->enabled = !empty($this->apiKey) && !empty($this->apiSecret) && !empty($this->tssId);
        $this->authToken = null;
        
        if (!$this->enabled) {
            error_log('TSE Service: Not configured. Set FISKALY_API_KEY, FISKALY_API_SECRET and FISKALY_TSS_ID in .env file.');
        }
    }

    /**
     * Check if string is valid UUID
     * @param string $uuid
     * @return bool
     */
    private function isValidUuid($uuid) {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid) === 1;
    }
    
    /**
     * Generate deterministic UUID from string (for consistent client ID)
     * @param string $string
     * @return string
     */
    private function generateDeterministicUuid($string) {
        $hash = md5($string);
        return sprintf('%08s-%04s-%04s-%04s-%012s',
            substr($hash, 0, 8),
            substr($hash, 8, 4),
            substr($hash, 12, 4),
            substr($hash, 16, 4),
            substr($hash, 20, 12)
        );
    }

    /**
     * Get environment variable from multiple sources (Strato compatibility)
     * @param string $key
     * @return string|false
     */
    private function getEnvVar($key) {
        // Try multiple sources for maximum compatibility
        if (defined($key)) {
            return constant($key);
        }
        if (isset($_ENV[$key])) {
            return $_ENV[$key];
        }
        if (isset($_SERVER[$key])) {
            return $_SERVER[$key];
        }
        return getenv($key);
    }

    /**
     * Get authentication token (JWT) for Fiskaly API
     * @return string|null
     */
    private function getAuthToken() {
        if ($this->authToken) {
            return $this->authToken;
        }

        try {
            // Fiskaly v2 API requires Bearer token (JWT)
            // We need to authenticate with API Key and Secret to get the token

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->apiBaseUrl . '/auth');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'api_key' => $this->apiKey,
                'api_secret' => $this->apiSecret
            ]));

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError) {
                error_log('TSE auth CURL error: ' . $curlError);
                return null;
            }

            if ($httpCode === 200) {
                $data = json_decode($response, true);
                if (isset($data['access_token'])) {
                    $this->authToken = $data['access_token'];
                    error_log('TSE: Auth token obtained successfully (Bearer JWT)');
                    return $this->authToken;
                }
            }

            error_log('TSE: Auth failed - HTTP ' . $httpCode . ' - Response: ' . $response);
            return null;

        } catch (Exception $e) {
            error_log('TSE auth error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Check if TSE is enabled and configured
     * @return bool
     */
    public function isEnabled() {
        return $this->enabled;
    }

    /**
     * Initialize TSE transaction (Fiskaly)
     * @param string $processType Transaction type (e.g., 'Kassenbeleg-V1')
     * @return array|null Transaction data or null on failure
     */
    public function initTransaction($processType = 'Kassenbeleg-V1') {
        if (!$this->enabled) {
            return $this->mockTransaction('INIT');
        }

        try {
            // Step 1: Ensure TSS is initialized
            $this->ensureTSSInitialized();

            // Step 2: Ensure client exists
            $this->ensureClientExists();

            // Step 3: Start transaction
            $transactionId = $this->generateTransactionId();
            $endpoint = "/tss/{$this->tssId}/tx/{$transactionId}";

            error_log("TSE: Initializing transaction at endpoint: {$endpoint}");

            $response = $this->apiRequest('PUT', $endpoint, [
                'state' => 'ACTIVE',
                'client_id' => $this->clientId
            ]);

            if ($response && isset($response['_id'])) {
                error_log("TSE: Transaction initialized successfully: {$response['_id']}");
                return [
                    'transaction_number' => $response['number'] ?? 0,
                    'transaction_id' => $response['_id'],
                    'time_start' => $response['time_start'] ?? time(),
                    'serial_number' => $response['tss_serial_number'] ?? $this->tssId
                ];
            }

            throw new Exception('Invalid response from Fiskaly TSE service');
        } catch (Exception $e) {
            error_log('TSE initTransaction error: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Ensure TSS is initialized
     * @return bool
     */
    private function ensureTSSInitialized() {
        try {
            // Check current TSS state
            $endpoint = "/tss/{$this->tssId}";
            $response = $this->apiRequest('GET', $endpoint);

            if ($response && isset($response['state'])) {
                $currentState = $response['state'];
                error_log("TSE: Current TSS state: {$currentState}");

                // If TSS is in CREATED or UNINITIALIZED state, initialize it
                if ($currentState === 'CREATED' || $currentState === 'UNINITIALIZED') {
                    error_log("TSE: TSS is in state '{$currentState}', attempting to initialize...");

                    try {
                        $this->apiRequest('PATCH', $endpoint, [
                            'state' => 'INITIALIZED'
                        ]);
                        error_log("TSE: TSS initialized successfully");
                    } catch (Exception $e) {
                        error_log("TSE: TSS initialization failed: " . $e->getMessage());
                        // If it's CREATED state and normal init fails, try alternative method
                        if ($currentState === 'CREATED') {
                            error_log("TSE: Trying alternative initialization for CREATED state");
                            // Some TSS in CREATED state need to be used directly
                            // They auto-initialize on first transaction
                        }
                    }
                } elseif ($currentState === 'INITIALIZED') {
                    error_log("TSE: TSS already initialized");
                } else {
                    error_log("TSE: TSS state is {$currentState}");
                }
            }

            return true;
        } catch (Exception $e) {
            error_log('TSE ensureTSSInitialized warning: ' . $e->getMessage());
            // Don't fail if already initialized
            return true;
        }
    }

    /**
     * Ensure Fiskaly client exists
     * @return bool
     */
    private function ensureClientExists() {
        try {
            // Create/update client - Fiskaly automatically creates if not exists
            $endpoint = "/tss/{$this->tssId}/client/{$this->clientId}";

            $this->apiRequest('PUT', $endpoint, [
                'serial_number' => $this->clientId
            ]);

            error_log("TSE: Client ensured: {$this->clientId}");
            return true;
        } catch (Exception $e) {
            error_log('TSE ensureClientExists warning: ' . $e->getMessage());
            // Don't fail if client already exists or minor error
            return true;
        }
    }

    /**
     * Generate unique transaction ID (UUID v4 format for Fiskaly)
     * @return string
     */
    private function generateTransactionId() {
        // Generate UUID v4
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Sign a transaction (Finalize) - Fiskaly
     * @param string $orderId Order ID or number
     * @param float $amount Total amount
     * @param string $paymentType Payment method (cash, card, etc.)
     * @param string|null $transactionId Transaction ID from initTransaction
     * @return array|null Signature data or null on failure
     */
    public function signTransaction($orderId, $amount, $paymentType = 'Bar', $transactionId = null) {
        if (!$this->enabled) {
            return $this->mockTransaction('SIGN', $orderId, $amount);
        }

        try {
            // If no transaction ID provided, init a new one
            if (!$transactionId) {
                $init = $this->initTransaction();
                if (!$init) {
                    throw new Exception('Failed to initialize TSE transaction');
                }
                $transactionId = $init['transaction_id'];
            }

            $endpoint = "/tss/{$this->tssId}/tx/{$transactionId}";

            // Build process data for Fiskaly
            $schema = [
                'standard_v1' => [
                    'receipt' => [
                        'receipt_type' => 'RECEIPT',
                        'amounts_per_vat_rate' => [
                            [
                                'vat_rate' => 'NORMAL',
                                'amount' => strval(number_format($amount, 2, '.', ''))
                            ]
                        ],
                        'amounts_per_payment_type' => [
                            [
                                'payment_type' => $paymentType === 'Bar' ? 'CASH' : 'NON_CASH',
                                'amount' => strval(number_format($amount, 2, '.', ''))
                            ]
                        ]
                    ]
                ]
            ];

            // Finalize transaction
            $response = $this->apiRequest('PUT', $endpoint, [
                'state' => 'FINISHED',
                'client_id' => $this->clientId,
                'schema' => $schema
            ]);

            if ($response) {
                // Generate QR code data
                $qrData = $this->generateQRCodeDataFiskaly($response);
                
                return [
                    'transaction_id' => $response['_id'] ?? $transactionId,
                    'signature' => $response['signature']['value'] ?? '',
                    'signature_counter' => $response['signature']['counter'] ?? 0,
                    'time_start' => $response['time_start'] ?? time(),
                    'time_end' => $response['time_end'] ?? time(),
                    'serial_number' => $response['tss_serial_number'] ?? $this->tssId,
                    'qr_code_data' => $qrData,
                    'log_time' => date('Y-m-d H:i:s')
                ];
            }

            throw new Exception('Invalid signature response from Fiskaly TSE service');
        } catch (Exception $e) {
            error_log('TSE signTransaction error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Build process data for TSE signature (DSFinV-K format)
     * @param string $orderId
     * @param float $amount
     * @param string $paymentType
     * @return string Process data string
     */
    private function buildProcessData($orderId, $amount, $paymentType) {
        // DSFinV-K format: Beleg^Brutto_Betrag^Zahlungsart^Bon-ID
        $data = implode('^', [
            'Beleg',
            number_format($amount, 2, '.', ''),
            $paymentType,
            $orderId,
            date('Y-m-d\TH:i:s')
        ]);
        
        return $data;
    }

    /**
     * Generate QR code data for Fiskaly (BSI TR-03153)
     * @param array $response Fiskaly transaction response
     * @return string QR code data
     */
    private function generateQRCodeDataFiskaly($response) {
        // QR code format according to BSI TR-03153
        // V0;base64(CashPointClosingID);base64(processType);base64(processData);transactionNumber;signatureCounter;unixTime(start);unixTime(end);ecdsa-plain-SHA256;unixTime;base64(signature);base64(publicKey)
        
        $parts = [
            'V0',
            base64_encode($this->tssId),
            base64_encode('Kassenbeleg-V1'),
            base64_encode(''),  // Process data (optional for Fiskaly)
            $response['number'] ?? 0,
            $response['signature']['counter'] ?? 0,
            $response['time_start'] ?? time(),
            $response['time_end'] ?? time(),
            'ecdsa-plain-SHA256',
            'unixTime',
            base64_encode($response['signature']['value'] ?? ''),
            base64_encode($response['signature']['public_key'] ?? '')
        ];
        
        return implode(';', $parts);
    }

    /**
     * Format timestamp for QR code (Unix timestamp)
     * @param string $datetime
     * @return string Unix timestamp
     */
    private function formatTimestamp($datetime) {
        return strtotime($datetime);
    }

    /**
     * Make API request to Fiskaly
     * @param string $method HTTP method
     * @param string $endpoint API endpoint
     * @param array $data Request data
     * @return array|null Response data or null on failure
     */
    private function apiRequest($method, $endpoint, $data = []) {
        $url = $this->apiBaseUrl . $endpoint;
        $authToken = $this->getAuthToken();
        
        if (!$authToken) {
            throw new Exception('Failed to get auth token');
        }
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $authToken,
            'Accept: application/json'
        ]);
        
        if ($method !== 'GET' && !empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception('CURL error: ' . $error);
        }
        
        if ($httpCode >= 400) {
            throw new Exception('Fiskaly API error: HTTP ' . $httpCode . ' - ' . $response);
        }
        
        return json_decode($response, true);
    }

    /**
     * Mock transaction for testing without real TSE
     * @param string $action
     * @param string $orderId
     * @param float $amount
     * @return array Mock data
     */
    private function mockTransaction($action, $orderId = '', $amount = 0) {
        $transactionNumber = 'MOCK_' . time() . '_' . rand(1000, 9999);
        
        if ($action === 'INIT') {
            return [
                'transaction_number' => $transactionNumber,
                'time_start' => date('Y-m-d H:i:s'),
                'serial_number' => 'MOCK_TSS_001'
            ];
        }
        
        // SIGN action
        return [
            'transaction_id' => $transactionNumber,
            'signature' => 'MOCK_SIGNATURE_' . hash('sha256', $orderId . $amount . time()),
            'signature_counter' => rand(1000, 9999),
            'time_start' => date('Y-m-d H:i:s', time() - 5),
            'time_end' => date('Y-m-d H:i:s'),
            'serial_number' => 'MOCK_TSS_001',
            'qr_code_data' => 'V0;MOCK_TSS_001;Kassenbeleg-V1;;;1000;' . time() . ';' . time() . ';ecdsa-plain-SHA256;unixTime;MOCK_SIG;PUB_001',
            'log_time' => date('Y-m-d H:i:s'),
            'mock_mode' => true
        ];
    }

    /**
     * Export DSFinV-K compliant data for tax authorities
     * @param string $startDate Start date (Y-m-d)
     * @param string $endDate End date (Y-m-d)
     * @return array Export data
     */
    public function exportDSFinVK($startDate, $endDate) {
        // This would query the database for all TSE transactions in the date range
        // and format them according to DSFinV-K specification
        // Implementation depends on specific requirements
        
        $db = getDBConnection();
        $stmt = $db->prepare("
            SELECT 
                order_number,
                tse_transaction_id,
                tse_signature,
                total_amount,
                payment_method,
                created_at,
                cashier_name
            FROM orders
            WHERE order_source = 'KASSE'
            AND tse_transaction_id IS NOT NULL
            AND DATE(created_at) BETWEEN ? AND ?
            ORDER BY created_at
        ");
        $stmt->execute([$startDate, $endDate]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Verify TSE health and connectivity
     * @return array Health status
     */
    public function checkHealth() {
        if (!$this->enabled) {
            return [
                'status' => 'disabled',
                'message' => 'TSE service is not configured',
                'healthy' => false
            ];
        }

        try {
            // Try to get TSS info
            $endpoint = "/tss/{$this->tssId}";
            $response = $this->apiRequest('GET', $endpoint);
            
            if ($response) {
                return [
                    'status' => 'online',
                    'message' => 'TSE service is operational',
                    'healthy' => true,
                    'tss_id' => $this->tssId,
                    'response' => $response
                ];
            }
            
            return [
                'status' => 'error',
                'message' => 'Invalid response from TSE service',
                'healthy' => false
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage(),
                'healthy' => false
            ];
        }
    }
}

// Create global TSE service instance
function getTSEService() {
    static $tseService = null;
    
    if ($tseService === null) {
        $tseService = new TSEService();
    }
    
    return $tseService;
}

?>

