<?php
// Q-Bab Burger - Configuration File
// Compatible with Strato hosting

// Prevent direct access
if (!defined('ALLOW_INCLUDE')) {
    die('Direct access not permitted');
}

// Load environment variables from .env file
function loadEnv($path) {
    // Check if already loaded to prevent duplicate loading
    if (defined('ENV_LOADED')) {
        return; // Already loaded, skip
    }

    if (!file_exists($path)) {
        // Show detailed error message
        $errorMsg = "<h1>Configuration Error</h1>";
        $errorMsg .= "<p><strong>.env file not found!</strong></p>";
        $errorMsg .= "<p>Expected location: <code>" . htmlspecialchars($path) . "</code></p>";
        $errorMsg .= "<p>Please create this file with your database and site configuration.</p>";
        $errorMsg .= "<h3>Required .env format:</h3>";
        $errorMsg .= "<pre style='background:#f4f4f4;padding:15px;border-left:3px solid #e74c3c;'>";
        $errorMsg .= "DEBUG_MODE=true\n";
        $errorMsg .= "DISPLAY_ERRORS=1\n\n";
        $errorMsg .= "DB_HOST=localhost\n";
        $errorMsg .= "DB_NAME=qbab_burger\n";
        $errorMsg .= "DB_USER=your_username\n";
        $errorMsg .= "DB_PASS=your_password\n";
        $errorMsg .= "DB_CHARSET=utf8mb4\n\n";
        $errorMsg .= "SITE_URL=http://localhost/qbab-burger-website\n";
        $errorMsg .= "ADMIN_URL=http://localhost/qbab-burger-website/admin\n";
        $errorMsg .= "ASSETS_URL=http://localhost/qbab-burger-website/assets\n";
        $errorMsg .= "UPLOADS_URL=http://localhost/qbab-burger-website/assets/uploads\n";
        $errorMsg .= "ASSET_VERSION=1.0.0\n\n";
        $errorMsg .= "ADMIN_EMAIL=admin@example.com\n";
        $errorMsg .= "ADMIN_PHONE=+49123456789\n";
        $errorMsg .= "</pre>";
        die($errorMsg);
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove quotes if present
            if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                $value = substr($value, 1, -1);
            }

            // Set in multiple places for maximum compatibility
            putenv("$key=$value");           // For getenv()
            $_ENV[$key] = $value;             // For $_ENV
            $_SERVER[$key] = $value;          // For $_SERVER
            
            // Define as constant
            if (!defined($key)) {
                define($key, $value);
            }
        }
    }

    // Mark as loaded
    define('ENV_LOADED', true);
}

// Load .env file (go up one directory from includes/)
loadEnv(dirname(__DIR__) . '/.env');

// Error reporting (from .env)
$debugMode = getenv('DEBUG_MODE') === 'true';
$displayErrors = getenv('DISPLAY_ERRORS') ?: 0;

error_reporting(E_ALL);
ini_set('display_errors', $displayErrors);
ini_set('log_errors', 1);

// Create logs directory if it doesn't exist
$logDir = __DIR__ . '/../logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}
ini_set('error_log', $logDir . '/error.log');

// Database configuration (from .env)
if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST'));
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME'));
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER'));
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS'));
if (!defined('DB_CHARSET')) define('DB_CHARSET', getenv('DB_CHARSET'));

// Site configuration (from .env)
if (!defined('SITE_URL')) define('SITE_URL', getenv('SITE_URL'));
if (!defined('ADMIN_URL')) define('ADMIN_URL', getenv('ADMIN_URL'));
if (!defined('ASSETS_URL')) define('ASSETS_URL', getenv('ASSETS_URL'));
if (!defined('UPLOADS_URL')) define('UPLOADS_URL', getenv('UPLOADS_URL'));
// Static asset version (for cache-busting only on deploys, not every request)
if (!defined('ASSET_VERSION')) define('ASSET_VERSION', getenv('ASSET_VERSION') ?: '2025-10-08');

// File paths
define('ROOT_PATH', dirname(__DIR__));
define('INCLUDES_PATH', ROOT_PATH . '/includes');
define('LANGUAGES_PATH', ROOT_PATH . '/languages');
define('UPLOADS_PATH', ROOT_PATH . '/assets/uploads');

// Session configuration
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    // Only use secure cookies if HTTPS is enabled
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 1 : 0);
    ini_set('session.use_strict_mode', 1);
    session_name('QBAB_SESSION');
    session_start();
}

// Timezone
date_default_timezone_set('Europe/Berlin');

