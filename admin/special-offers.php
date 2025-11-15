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

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $stmt = $db->prepare("DELETE FROM special_offers WHERE id = ?");
        $stmt->execute([$id]);
        logAdminAction('DELETE', "Deleted special offer ID: $id", 'special_offers', $id);
        $message = 'Angebot erfolgreich gelöscht!';
    } catch (Exception $e) {
        $error = 'Fehler beim Löschen: ' . $e->getMessage();
    }
}

// Handle form submission (add/edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'CSRF token validation failed';
    } else {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $title_de = sanitize($_POST['title_de']);
        $title_en = sanitize($_POST['title_en']);
        $title_tr = sanitize($_POST['title_tr']);
        $description_de = sanitize($_POST['description_de']);
        $description_en = sanitize($_POST['description_en']);
        $description_tr = sanitize($_POST['description_tr']);
        $original_price = floatval(str_replace(',', '.', $_POST['original_price']));
        $offer_price = floatval(str_replace(',', '.', $_POST['offer_price']));
        $image_url = sanitize($_POST['image_url']);
        $badge_text_de = sanitize($_POST['badge_text_de']);
        $badge_text_en = sanitize($_POST['badge_text_en']);
        $badge_text_tr = sanitize($_POST['badge_text_tr']);
        $badge_color = sanitize($_POST['badge_color']);
        $valid_from = sanitize($_POST['valid_from']);
        $valid_until = !empty($_POST['valid_until']) ? sanitize($_POST['valid_until']) : null;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $display_order = intval($_POST['display_order']);
        $terms_de = sanitize($_POST['terms_de']);
        $terms_en = sanitize($_POST['terms_en']);
        $terms_tr = sanitize($_POST['terms_tr']);
        $button_text_de = sanitize($_POST['button_text_de']);
        $button_text_en = sanitize($_POST['button_text_en']);
        $button_text_tr = sanitize($_POST['button_text_tr']);
        $button_link = sanitize($_POST['button_link']);

        try {
            if ($_POST['action'] === 'add') {
                $stmt = $db->prepare("
                    INSERT INTO special_offers (
                        title_de, title_en, title_tr,
                        description_de, description_en, description_tr,
                        original_price, offer_price, image_url,
                        badge_text_de, badge_text_en, badge_text_tr, badge_color,
                        valid_from, valid_until,
                        is_active, is_featured, display_order,
                        terms_de, terms_en, terms_tr,
                        button_text_de, button_text_en, button_text_tr, button_link
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $title_de, $title_en, $title_tr,
                    $description_de, $description_en, $description_tr,
                    $original_price, $offer_price, $image_url,
                    $badge_text_de, $badge_text_en, $badge_text_tr, $badge_color,
                    $valid_from, $valid_until,
                    $is_active, $is_featured, $display_order,
                    $terms_de, $terms_en, $terms_tr,
                    $button_text_de, $button_text_en, $button_text_tr, $button_link
                ]);
                logAdminAction('CREATE', "Added special offer: $title_de", 'special_offers', $db->lastInsertId());
                $message = 'Angebot erfolgreich erstellt!';
            } else {
                $stmt = $db->prepare("
                    UPDATE special_offers SET
                        title_de = ?, title_en = ?, title_tr = ?,
                        description_de = ?, description_en = ?, description_tr = ?,
                        original_price = ?, offer_price = ?, image_url = ?,
                        badge_text_de = ?, badge_text_en = ?, badge_text_tr = ?, badge_color = ?,
                        valid_from = ?, valid_until = ?,
                        is_active = ?, is_featured = ?, display_order = ?,
                        terms_de = ?, terms_en = ?, terms_tr = ?,
                        button_text_de = ?, button_text_en = ?, button_text_tr = ?, button_link = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $title_de, $title_en, $title_tr,
                    $description_de, $description_en, $description_tr,
                    $original_price, $offer_price, $image_url,
                    $badge_text_de, $badge_text_en, $badge_text_tr, $badge_color,
                    $valid_from, $valid_until,
                    $is_active, $is_featured, $display_order,
                    $terms_de, $terms_en, $terms_tr,
                    $button_text_de, $button_text_en, $button_text_tr, $button_link,
                    $id
                ]);
                logAdminAction('UPDATE', "Updated special offer: $title_de", 'special_offers', $id);
                $message = 'Angebot erfolgreich aktualisiert!';
            }
        } catch (Exception $e) {
            $error = 'Fehler: ' . $e->getMessage();
        }
    }
}

