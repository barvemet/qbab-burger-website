<?php
// Email Verification Endpoint
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('ALLOW_INCLUDE')) {
    define('ALLOW_INCLUDE', true);
}
require_once __DIR__ . '/includes/config.php';

$lang = getCurrentLanguage();

$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$email = isset($_GET['email']) ? trim($_GET['email']) : '';

$success = false;
$error   = '';

if ($token && $email) {
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare('UPDATE users SET email_verified = 1, verification_token = NULL WHERE email = ? AND verification_token = ?');
        $stmt->execute([$email, $token]);
        if ($stmt->rowCount() > 0) {
            $success = true;
        } else {
            // Already verified or invalid token
            // Try to see if already verified
            $check = $pdo->prepare('SELECT email_verified FROM users WHERE email = ?');
            $check->execute([$email]);
            $row = $check->fetch();
            if ($row && intval($row['email_verified']) === 1) {
                $success = true; // treat as success
            } else {
                $error = 'Ungültiger oder abgelaufener Bestätigungslink.';
            }
        }
    } catch (Exception $e) {
        error_log('Email verify error: ' . $e->getMessage());
        $error = 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.';
    }
} else {
    $error = 'Ungültige Anfrage.';
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $success ? 'E-Mail bestätigt' : 'Bestätigung fehlgeschlagen'; ?> - Q-Bab Burger</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/navbar.css?v=<?php echo ASSET_VERSION; ?>">
    <style>
        body { background: #f9a825; font-family: 'Bebas Neue', Arial, sans-serif; min-height: 100vh; }
        .container { max-width: 900px; margin: 120px auto 60px; padding: 0 40px; }
        .box { background: #fff; padding: 60px 40px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); text-align: center; }
        h1 { font-size: 3rem; color: #1a1a1a; margin-bottom: 20px; letter-spacing: 2px; }
        p { font-family: Arial, sans-serif; font-size: 1.1rem; color: #666; margin-bottom: 15px; line-height: 1.6; }
        .btn { display: inline-block; margin-top: 20px; padding: 15px 40px; background: #e74c3c; color: #fff; text-decoration: none; letter-spacing: 2px; transition: all .3s; }
        .btn:hover { background: #c0392b; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>
    <div class="container">
        <div class="box">
            <?php if ($success): ?>
                <h1>E-Mail bestätigt!</h1>
                <p>Ihre E-Mail-Adresse wurde erfolgreich verifiziert.</p>
                <a href="index.php" class="btn">Zur Startseite</a>
            <?php else: ?>
                <h1>Bestätigung fehlgeschlagen</h1>
                <p><?php echo htmlspecialchars($error); ?></p>
                <a href="index.php" class="btn">Zur Startseite</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
