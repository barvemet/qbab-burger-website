<?php
session_start();

// Check if logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Load configuration
if (!defined('ALLOW_INCLUDE')) {
    define('ALLOW_INCLUDE', true);
}
require_once __DIR__ . '/../includes/config.php';

$db = getDBConnection();

// Get statistics
$stats = [
    'orders_today' => 0,
    'total_revenue' => 0,
    'menu_items' => 0,
    'pending_reviews' => 0
];

try {
    // Count orders today
    $stmt = $db->query("SELECT COUNT(*) as count FROM orders WHERE DATE(created_at) = CURDATE()");
    $stats['orders_today'] = $stmt->fetch()['count'];

    // Total revenue - fixed: use 'completed' instead of 'paid'
    $stmt = $db->query("SELECT SUM(total_amount) as total FROM orders WHERE payment_status IN ('completed', 'pending')");
    $stats['total_revenue'] = $stmt->fetch()['total'] ?? 0;

    // Menu items count
    $stmt = $db->query("SELECT COUNT(*) as count FROM menu_items WHERE is_active = 1");
    $stats['menu_items'] = $stmt->fetch()['count'];

    // Pending reviews
    $stmt = $db->query("SELECT COUNT(*) as count FROM reviews WHERE is_approved = 0");
    $stats['pending_reviews'] = $stmt->fetch()['count'];
} catch (Exception $e) {
    // Error handling
    error_log('Admin dashboard stats error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Q-Bab Burger</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 {
            font-size: 1.5rem;
        }
        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .stat-card h3 {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        .stat-card .value {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }
        .welcome {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .welcome h2 {
            color: #333;
            margin-bottom: 20px;
        }
        .welcome p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 20px;
        }
        .info-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
        }
        .info-box strong {
            display: block;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>🍔 Q-Bab Burger - Admin Panel</h1>
            <div>
                <span style="margin-right: 20px;">Willkommen, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</span>
                <a href="logout.php" class="logout-btn">Abmelden</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Bestellungen Heute</h3>
                <div class="value"><?php echo $stats['orders_today']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Gesamtumsatz</h3>
                <div class="value"><?php echo formatPrice($stats['total_revenue']); ?></div>
            </div>
            <div class="stat-card">
                <h3>Aktive Menüpunkte</h3>
                <div class="value"><?php echo $stats['menu_items']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Ausstehende Bewertungen</h3>
                <div class="value"><?php echo $stats['pending_reviews']; ?></div>
            </div>
        </div>
        
        <div class="welcome">
            <h2>Willkommen im Q-Bab Burger Admin Panel!</h2>
            <p>Von hier aus können Sie Ihre Restaurant-Website verwalten.</p>
            
            <div class="info-box">
                <strong>ℹ️ Wichtige Information:</strong>
                Dies ist ein Basis-Admin-Dashboard. Die vollständige Verwaltungsoberfläche befindet sich noch in der Entwicklung.<br><br>
                Aktuell können Sie:<br>
                • Statistiken einsehen<br>
                • Datenbankzugriff über PhpMyAdmin nutzen<br>
                • Website-Frontend verwenden
            </div>
            
            <div style="margin-top: 30px;">
                <a href="orders.php" class="logout-btn" style="margin-right: 10px; background: #dc3545;">📦 Bestellungen</a>
                <a href="categories.php" class="logout-btn" style="margin-right: 10px; background: #e10000;">📁 Kategorien</a>
                <a href="menu-items.php" class="logout-btn" style="margin-right: 10px; background: #007bff;">🍔 Menü-Produkte</a>
                <a href="extras.php" class="logout-btn" style="margin-right: 10px; background: #17a2b8;">🧩 Extras & Toppings</a>
                <a href="special-offers.php" class="logout-btn" style="margin-right: 10px; background: #f39c12;">🎁 Special Offers</a>
        <a href="audit-log.php" class="logout-btn" style="margin-right: 10px; background: #6c757d;">📜 Audit Log</a>
                <a href="menu-list-items.php" class="logout-btn" style="margin-right: 10px; background: #fd7e14;">📋 Menü-Liste Verwaltung</a>
                <a href="popular-items.php" class="logout-btn" style="margin-right: 10px; background: #28a745;">⭐ Beliebte Produkte</a>
                <a href="reviews.php" class="logout-btn" style="margin-right: 10px; background: #ffc107; color: #333;">💬 Bewertungen</a>
                <a href="newsletter.php" class="logout-btn" style="margin-right: 10px; background: #e74c3c;">📧 Newsletter</a>
                <a href="homepage-settings.php" class="logout-btn" style="margin-right: 10px; background: #6f42c1;">🏠 Homepage Einstellungen</a>
                <a href="customers.php" class="logout-btn" style="margin-right: 10px; background: #17a2b8;">👥 Kunden</a>
                <a href="<?php echo SITE_URL; ?>" class="logout-btn" style="margin-right: 10px;">Zur Website</a>
                <a href="https://<?php echo DB_HOST; ?>/phpMyAdmin" target="_blank" class="logout-btn">PhpMyAdmin</a>
            </div>
        </div>
    </div>
</body>
</html>