// Fetch all offers
try {
    $stmt = $db->query("
        SELECT * FROM special_offers 
        ORDER BY is_featured DESC, display_order ASC, id DESC
    ");
    $offers = $stmt->fetchAll();
} catch (Exception $e) {
    $offers = [];
    $error = 'Fehler beim Laden: ' . $e->getMessage();
}

// Get offer for editing
$editOffer = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM special_offers WHERE id = ?");
    $stmt->execute([$id]);
    $editOffer = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Special Offers Management - Q-Bab Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #f9a825 0%, #e67e22 100%);
            color: white;
            padding: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 2rem;
            font-weight: 700;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            font-size: 14px;
        }
        
        .btn-primary {
            background: white;
            color: #f9a825;
        }
        
        .btn-primary:hover {
            background: #f5f5f5;
            transform: translateY(-2px);
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-success:hover {
            background: #229954;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c0392b;
        }
        
        .btn-edit {
            background: #3498db;
            color: white;
            padding: 8px 15px;
            font-size: 13px;
        }
        
        .btn-edit:hover {
            background: #2980b9;
        }
        
        .content {
            padding: 30px;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #27ae60;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #e74c3c;
        }
        
        .form-section {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
        }
        
        .form-section h2 {
            color: #333;
            margin-bottom: 25px;
            font-size: 1.5rem;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 600;
            font-size: 14px;
        }
        
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group input[type="datetime-local"],
        .form-group input[type="url"],
        .form-group input[type="color"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #f9a825;
        }
        
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px;
            background: white;
            border-radius: 8px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .table-container {
            overflow-x: auto;
            margin-top: 30px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 12px;
            overflow: hidden;
        }
        
        th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-featured {
            background: #fff3cd;
            color: #856404;
        }
        
        .offer-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .actions {
            display: flex;
            gap: 10px;
        }
        
        .price-info {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .original-price {
            text-decoration: line-through;
            color: #999;
        }
        
        .offer-price {
            color: #27ae60;
            font-weight: bold;
            font-size: 1.1rem;
        }
        
        .discount-badge {
            background: #e74c3c;
            color: white;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .lang-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .lang-tab {
            padding: 10px 20px;
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .lang-tab.active {
            background: #f9a825;
            color: white;
            border-color: #f9a825;
        }
        
        .lang-content {
            display: none;
        }
        
        .lang-content.active {
            display: block;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            border-left: 4px solid #f9a825;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
        }

        .stat-label {
            color: #888;
            font-size: 0.9rem;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎁 Special Offers Management</h1>
            <a href="index.php" class="btn btn-primary">← Back to Dashboard</a>
        </div>
        
        <div class="content">
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo count($offers); ?></div>
                    <div class="stat-label">Total Offers</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo count(array_filter($offers, fn($o) => $o['is_active'] == 1)); ?></div>
                    <div class="stat-label">Active Offers</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo count(array_filter($offers, fn($o) => $o['is_featured'] == 1)); ?></div>
                    <div class="stat-label">Featured</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo array_sum(array_column($offers, 'view_count')); ?></div>
                    <div class="stat-label">Total Views</div>
                </div>
            </div>
            
            <!-- Form -->
            <div class="form-section">
                <h2><?php echo $editOffer ? '✏️ Edit Offer' : '➕ Add New Offer'; ?></h2>
                
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="<?php echo $editOffer ? 'edit' : 'add'; ?>">
                    <?php if ($editOffer): ?>
                        <input type="hidden" name="id" value="<?php echo $editOffer['id']; ?>">
                    <?php endif; ?>
                    
                    <!-- Language Tabs -->
                    <div class="lang-tabs">
                        <div class="lang-tab active" onclick="switchLang('de')">🇩🇪 Deutsch</div>
                        <div class="lang-tab" onclick="switchLang('en')">🇬🇧 English</div>
                        <div class="lang-tab" onclick="switchLang('tr')">🇹🇷 Türkçe</div>
                    </div>
                    
                    <!-- German Content -->
                    <div class="lang-content active" id="lang-de">
                        <div class="form-grid">
                            <div class="form-group full-width">
                                <label>Titel (DE) *</label>
                                <input type="text" name="title_de" value="<?php echo $editOffer ? htmlspecialchars($editOffer['title_de']) : ''; ?>" required>
                            </div>
                            <div class="form-group full-width">
                                <label>Beschreibung (DE) *</label>
                                <textarea name="description_de" required><?php echo $editOffer ? htmlspecialchars($editOffer['description_de']) : ''; ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Badge Text (DE)</label>
                                <input type="text" name="badge_text_de" value="<?php echo $editOffer ? htmlspecialchars($editOffer['badge_text_de']) : 'HOT DEAL'; ?>">
                            </div>
                            <div class="form-group full-width">
                                <label>Bedingungen (DE)</label>
                                <textarea name="terms_de"><?php echo $editOffer ? htmlspecialchars($editOffer['terms_de']) : ''; ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Button Text (DE)</label>
                                <input type="text" name="button_text_de" value="<?php echo $editOffer ? htmlspecialchars($editOffer['button_text_de']) : 'Jetzt bestellen'; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- English Content -->
                    <div class="lang-content" id="lang-en">
                        <div class="form-grid">
                            <div class="form-group full-width">
                                <label>Title (EN) *</label>
                                <input type="text" name="title_en" value="<?php echo $editOffer ? htmlspecialchars($editOffer['title_en']) : ''; ?>" required>
                            </div>
                            <div class="form-group full-width">
                                <label>Description (EN) *</label>
                                <textarea name="description_en" required><?php echo $editOffer ? htmlspecialchars($editOffer['description_en']) : ''; ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Badge Text (EN)</label>
                                <input type="text" name="badge_text_en" value="<?php echo $editOffer ? htmlspecialchars($editOffer['badge_text_en']) : 'HOT DEAL'; ?>">
                            </div>
                            <div class="form-group full-width">
                                <label>Terms (EN)</label>
                                <textarea name="terms_en"><?php echo $editOffer ? htmlspecialchars($editOffer['terms_en']) : ''; ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Button Text (EN)</label>
                                <input type="text" name="button_text_en" value="<?php echo $editOffer ? htmlspecialchars($editOffer['button_text_en']) : 'Order now'; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Turkish Content -->
                    <div class="lang-content" id="lang-tr">
                        <div class="form-grid">
                            <div class="form-group full-width">
                                <label>Başlık (TR) *</label>
                                <input type="text" name="title_tr" value="<?php echo $editOffer ? htmlspecialchars($editOffer['title_tr']) : ''; ?>" required>
                            </div>
                            <div class="form-group full-width">
                                <label>Açıklama (TR) *</label>
                                <textarea name="description_tr" required><?php echo $editOffer ? htmlspecialchars($editOffer['description_tr']) : ''; ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Badge Text (TR)</label>
                                <input type="text" name="badge_text_tr" value="<?php echo $editOffer ? htmlspecialchars($editOffer['badge_text_tr']) : 'SÜPER TEKLİF'; ?>">
                            </div>
                            <div class="form-group full-width">
                                <label>Şartlar (TR)</label>
                                <textarea name="terms_tr"><?php echo $editOffer ? htmlspecialchars($editOffer['terms_tr']) : ''; ?></textarea>
                            </div>
                            <div class="form-group">
                                <label>Button Text (TR)</label>
                                <input type="text" name="button_text_tr" value="<?php echo $editOffer ? htmlspecialchars($editOffer['button_text_tr']) : 'Şimdi sipariş ver'; ?>">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Common Fields -->
                    <div class="form-grid" style="margin-top: 20px;">
                        <div class="form-group">
                            <label>Original Price (€) *</label>
                            <input type="number" step="0.01" name="original_price" value="<?php echo $editOffer ? $editOffer['original_price'] : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Offer Price (€) *</label>
                            <input type="number" step="0.01" name="offer_price" value="<?php echo $editOffer ? $editOffer['offer_price'] : ''; ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Badge Color</label>
                            <input type="color" name="badge_color" value="<?php echo $editOffer ? $editOffer['badge_color'] : '#e74c3c'; ?>">
                        </div>
                        <div class="form-group full-width">
                            <label>Image URL</label>
                            <input type="url" name="image_url" value="<?php echo $editOffer ? htmlspecialchars($editOffer['image_url']) : ''; ?>" placeholder="/assets/images/...">
                        </div>
                        <div class="form-group">
                            <label>Valid From *</label>
                            <input type="datetime-local" name="valid_from" value="<?php echo $editOffer ? date('Y-m-d\TH:i', strtotime($editOffer['valid_from'])) : date('Y-m-d\TH:i'); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Valid Until (Optional)</label>
                            <input type="datetime-local" name="valid_until" value="<?php echo $editOffer && $editOffer['valid_until'] ? date('Y-m-d\TH:i', strtotime($editOffer['valid_until'])) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>Display Order</label>
                            <input type="number" name="display_order" value="<?php echo $editOffer ? $editOffer['display_order'] : '0'; ?>">
                        </div>
                        <div class="form-group">
                            <label>Button Link</label>
                            <input type="text" name="button_link" value="<?php echo $editOffer ? htmlspecialchars($editOffer['button_link']) : '/menu.php'; ?>">
                        </div>
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" name="is_active" id="is_active" <?php echo $editOffer && $editOffer['is_active'] ? 'checked' : ''; ?>>
                                <label for="is_active" style="margin-bottom: 0;">Active</label>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" name="is_featured" id="is_featured" <?php echo $editOffer && $editOffer['is_featured'] ? 'checked' : ''; ?>>
                                <label for="is_featured" style="margin-bottom: 0;">Featured</label>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-success" style="margin-top: 20px;">
                        <?php echo $editOffer ? '💾 Update Offer' : '➕ Create Offer'; ?>
                    </button>
                    <?php if ($editOffer): ?>
                        <a href="special-offers.php" class="btn" style="background: #95a5a6; color: white; margin-left: 10px;">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- Offers List -->
            <h2 style="margin-bottom: 20px;">📋 All Offers</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Title (DE)</th>
                            <th>Prices</th>
                            <th>Valid Period</th>
                            <th>Status</th>
                            <th>Views</th>
                            <th>Clicks</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($offers as $offer): ?>
                        <tr>
                            <td>
                                <img src="<?php echo htmlspecialchars($offer['image_url'] ?: '/assets/images/placeholder-food.jpg'); ?>" 
                                     alt="<?php echo htmlspecialchars($offer['title_de']); ?>" 
                                     class="offer-image">
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($offer['title_de']); ?></strong>
                                <?php if ($offer['is_featured']): ?>
                                    <br><span class="status-badge status-featured">⭐ FEATURED</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="price-info">
                                    <span class="original-price">€<?php echo number_format($offer['original_price'], 2); ?></span>
                                    <span class="offer-price">€<?php echo number_format($offer['offer_price'], 2); ?></span>
                                    <span class="discount-badge">-<?php echo $offer['discount_percentage']; ?>%</span>
                                </div>
                            </td>
                            <td>
                                <?php echo date('d.m.Y H:i', strtotime($offer['valid_from'])); ?><br>
                                <?php if ($offer['valid_until']): ?>
                                    → <?php echo date('d.m.Y H:i', strtotime($offer['valid_until'])); ?>
                                <?php else: ?>
                                    → ∞ (No expiry)
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $offer['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $offer['is_active'] ? '✓ Active' : '✗ Inactive'; ?>
                                </span>
                            </td>
                            <td><?php echo number_format($offer['view_count']); ?></td>
                            <td><?php echo number_format($offer['click_count']); ?></td>
                            <td>
                                <div class="actions">
                                    <a href="?edit=<?php echo $offer['id']; ?>" class="btn btn-edit">Edit</a>
                                    <a href="?delete=<?php echo $offer['id']; ?>" 
                                       class="btn btn-danger" 
                                       onclick="return confirm('Delete this offer?')">Delete</a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($offers)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px; color: #999;">
                                No offers yet. Create your first special offer!
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <script>
    function switchLang(lang) {
        // Update tabs
        document.querySelectorAll('.lang-tab').forEach(tab => tab.classList.remove('active'));
        event.target.classList.add('active');
        
        // Update content
        document.querySelectorAll('.lang-content').forEach(content => content.classList.remove('active'));
        document.getElementById('lang-' + lang).classList.add('active');
    }
    </script>
</body>
</html>