// Email configuration (from .env)
if (!defined('SMTP_HOST')) define('SMTP_HOST', getenv('SMTP_HOST'));
if (!defined('SMTP_PORT')) define('SMTP_PORT', getenv('SMTP_PORT'));
if (!defined('SMTP_SECURE')) define('SMTP_SECURE', getenv('SMTP_SECURE'));
if (!defined('SMTP_USERNAME')) define('SMTP_USERNAME', getenv('SMTP_USERNAME'));
if (!defined('SMTP_PASSWORD')) define('SMTP_PASSWORD', getenv('SMTP_PASSWORD'));

// Admin contact (from .env)
if (!defined('ADMIN_EMAIL')) define('ADMIN_EMAIL', getenv('ADMIN_EMAIL'));
if (!defined('ADMIN_PHONE')) define('ADMIN_PHONE', getenv('ADMIN_PHONE'));

// PayPal configuration (from .env)
if (!defined('PAYPAL_MODE')) define('PAYPAL_MODE', getenv('PAYPAL_MODE'));
if (!defined('PAYPAL_CLIENT_ID')) define('PAYPAL_CLIENT_ID', getenv('PAYPAL_CLIENT_ID'));
if (!defined('PAYPAL_SECRET')) define('PAYPAL_SECRET', getenv('PAYPAL_SECRET'));

// Supported languages
define('SUPPORTED_LANGUAGES', ['en', 'de', 'tr']);
define('DEFAULT_LANGUAGE', 'de');

// Security (from .env)
if (!defined('ADMIN_SESSION_TIMEOUT')) define('ADMIN_SESSION_TIMEOUT', getenv('ADMIN_SESSION_TIMEOUT'));
if (!defined('MAX_LOGIN_ATTEMPTS')) define('MAX_LOGIN_ATTEMPTS', getenv('MAX_LOGIN_ATTEMPTS'));
if (!defined('LOGIN_LOCKOUT_TIME')) define('LOGIN_LOCKOUT_TIME', getenv('LOGIN_LOCKOUT_TIME'));

// Upload settings
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024);  // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// Google Maps API (from .env)
if (!defined('GOOGLE_MAPS_API_KEY')) define('GOOGLE_MAPS_API_KEY', getenv('GOOGLE_MAPS_API_KEY'));

// Helper function to get database connection
function getDBConnection() {
    static $conn = null;

    if ($conn === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];
            $conn = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            
            // Show detailed error in development mode
            if (getenv('DEBUG_MODE') === 'true') {
                die("<h1>Database Connection Error</h1>" .
                    "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>" .
                    "<p><strong>Host:</strong> " . htmlspecialchars(DB_HOST) . "</p>" .
                    "<p><strong>Database:</strong> " . htmlspecialchars(DB_NAME) . "</p>" .
                    "<p><strong>User:</strong> " . htmlspecialchars(DB_USER) . "</p>" .
                    "<p>Please check your database configuration in .env file.</p>");
            }
            
            die("Database connection failed. Please try again later.");
        }
    }

    return $conn;
}

// Helper function to sanitize input
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Helper function to generate CSRF token
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Helper function to verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Helper function to check if user is logged in (admin)
function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// Helper function to require admin login
function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        header('Location: ' . ADMIN_URL . '/login.php');
        exit;
    }
}

// Helper function to get current language
function getCurrentLanguage() {
    // Check if language is already set in session
    if (isset($_SESSION['language']) && in_array($_SESSION['language'], SUPPORTED_LANGUAGES)) {
        return $_SESSION['language'];
    }

    // Default to German (Almanca) - Site ana dili
    // Tarayıcı dil algılama devre dışı - Her zaman Almanca başlar
    $_SESSION['language'] = DEFAULT_LANGUAGE;
    return DEFAULT_LANGUAGE;
}

// Helper function to format price
function formatPrice($price, $currency = '€') {
    return number_format($price, 2, ',', '.') . ' ' . $currency;
}

// Helper function to generate order number
function generateOrderNumber() {
    return 'QB' . date('Ymd') . strtoupper(substr(uniqid(), -6));
}

// Rate limiting helper function
function checkRateLimit($action, $maxAttempts = 60, $timeWindow = 60) {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $key = 'rate_limit_' . $action . '_' . $ip;
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'start_time' => time()];
    }
    
    $data = $_SESSION[$key];
    $elapsed = time() - $data['start_time'];
    
    // Reset if time window passed
    if ($elapsed > $timeWindow) {
        $_SESSION[$key] = ['count' => 1, 'start_time' => time()];
        return true;
    }
    
    // Check if limit exceeded
    if ($data['count'] >= $maxAttempts) {
        return false;
    }
    
    // Increment counter
    $_SESSION[$key]['count']++;
    return true;
}

