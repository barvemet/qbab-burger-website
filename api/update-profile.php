<?php
/**
 * Update User Profile Handler
 * Handles AJAX profile update requests
 */

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load configuration
if (!defined('ALLOW_INCLUDE')) {
    define('ALLOW_INCLUDE', true);
}
require_once __DIR__ . '/../includes/config.php';

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
    echo json_encode([
        'success' => false,
        'message' => 'Sie müssen angemeldet sein, um Ihr Profil zu aktualisieren.'
    ]);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// CSRF check
$csrf = $input['csrf_token'] ?? '';
if (!verifyCSRFToken($csrf)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid CSRF token'
    ]);
    exit;
}

// Validate input
$firstname = isset($input['firstname']) ? trim($input['firstname']) : '';
$lastname = isset($input['lastname']) ? trim($input['lastname']) : '';
$phone = isset($input['phone']) ? trim($input['phone']) : null;
$address = isset($input['address']) ? trim($input['address']) : null;
$city = isset($input['city']) ? trim($input['city']) : null;
$postal_code = isset($input['postal_code']) ? trim($input['postal_code']) : null;

// Validation errors array
$errors = [];

// Validate firstname
if (empty($firstname)) {
    $errors[] = 'Vorname ist erforderlich.';
} elseif (strlen($firstname) < 2 || strlen($firstname) > 50) {
    $errors[] = 'Vorname muss zwischen 2 und 50 Zeichen lang sein.';
}

// Validate lastname
if (empty($lastname)) {
    $errors[] = 'Nachname ist erforderlich.';
} elseif (strlen($lastname) < 2 || strlen($lastname) > 50) {
    $errors[] = 'Nachname muss zwischen 2 und 50 Zeichen lang sein.';
}

// Validate phone (optional)
if (!empty($phone) && strlen($phone) > 20) {
    $errors[] = 'Telefonnummer ist zu lang.';
}

// Validate postal code (optional)
if (!empty($postal_code) && strlen($postal_code) > 10) {
    $errors[] = 'Postleitzahl ist zu lang.';
}

// If there are validation errors, return them
if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'message' => implode(' ', $errors)
    ]);
    exit;
}

try {
    // Get database connection
    $pdo = getDBConnection();

    // Update user profile
    $stmt = $pdo->prepare("
        UPDATE users
        SET firstname = ?,
            lastname = ?,
            phone = ?,
            address = ?,
            city = ?,
            postal_code = ?
        WHERE id = ?
    ");

    $stmt->execute([
        $firstname,
        $lastname,
        $phone,
        $address,
        $city,
        $postal_code,
        $_SESSION['user_id']
    ]);

    // Update session variables
    $_SESSION['user_firstname'] = $firstname;
    $_SESSION['user_lastname'] = $lastname;

    echo json_encode([
        'success' => true,
        'message' => 'Ihr Profil wurde erfolgreich aktualisiert!',
        'user' => [
            'firstname' => $firstname,
            'lastname' => $lastname,
            'phone' => $phone,
            'address' => $address,
            'city' => $city,
            'postal_code' => $postal_code
        ]
    ]);

} catch (PDOException $e) {
    error_log("Profile update error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.'
    ]);
}
?>
