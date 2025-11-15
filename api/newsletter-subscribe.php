<?php
/**
 * Newsletter Subscribe API
 * Handles newsletter subscription requests
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define constant for includes
define('ALLOW_INCLUDE', true);

// Include config
require_once __DIR__ . '/../includes/config.php';

// Set JSON header
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($input['email']) || empty(trim($input['email']))) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'E-Mail-Adresse ist erforderlich.'
    ]);
    exit;
}

$email = trim($input['email']);

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Bitte geben Sie eine gültige E-Mail-Adresse ein.'
    ]);
    exit;
}

try {
    // Get database connection
    $db = getDBConnection();

    // Check if email already exists
    $stmt = $db->prepare("SELECT id, is_active FROM newsletter_subscribers WHERE email = ?");
    $stmt->execute([$email]);
    $existing = $stmt->fetch();

    if ($existing) {
        if ($existing['is_active']) {
            echo json_encode([
                'success' => false,
                'message' => 'Diese E-Mail-Adresse ist bereits für unseren Newsletter registriert.'
            ]);
            exit;
        } else {
            // Reactivate subscription
            $stmt = $db->prepare("UPDATE newsletter_subscribers SET is_active = 1, subscribed_at = NOW() WHERE email = ?");
            $stmt->execute([$email]);

            echo json_encode([
                'success' => true,
                'message' => 'Willkommen zurück! Ihr Newsletter-Abonnement wurde reaktiviert.'
            ]);
            exit;
        }
    }

    // Get user info
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

    // Insert new subscriber
    $stmt = $db->prepare("
        INSERT INTO newsletter_subscribers (email, ip_address, user_agent)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$email, $ip_address, $user_agent]);

    // Success response
    echo json_encode([
        'success' => true,
        'message' => 'Vielen Dank! Sie haben sich erfolgreich für unseren Newsletter angemeldet.'
    ]);

} catch (PDOException $e) {
    // Log error (in production, use proper logging)
    error_log('Newsletter subscription error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.'
    ]);
}
