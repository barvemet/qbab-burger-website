<?php
// API: Change Language
define('ALLOW_INCLUDE', true);
require_once __DIR__ . '/../includes/config.php';

header('Content-Type: application/json');

// Get request data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['language']) || !in_array($data['language'], SUPPORTED_LANGUAGES)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid language code'
    ]);
    exit;
}

// Set language in session
$_SESSION['language'] = $data['language'];

echo json_encode([
    'success' => true,
    'language' => $data['language'],
    'message' => 'Language updated successfully'
]);
?>
