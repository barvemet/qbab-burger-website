<?php
/**
 * User Registration Handler
 * Handles AJAX registration requests
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
$firstname = isset($input['firstname']) ? trim($input['firstname']) : '';
$lastname = isset($input['lastname']) ? trim($input['lastname']) : '';
$email = isset($input['email']) ? trim($input['email']) : '';
$password = isset($input['password']) ? $input['password'] : '';
$passwordConfirm = isset($input['password_confirm']) ? $input['password_confirm'] : '';

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

// Validate email
if (empty($email)) {
    $errors[] = 'E-Mail ist erforderlich.';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Ungültige E-Mail-Adresse.';
}

// Validate password
if (empty($password)) {
    $errors[] = 'Passwort ist erforderlich.';
} elseif (strlen($password) < 8) {
    $errors[] = 'Passwort muss mindestens 8 Zeichen lang sein.';
}

// Validate password confirmation
if ($password !== $passwordConfirm) {
    $errors[] = 'Passwörter stimmen nicht überein.';
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

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);

    if ($stmt->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => 'Diese E-Mail-Adresse ist bereits registriert.'
        ]);
        exit;
    }

    // Hash password
    $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    // Generate verification token (optional for email verification)
    $verificationToken = bin2hex(random_bytes(32));

    // Insert new user
    $stmt = $pdo->prepare("
        INSERT INTO users (firstname, lastname, email, password, verification_token, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");

    $stmt->execute([$firstname, $lastname, $email, $passwordHash, $verificationToken]);

    $userId = $pdo->lastInsertId();

    // Regenerate session ID to prevent session fixation attacks
    session_regenerate_id(true);

    // Auto-login after registration (using consistent naming)
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_firstname'] = $firstname;
    $_SESSION['user_lastname'] = $lastname;
    $_SESSION['user_logged_in'] = true;

    // Send verification email (fail silently)
    try {
        sendVerificationEmail($email, $firstname, $verificationToken);
    } catch (Exception $e) {
        error_log('Verification email error: ' . $e->getMessage());
    }

    echo json_encode([
        'success' => true,
        'message' => 'Registrierung erfolgreich! Willkommen, ' . $firstname . '!',
        'user' => [
            'id' => $userId,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email
        ]
    ]);

} catch (PDOException $e) {
    error_log("Registration error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.'
    ]);
}

/**
 * Send email verification link to user
 */
function sendVerificationEmail($email, $firstname, $token) {
    // Build verification URL
    $baseUrl = defined('SITE_URL') && SITE_URL ? rtrim(SITE_URL, '/') : ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on' ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? '') );
    $verifyUrl = $baseUrl . '/verify-email.php?token=' . urlencode($token) . '&email=' . urlencode($email);

    $subject = 'Bitte bestätigen Sie Ihre E-Mail-Adresse';
    $message = "<html><body style=\"font-family:Arial, sans-serif; color:#1a1a1a;\">" .
        "<h2>Hallo " . htmlspecialchars($firstname) . ",</h2>" .
        "<p>Vielen Dank für Ihre Registrierung bei Q-Bab Burger.</p>" .
        "<p>Bitte bestätigen Sie Ihre E-Mail-Adresse, indem Sie auf den folgenden Link klicken:</p>" .
        "<p><a href='" . $verifyUrl . "' style=\"display:inline-block;padding:10px 16px;background:#e74c3c;color:#fff;text-decoration:none;border-radius:6px\">E-Mail bestätigen</a></p>" .
        "<p>Falls der Button nicht funktioniert, kopieren Sie diesen Link in Ihren Browser:</p>" .
        "<p><a href='" . $verifyUrl . "'>" . $verifyUrl . "</a></p>" .
        "<p>Beste Grüße<br>Q-Bab Burger Team</p>" .
        "</body></html>";

    $fromEmail = defined('SMTP_FROM_EMAIL') && SMTP_FROM_EMAIL ? SMTP_FROM_EMAIL : (defined('SMTP_USERNAME') ? SMTP_USERNAME : 'noreply@q-bab.de');
    $fromName  = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'Q-Bab Burger';

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= 'From: ' . $fromName . ' <' . $fromEmail . ">\r\n";
    $headers .= 'Reply-To: ' . $fromEmail . "\r\n";
    $headers .= 'X-Mailer: PHP/' . phpversion();

    // Use native mail(); if server supports SMTP via sendmail it will deliver.
    // For production-grade SMTP, integrate PHPMailer and SMTP_* settings.
    @mail($email, $subject, $message, $headers);
}
?>
