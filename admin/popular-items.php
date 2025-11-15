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
$message = '';
$error = '';

// Handle form submission (mark item as popular/unpopular)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $itemId = (int)$_POST['item_id'];

    if ($_POST['action'] === 'toggle_popular') {
        try {
            $stmt = $db->prepare("UPDATE menu_items SET is_popular = NOT is_popular WHERE id = ?");
            $stmt->execute([$itemId]);
            $message = 'Status erfolgreich aktualisiert!';
        } catch (Exception $e) {
            $error = 'Fehler beim Aktualisieren: ' . $e->getMessage();
        }
    }
}

// Get all menu items with popular status
try {
    $stmt = $db->query("
        SELECT mi.*, mc.name_de as category_name
        FROM menu_items mi
        LEFT JOIN menu_categories mc ON mi.category_id = mc.id
        WHERE mi.is_active = 1
        ORDER BY mi.is_popular DESC, mc.display_order, mi.display_order
    ");
    $menuItems = $stmt->fetchAll();

    // Get count of popular items
    $popularCount = count(array_filter($menuItems, function($item) {
        return $item['is_popular'] == 1;
    }));
} catch (Exception $e) {
    $error = 'Fehler beim Laden der Menüpunkte: ' . $e->getMessage();
    $menuItems = [];
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Beliebte Produkte verwalten - Admin</title>
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
        .nav-links {
            display: flex;
            gap: 15px;
        }
        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
            background: rgba(255,255,255,0.2);
            transition: all 0.3s;
        }
        .nav-links a:hover {
            background: rgba(255,255,255,0.3);
        }
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }
        .info-box {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .info-box h2 {
            color: #333;
            margin-bottom: 10px;
        }
        .info-box p {
            color: #666;
            line-height: 1.6;
        }
        .popular-count {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            margin-left: 10px;
        }
        .items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        .item-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .item-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        .item-card.popular {
            border: 3px solid #ffc107;
            box-shadow: 0 0 20px rgba(255, 193, 7, 0.3);
        }
        .item-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            background: #f0f0f0;
        }
        .item-content {
            padding: 20px;
        }
        .item-category {
            font-size: 0.85rem;
            color: #999;
            margin-bottom: 5px;
        }
        .item-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }
        .item-price {
            font-size: 1.3rem;
            color: #667eea;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .item-status {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 15px;
        }
        .status-popular {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffc107;
        }
        .status-normal {
            background: #e9ecef;
            color: #6c757d;
        }
        .item-actions {
            display: flex;
            gap: 10px;
        }
        .btn {
            flex: 1;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            text-align: center;
        }
        .btn-toggle {
            background: #667eea;
            color: white;
        }
        .btn-toggle:hover {
            background: #5568d3;
        }
        .btn-remove {
            background: #dc3545;
            color: white;
        }
        .btn-remove:hover {
            background: #c82333;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>🍔 Beliebte Produkte verwalten</h1>
            <div class="nav-links">
                <a href="index.php">← Dashboard</a>
                <a href="logout.php">Abmelden</a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if ($message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="info-box">
            <h2>
                Beliebte Produkte auf der Startseite
                <span class="popular-count"><?php echo $popularCount; ?> / 4</span>
            </h2>
            <p>
                Die als "beliebt" markierten Produkte werden auf der Startseite in der Sektion "BELIEBTESTEN Q-BAB" angezeigt.
                Es werden maximal 4 Produkte angezeigt. Klicken Sie auf "Als beliebt markieren/entfernen", um den Status zu ändern.
            </p>
        </div>

        <div class="items-grid">
            <?php foreach ($menuItems as $item): ?>
            <div class="item-card <?php echo $item['is_popular'] ? 'popular' : ''; ?>">
                <?php if ($item['image']): ?>
                <img src="<?php echo UPLOADS_URL . '/' . htmlspecialchars($item['image']); ?>"
                     alt="<?php echo htmlspecialchars($item['name_de']); ?>"
                     class="item-image">
                <?php else: ?>
                <div class="item-image" style="display: flex; align-items: center; justify-content: center; color: #999;">
                    Kein Bild
                </div>
                <?php endif; ?>

                <div class="item-content">
                    <div class="item-category"><?php echo htmlspecialchars($item['category_name'] ?? 'Keine Kategorie'); ?></div>
                    <div class="item-name"><?php echo htmlspecialchars($item['name_de']); ?></div>
                    <div class="item-price"><?php echo formatPrice($item['price']); ?></div>

                    <div class="item-status <?php echo $item['is_popular'] ? 'status-popular' : 'status-normal'; ?>">
                        <?php echo $item['is_popular'] ? '⭐ Beliebt' : 'Normal'; ?>
                    </div>

                    <div class="item-actions">
                        <form method="POST" style="flex: 1;">
                            <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                            <input type="hidden" name="action" value="toggle_popular">
                            <button type="submit" class="btn btn-toggle">
                                <?php echo $item['is_popular'] ? 'Als normal markieren' : 'Als beliebt markieren'; ?>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($menuItems)): ?>
        <div class="info-box" style="text-align: center; color: #999;">
            <p>Keine Menüpunkte gefunden. Bitte fügen Sie zuerst Menüpunkte über PhpMyAdmin hinzu.</p>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
