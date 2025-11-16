<?php
/**
 * Database Connection Test
 * Tests if database credentials in .env are correct
 *
 * @author Q-Bab Kasse System
 */

define('ALLOW_INCLUDE', true);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../../includes/config.php';

$result = [
    'timestamp' => date('Y-m-d H:i:s'),
    'test' => 'Database Connection',
    'status' => 'unknown',
    'details' => []
];

try {
    // Test 1: Check if .env variables are loaded
    $result['details']['env_loaded'] = [
        'DB_HOST' => !empty(getenv('DB_HOST')) ? 'Found' : 'Missing',
        'DB_NAME' => !empty(getenv('DB_NAME')) ? 'Found' : 'Missing',
        'DB_USER' => !empty(getenv('DB_USER')) ? 'Found' : 'Missing',
        'DB_PASS' => !empty(getenv('DB_PASS')) ? 'Found' : 'Missing',
    ];

    // Test 2: Attempt database connection
    try {
        $db = getDBConnection();

        // Test 3: Run a simple query
        $stmt = $db->query("SELECT VERSION() as version, DATABASE() as current_db, NOW() as server_time");
        $dbInfo = $stmt->fetch(PDO::FETCH_ASSOC);

        // Test 4: Check if orders table exists
        $stmt = $db->query("SHOW TABLES LIKE 'orders'");
        $ordersTableExists = $stmt->rowCount() > 0;

        // Test 5: Count records in orders table
        $orderCount = 0;
        if ($ordersTableExists) {
            $stmt = $db->query("SELECT COUNT(*) as count FROM orders");
            $orderCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        }

        // Success!
        $result['status'] = 'SUCCESS';
        $result['message'] = 'Database connection successful!';
        $result['details']['connection'] = [
            'host' => getenv('DB_HOST'),
            'database' => getenv('DB_NAME'),
            'user' => getenv('DB_USER'),
            'connected' => true
        ];
        $result['details']['database_info'] = [
            'mysql_version' => $dbInfo['version'],
            'current_database' => $dbInfo['current_db'],
            'server_time' => $dbInfo['server_time']
        ];
        $result['details']['tables'] = [
            'orders_exists' => $ordersTableExists,
            'orders_count' => $orderCount
        ];

        http_response_code(200);

    } catch (PDOException $e) {
        // Connection failed
        $result['status'] = 'ERROR';
        $result['message'] = 'Database connection failed';
        $result['error'] = $e->getMessage();
        $result['error_code'] = $e->getCode();
        $result['details']['connection'] = [
            'host' => getenv('DB_HOST'),
            'database' => getenv('DB_NAME'),
            'user' => getenv('DB_USER'),
            'connected' => false
        ];

        // Common error codes
        if (strpos($e->getMessage(), 'Access denied') !== false) {
            $result['help'] = 'Check DB_USER and DB_PASS in .env file';
        } elseif (strpos($e->getMessage(), 'Unknown database') !== false) {
            $result['help'] = 'Check DB_NAME in .env file - database does not exist';
        } elseif (strpos($e->getMessage(), "Can't connect") !== false) {
            $result['help'] = 'Check DB_HOST in .env file - cannot reach database server';
        }

        http_response_code(500);
    }

} catch (Exception $e) {
    $result['status'] = 'ERROR';
    $result['message'] = 'Configuration error';
    $result['error'] = $e->getMessage();
    http_response_code(500);
}

echo json_encode($result, JSON_PRETTY_PRINT);
?>