// Admin audit logging
function logAdminAction($action, $details = '', $table = null, $record_id = null) {
    if (!isAdminLoggedIn()) {
        return;
    }
    
    try {
        $db = getDBConnection();
        
        $stmt = $db->prepare("
            INSERT INTO admin_audit_log 
            (admin_id, admin_username, action, details, table_name, record_id, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_SESSION['admin_id'] ?? null,
            $_SESSION['admin_username'] ?? 'unknown',
            $action,
            $details,
            $table,
            $record_id,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (Exception $e) {
        error_log('Audit log failed: ' . $e->getMessage());
    }
}

// Auto-load translations
require_once LANGUAGES_PATH . '/translations.php';

// ==============================
// Remember Me implementation
// ==============================
if (!defined('REMEMBER_COOKIE_NAME')) define('REMEMBER_COOKIE_NAME', 'remember_token');
if (!defined('REMEMBER_COOKIE_LIFETIME')) define('REMEMBER_COOKIE_LIFETIME', 30 * 24 * 60 * 60); // 30 days

function setRememberCookie($value, $expiresAt)
{
    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    // Path '/'; domain default; secure based on HTTPS; httponly true
    setcookie(REMEMBER_COOKIE_NAME, $value, $expiresAt, '/', '', $secure, true);
}

function createRememberMeToken($userId)
{
    $pdo = getDBConnection();
    $selector = bin2hex(random_bytes(12));
    $validator = bin2hex(random_bytes(32));
    $validatorHash = hash('sha256', $validator);
    $expires = time() + REMEMBER_COOKIE_LIFETIME;
    $expiresAt = date('Y-m-d H:i:s', $expires);

    try {
        $stmt = $pdo->prepare('INSERT INTO user_remember_tokens (user_id, selector, validator_hash, expires_at, user_agent, ip_address, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $stmt->execute([$userId, $selector, $validatorHash, $expiresAt, $ua, $ip]);
    } catch (Exception $e) {
        // Table may not exist yet; rethrow to be handled upstream
        throw $e;
    }

    // Set cookie as selector:validator
    $cookieValue = $selector . ':' . $validator;
    setRememberCookie($cookieValue, $expires);
}

function validateRememberMeToken($cookie)
{
    if (!$cookie || strpos($cookie, ':') === false) return null;
    list($selector, $validator) = explode(':', $cookie, 2);
    if (!$selector || !$validator) return null;

    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare('SELECT * FROM user_remember_tokens WHERE selector = ? AND expires_at > NOW()');
        $stmt->execute([$selector]);
        $row = $stmt->fetch();
        if (!$row) return null;
        $calc = hash('sha256', $validator);
        if (!hash_equals($row['validator_hash'], $calc)) {
            // Possible theft - delete this token
            $del = $pdo->prepare('DELETE FROM user_remember_tokens WHERE selector = ?');
            $del->execute([$selector]);
            return null;
        }
        return $row; // valid
    } catch (Exception $e) {
        error_log('Remember me validate error: ' . $e->getMessage());
        return null;
    }
}

function rotateRememberMeToken($selector)
{
    $pdo = getDBConnection();
    $newValidator = bin2hex(random_bytes(32));
    $newHash = hash('sha256', $newValidator);
    $expires = time() + REMEMBER_COOKIE_LIFETIME;
    $expiresAt = date('Y-m-d H:i:s', $expires);

    $stmt = $pdo->prepare('UPDATE user_remember_tokens SET validator_hash = ?, expires_at = ?, last_used_at = NOW() WHERE selector = ?');
    $stmt->execute([$newHash, $expiresAt, $selector]);

    setRememberCookie($selector . ':' . $newValidator, $expires);
}

function autoLoginFromRememberMe()
{
    if (!empty($_SESSION['user_logged_in'])) return; // already logged in
    $cookie = $_COOKIE[REMEMBER_COOKIE_NAME] ?? null;
    if (!$cookie) return;

    $row = validateRememberMeToken($cookie);
    if (!$row) {
        // Clear invalid cookie
        setRememberCookie('', time() - 3600);
        return;
    }

    // Load user and set session
    try {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare('SELECT id, firstname, lastname, email, is_active FROM users WHERE id = ?');
        $stmt->execute([$row['user_id']]);
        $user = $stmt->fetch();
        if ($user && (!isset($user['is_active']) || $user['is_active'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_firstname'] = $user['firstname'] ?? '';
            $_SESSION['user_lastname'] = $user['lastname'] ?? '';
            $_SESSION['user_logged_in'] = true;
            // Rotate validator to prevent replay
            rotateRememberMeToken($row['selector']);
        } else {
            // Invalidate token for inactive/nonexistent user
            $del = $pdo->prepare('DELETE FROM user_remember_tokens WHERE selector = ?');
            $del->execute([$row['selector']]);
            setRememberCookie('', time() - 3600);
        }
    } catch (Exception $e) {
        error_log('Remember me autologin error: ' . $e->getMessage());
    }
}

// Attempt auto-login from remember me cookie on every request
try { autoLoginFromRememberMe(); } catch (Exception $e) { /* ignore */ }

?>
