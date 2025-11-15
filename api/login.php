<?php
/**
 * User Login Handler
 * Handles AJAX login requests
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

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
$email = isset($input['email']) ? trim($input['email']) : '';
$password = isset($input['password']) ? $input['password'] : '';
$remember = isset($input['remember']) ? (bool)$input['remember'] : false;

// Validation
if (empty($email)) {
    echo json_encode([
        'success' => false,
        'message' => 'E-Mail ist erforderlich.'
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Ungültige E-Mail-Adresse.'
    ]);
    exit;
}

if (empty($password)) {
    echo json_encode([
        'success' => false,
        'message' => 'Passwort ist erforderlich.'
    ]);
    exit;
}

try {
    // Get database connection
    $pdo = getDBConnection();

    // Check login attempts (basic rate limiting)
    $loginAttemptKey = 'login_attempts_' . $_SERVER['REMOTE_ADDR'];
    $attempts = isset($_SESSION[$loginAttemptKey]) ? $_SESSION[$loginAttemptKey] : 0;
    $lockoutTime = isset($_SESSION[$loginAttemptKey . '_time']) ? $_SESSION[$loginAttemptKey . '_time'] : 0;

    // Check if locked out
    if ($attempts >= MAX_LOGIN_ATTEMPTS && (time() - $lockoutTime) < LOGIN_LOCKOUT_TIME) {
        $remainingTime = LOGIN_LOCKOUT_TIME - (time() - $lockoutTime);
        echo json_encode([
            'success' => false,
            'message' => 'Zu viele Anmeldeversuche. Bitte versuchen Sie es in ' . ceil($remainingTime / 60) . ' Minuten erneut.'
        ]);
        exit;
    }

    // Reset attempts if lockout time has passed
    if ($attempts >= MAX_LOGIN_ATTEMPTS && (time() - $lockoutTime) >= LOGIN_LOCKOUT_TIME) {
        $_SESSION[$loginAttemptKey] = 0;
    }

    // Find user by email
    $stmt = $pdo->prepare("
        SELECT id, firstname, lastname, email, password, is_active
        FROM users
        WHERE email = ?
    ");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // Check if user exists and password is correct
    if (!$user || !password_verify($password, $user['password'])) {
        // Increment failed login attempts
        $_SESSION[$loginAttemptKey] = $attempts + 1;
        $_SESSION[$loginAttemptKey . '_time'] = time();

        echo json_encode([
            'success' => false,
            'message' => 'Ungültige E-Mail oder Passwort.'
        ]);
        exit;
    }

    // Check if account is active
    if (!$user['is_active']) {
        echo json_encode([
            'success' => false,
            'message' => 'Ihr Konto wurde deaktiviert. Bitte kontaktieren Sie den Support.'
        ]);
        exit;
    }

    // Reset login attempts
    unset($_SESSION[$loginAttemptKey]);
    unset($_SESSION[$loginAttemptKey . '_time']);

    // Regenerate session ID to prevent session fixation attacks
    session_regenerate_id(true);

    // Set session variables (using consistent naming)
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_firstname'] = $user['firstname'];
    $_SESSION['user_lastname'] = $user['lastname'];
    $_SESSION['user_logged_in'] = true;

    // Update last login time
    $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
    $updateStmt->execute([$user['id']]);

    // Set remember me cookie if requested (30 days)
    if ($remember) {
        try {
            createRememberMeToken($user['id']);
        } catch (Exception $ex) {
            // Fail silently; login still succeeds
            error_log('Remember me token error: ' . $ex->getMessage());
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Anmeldung erfolgreich! Willkommen zurück, ' . $user['firstname'] . '!',
        'user' => [
            'id' => $user['id'],
            'firstname' => $user['firstname'],
            'lastname' => $user['lastname'],
            'email' => $user['email']
        ]
    ]);

} catch (PDOException $e) {
    error_log("Login error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.'
    ]);
}
?>
