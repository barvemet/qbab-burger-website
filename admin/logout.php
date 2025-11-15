<?php
session_start();

// Load configuration for audit logging
if (!defined('ALLOW_INCLUDE')) {
    define('ALLOW_INCLUDE', true);
}
require_once __DIR__ . '/../includes/config.php';

// Log logout action before destroying session
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
    logAdminAction('LOGOUT', 'Admin logged out');
}

// Destroy session
$_SESSION = array();
session_destroy();

// Redirect to login page
header('Location: login.php');
exit;
?>
