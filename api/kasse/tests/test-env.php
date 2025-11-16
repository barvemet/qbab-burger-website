<?php
/**
 * Environment Variables Test
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Try to load .env manually
$envPath = __DIR__ . '/../../.env';
$envExists = file_exists($envPath);

$envVars = [];
if ($envExists) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; // Skip comments
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            if (strpos($key, 'FISKALY') === 0) {
                if (strpos($key, 'SECRET') !== false) {
                    $envVars[$key] = substr($value, 0, 10) . '... (hidden)';
                } else {
                    $envVars[$key] = $value;
                }
            }
        }
    }
}

// Check getenv()
$getenvVars = [
    'FISKALY_API_KEY' => getenv('FISKALY_API_KEY'),
    'FISKALY_API_SECRET' => getenv('FISKALY_API_SECRET') ? substr(getenv('FISKALY_API_SECRET'), 0, 10) . '... (hidden)' : false,
    'FISKALY_TSS_ID' => getenv('FISKALY_TSS_ID'),
    'FISKALY_CLIENT_ID' => getenv('FISKALY_CLIENT_ID')
];

// Check $_ENV
$envArrayVars = [
    'FISKALY_API_KEY' => $_ENV['FISKALY_API_KEY'] ?? 'NOT SET',
    'FISKALY_API_SECRET' => isset($_ENV['FISKALY_API_SECRET']) ? substr($_ENV['FISKALY_API_SECRET'], 0, 10) . '... (hidden)' : 'NOT SET',
    'FISKALY_TSS_ID' => $_ENV['FISKALY_TSS_ID'] ?? 'NOT SET',
    'FISKALY_CLIENT_ID' => $_ENV['FISKALY_CLIENT_ID'] ?? 'NOT SET'
];

echo json_encode([
    'success' => true,
    'env_file_path' => $envPath,
    'env_file_exists' => $envExists,
    'env_file_readable' => $envExists && is_readable($envPath),
    'env_file_fiskaly_vars' => $envVars,
    'getenv_results' => $getenvVars,
    'env_array_results' => $envArrayVars,
    'php_version' => PHP_VERSION,
    'current_dir' => __DIR__
], JSON_PRETTY_PRINT);
?>

