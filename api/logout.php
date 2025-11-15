<?php
/**
 * User Logout Handler
 */

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clear all session variables
$_SESSION = [];

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Clear remember me cookie and invalidate server-side
if (isset($_COOKIE['remember_token'])) {
    $cookie = $_COOKIE['remember_token'];
    // Attempt to delete token by selector
    try {
        if (strpos($cookie, ':') !== false) {
            list($selector, $validator) = explode(':', $cookie, 2);
            $pdo = getDBConnection();
            $stmt = $pdo->prepare('DELETE FROM user_remember_tokens WHERE selector = ?');
            $stmt->execute([$selector]);
        }
    } catch (Exception $e) {
        error_log('Remember me logout cleanup failed: ' . $e->getMessage());
    }
    setcookie('remember_token', '', time() - 3600, '/', '', true, true);
}

// Destroy the session
session_destroy();

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'Erfolgreich abgemeldet.'
]);
?>
