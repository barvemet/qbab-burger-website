<?php
/**
 * Test if config.php loads environment variables correctly
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Define ALLOW_INCLUDE first
define('ALLOW_INCLUDE', true);

// Load config
require_once __DIR__ . '/../../includes/config.php';

// After config.php loads, check all sources
$results = [
    'timestamp' => date('Y-m-d H:i:s'),
    'config_loaded' => defined('ENV_LOADED'),
    'sources' => [
        'constant' => [
            'FISKALY_API_KEY' => defined('FISKALY_API_KEY') ? substr(FISKALY_API_KEY, 0, 15) . '...' : 'NOT DEFINED',
            'FISKALY_API_SECRET' => defined('FISKALY_API_SECRET') ? substr(FISKALY_API_SECRET, 0, 10) . '...' : 'NOT DEFINED',
            'FISKALY_TSS_ID' => defined('FISKALY_TSS_ID') ? FISKALY_TSS_ID : 'NOT DEFINED',
            'FISKALY_CLIENT_ID' => defined('FISKALY_CLIENT_ID') ? FISKALY_CLIENT_ID : 'NOT DEFINED'
        ],
        'getenv' => [
            'FISKALY_API_KEY' => getenv('FISKALY_API_KEY') ?: 'NOT SET',
            'FISKALY_API_SECRET' => getenv('FISKALY_API_SECRET') ? substr(getenv('FISKALY_API_SECRET'), 0, 10) . '...' : 'NOT SET',
            'FISKALY_TSS_ID' => getenv('FISKALY_TSS_ID') ?: 'NOT SET',
            'FISKALY_CLIENT_ID' => getenv('FISKALY_CLIENT_ID') ?: 'NOT SET'
        ],
        '$_ENV' => [
            'FISKALY_API_KEY' => $_ENV['FISKALY_API_KEY'] ?? 'NOT SET',
            'FISKALY_API_SECRET' => isset($_ENV['FISKALY_API_SECRET']) ? substr($_ENV['FISKALY_API_SECRET'], 0, 10) . '...' : 'NOT SET',
            'FISKALY_TSS_ID' => $_ENV['FISKALY_TSS_ID'] ?? 'NOT SET',
            'FISKALY_CLIENT_ID' => $_ENV['FISKALY_CLIENT_ID'] ?? 'NOT SET'
        ],
        '$_SERVER' => [
            'FISKALY_API_KEY' => $_SERVER['FISKALY_API_KEY'] ?? 'NOT SET',
            'FISKALY_API_SECRET' => isset($_SERVER['FISKALY_API_SECRET']) ? substr($_SERVER['FISKALY_API_SECRET'], 0, 10) . '...' : 'NOT SET',
            'FISKALY_TSS_ID' => $_SERVER['FISKALY_TSS_ID'] ?? 'NOT SET',
            'FISKALY_CLIENT_ID' => $_SERVER['FISKALY_CLIENT_ID'] ?? 'NOT SET'
        ]
    ]
];

echo json_encode($results, JSON_PRETTY_PRINT);
?>

