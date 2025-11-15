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

// Define upload directory
define('UPLOAD_DIR', dirname(__DIR__) . '/assets/uploads/menu/');
define('UPLOAD_URL', ASSETS_URL . '/uploads/menu/');

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        // Get item to delete image file
        $stmt = $db->prepare("SELECT image FROM menu_items WHERE id = ?");
        $stmt->execute([$id]);
        $item = $stmt->fetch();
        
        // Delete from database
        $stmt = $db->prepare("DELETE FROM menu_items WHERE id = ?");
        $stmt->execute([$id]);
        
        // Delete image file if exists
        if ($item && $item['image'] && file_exists(UPLOAD_DIR . $item['image'])) {
            unlink(UPLOAD_DIR . $item['image']);
        }
        
        $message = 'Produkt erfolgreich gelöscht!';
    } catch (Exception $e) {
        $error = 'Fehler beim Löschen: ' . $e->getMessage();
    }
}

// Handle form submission (add/edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $category_id = (int)$_POST['category_id'];
    $name_de = trim($_POST['name_de']);
    $name_en = trim($_POST['name_en']);
    $name_tr = trim($_POST['name_tr']);
    $description_de = trim($_POST['description_de']);
    $description_en = trim($_POST['description_en']);
    $description_tr = trim($_POST['description_tr']);
    $price = floatval(str_replace(',', '.', $_POST['price']));
    $discount_percent = isset($_POST['discount_percent']) ? (int)$_POST['discount_percent'] : 0;
    $discount_percent = max(0, min(100, $discount_percent)); // Ensure 0-100
    $is_vegetarian = isset($_POST['is_vegetarian']) ? 1 : 0;
    $is_vegan = isset($_POST['is_vegan']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $display_order = (int)$_POST['display_order'];
    
    // Handle image upload
    $imageName = null;
    $deleteOldImage = false;
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!in_array($_FILES['image']['type'], $allowedTypes)) {
            $error = 'Ungültiger Dateityp. Nur JPG, PNG, GIF und WebP sind erlaubt.';
        } elseif ($_FILES['image']['size'] > $maxSize) {
            $error = 'Datei zu groß. Maximum 5MB erlaubt.';
        } else {
            // Generate unique filename
            $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $imageName = uniqid('menu_') . '.' . $extension;
            $uploadPath = UPLOAD_DIR . $imageName;
            
            if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadPath)) {
                $error = 'Fehler beim Hochladen der Datei.';
                $imageName = null;
            } else {
                $deleteOldImage = true;
            }
        }
    } elseif (isset($_POST['remove_image']) && $_POST['remove_image'] === '1') {
        // User wants to remove the image
        $imageName = '';
        $deleteOldImage = true;
    }

    try {
        if ($_POST['action'] === 'add') {
            $stmt = $db->prepare("
                INSERT INTO menu_items
                (category_id, name_de, name_en, name_tr, description_de, description_en, description_tr,
                 price, discount_percent, is_vegetarian, is_vegan, is_active, display_order, image)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $category_id, $name_de, $name_en, $name_tr,
                $description_de, $description_en, $description_tr,
                $price, $discount_percent, $is_vegetarian, $is_vegan, $is_active, $display_order, $imageName
            ]);
            $message = 'Produkt erfolgreich hinzugefügt!';
        } elseif ($_POST['action'] === 'edit') {
            // Get old image for deletion if new image uploaded
            if ($deleteOldImage) {
                $stmt = $db->prepare("SELECT image FROM menu_items WHERE id = ?");
                $stmt->execute([$id]);
                $oldItem = $stmt->fetch();
                if ($oldItem && $oldItem['image'] && file_exists(UPLOAD_DIR . $oldItem['image'])) {
                    unlink(UPLOAD_DIR . $oldItem['image']);
                }
            }
            
            // Update query with or without image
            if ($imageName !== null) {
                $stmt = $db->prepare("
                    UPDATE menu_items SET
                    category_id = ?, name_de = ?, name_en = ?, name_tr = ?,
                    description_de = ?, description_en = ?, description_tr = ?,
                    price = ?, discount_percent = ?, is_vegetarian = ?, is_vegan = ?, is_active = ?, display_order = ?, image = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $category_id, $name_de, $name_en, $name_tr,
                    $description_de, $description_en, $description_tr,
                    $price, $discount_percent, $is_vegetarian, $is_vegan, $is_active, $display_order, $imageName, $id
                ]);
            } else {
                $stmt = $db->prepare("
                    UPDATE menu_items SET
                    category_id = ?, name_de = ?, name_en = ?, name_tr = ?,
                    description_de = ?, description_en = ?, description_tr = ?,
                    price = ?, discount_percent = ?, is_vegetarian = ?, is_vegan = ?, is_active = ?, display_order = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $category_id, $name_de, $name_en, $name_tr,
                    $description_de, $description_en, $description_tr,
                    $price, $discount_percent, $is_vegetarian, $is_vegan, $is_active, $display_order, $id
                ]);
            }
            $message = 'Produkt erfolgreich aktualisiert!';
        }
    } catch (Exception $e) {
        $error = 'Fehler: ' . $e->getMessage();
    }
}

// Get all categories
$categories = $db->query("SELECT * FROM menu_categories ORDER BY display_order")->fetchAll();

// Get all menu items
$stmt = $db->query("
    SELECT mi.*, mc.name_de as category_name
    FROM menu_items mi
    LEFT JOIN menu_categories mc ON mi.category_id = mc.id
    ORDER BY mc.display_order, mi.display_order
");
$menuItems = $stmt->fetchAll();

// Get item for editing
$editItem = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM menu_items WHERE id = ?");
    $stmt->execute([$editId]);
    $editItem = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menü-Produkte verwalten - Admin</title>
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
            max-width: 1400px;
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
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 30px;
        }
        .form-card, .list-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-card h2, .list-card h2 {
            margin-bottom: 20px;
            color: #333;
        }
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            grid-column: 1 / -1;
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
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        .checkbox-group {
            display: flex;
            gap: 20px;
            margin-top: 10px;
        }
        .checkbox-group label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: normal;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-success {
            background: #28a745;
            color: white;
            font-size: 14px;
            padding: 8px 16px;
        }
        .btn-warning {
            background: #ffc107;
            color: #333;
            font-size: 14px;
            padding: 8px 16px;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
            font-size: 14px;
            padding: 8px 16px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
        }
        .items-table th, .items-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        .items-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        .items-table tr:hover {
            background: #f8f9fa;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-active {
            background: #d4edda;
            color: #155724;
        }
        .badge-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        .badge-vegetarian {
            background: #d1ecf1;
            color: #0c5460;
        }
        .badge-vegan {
            background: #d4edda;
            color: #155724;
        }
        .actions {
            display: flex;
            gap: 8px;
        }
        .image-preview {
            margin-top: 10px;
            max-width: 200px;
            border-radius: 8px;
            border: 2px solid #e0e0e0;
        }
        .image-current {
            margin-top: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .image-current img {
            max-width: 150px;
            border-radius: 4px;
            display: block;
            margin-bottom: 10px;
        }
        .remove-image-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        @media (max-width: 1200px) {
            .container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>🍔 Menü-Produkte verwalten</h1>
            <div class="nav-links">
                <a href="categories.php" style="background: #e10000;">📁 Kategorien Verwalten</a>
                <a href="index.php">← Dashboard</a>
                <a href="popular-items.php">⭐ Beliebte Produkte</a>
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

        <!-- Add/Edit Form -->
        <div class="form-card">
            <h2><?php echo $editItem ? 'Produkt bearbeiten' : 'Neues Produkt hinzufügen'; ?></h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="<?php echo $editItem ? 'edit' : 'add'; ?>">
                <?php if ($editItem): ?>
                <input type="hidden" name="id" value="<?php echo $editItem['id']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="category_id">Kategorie *</label>
                    <select name="category_id" id="category_id" required>
                        <option value="">Bitte wählen...</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"
                                <?php echo ($editItem && $editItem['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name_de']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="name_de">Name (Deutsch) *</label>
                    <input type="text" id="name_de" name="name_de"
                           value="<?php echo $editItem ? htmlspecialchars($editItem['name_de']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="name_en">Name (English) *</label>
                    <input type="text" id="name_en" name="name_en"
                           value="<?php echo $editItem ? htmlspecialchars($editItem['name_en']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="name_tr">Name (Türkçe) *</label>
                    <input type="text" id="name_tr" name="name_tr"
                           value="<?php echo $editItem ? htmlspecialchars($editItem['name_tr']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="description_de">Beschreibung (Deutsch)</label>
                    <textarea id="description_de" name="description_de"><?php echo $editItem ? htmlspecialchars($editItem['description_de']) : ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label for="description_en">Beschreibung (English)</label>
                    <textarea id="description_en" name="description_en"><?php echo $editItem ? htmlspecialchars($editItem['description_en']) : ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label for="description_tr">Beschreibung (Türkçe)</label>
                    <textarea id="description_tr" name="description_tr"><?php echo $editItem ? htmlspecialchars($editItem['description_tr']) : ''; ?></textarea>
                </div>

                <div class="form-group">
                    <label for="price">Preis (€) *</label>
                    <input type="number" step="0.01" id="price" name="price"
                           value="<?php echo $editItem ? $editItem['price'] : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="discount_percent">Rabatt (%) - Optional</label>
                    <input type="number" min="0" max="100" id="discount_percent" name="discount_percent"
                           value="<?php echo $editItem && isset($editItem['discount_percent']) ? $editItem['discount_percent'] : '0'; ?>"
                           placeholder="z.B. 20 für 20% Rabatt">
                    <small style="color: #666; font-size: 12px; display: block; margin-top: 5px;">
                        Geben Sie 0 ein für keinen Rabatt, oder einen Wert zwischen 1-100 für Rabatt in %.
                    </small>
                </div>

                <div class="form-group">
                    <label for="display_order">Reihenfolge</label>
                    <input type="number" id="display_order" name="display_order"
                           value="<?php echo $editItem ? $editItem['display_order'] : '0'; ?>">
                </div>

                <div class="form-group">
                    <label for="image">Produktbild (Optional)</label>
                    <?php if ($editItem && $editItem['image']): ?>
                    <div class="image-current">
                        <img src="<?php echo ASSETS_URL . '/uploads/menu/' . htmlspecialchars($editItem['image']); ?>" 
                             alt="Aktuelles Bild">
                        <label style="display: block; margin-top: 10px;">
                            <input type="checkbox" name="remove_image" value="1" id="remove_image_check">
                            <span style="color: #dc3545; font-weight: normal;">Bild entfernen</span>
                        </label>
                    </div>
                    <?php endif; ?>
                    <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/gif,image/webp">
                    <small style="color: #666; font-size: 12px; display: block; margin-top: 5px;">
                        Erlaubte Formate: JPG, PNG, GIF, WebP (Max. 5MB)
                    </small>
                </div>

                <div class="form-group">
                    <div class="checkbox-group">
                        <label>
                            <input type="checkbox" name="is_vegetarian" value="1"
                                   <?php echo ($editItem && $editItem['is_vegetarian']) ? 'checked' : ''; ?>>
                            Vegetarisch
                        </label>
                        <label>
                            <input type="checkbox" name="is_vegan" value="1"
                                   <?php echo ($editItem && $editItem['is_vegan']) ? 'checked' : ''; ?>>
                            Vegan
                        </label>
                        <label>
                            <input type="checkbox" name="is_active" value="1"
                                   <?php echo (!$editItem || $editItem['is_active']) ? 'checked' : ''; ?>>
                            Aktiv
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <?php echo $editItem ? 'Aktualisieren' : 'Hinzufügen'; ?>
                </button>
                <?php if ($editItem): ?>
                <a href="menu-items.php" class="btn" style="background: #6c757d; color: white; margin-left: 10px;">Abbrechen</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Items List -->
        <div class="list-card">
            <h2>Alle Produkte (<?php echo count($menuItems); ?>)</h2>
            <div style="overflow-x: auto;">
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Bild</th>
                            <th>Name</th>
                            <th>Kategorie</th>
                            <th>Preis</th>
                            <th>Status</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($menuItems as $item): ?>
                        <tr>
                            <td>
                                <?php if ($item['image']): ?>
                                <img src="<?php echo ASSETS_URL . '/uploads/menu/' . htmlspecialchars($item['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name_de']); ?>"
                                     style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                <?php else: ?>
                                <span style="color: #999; font-size: 12px;">Kein Bild</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($item['name_de']); ?></strong>
                                <?php if ($item['is_vegetarian']): ?>
                                <span class="badge badge-vegetarian">V</span>
                                <?php endif; ?>
                                <?php if ($item['is_vegan']): ?>
                                <span class="badge badge-vegan">VG</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                            <td><?php echo formatPrice($item['price']); ?></td>
                            <td>
                                <span class="badge <?php echo $item['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                                    <?php echo $item['is_active'] ? 'Aktiv' : 'Inaktiv'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="actions">
                                    <a href="?edit=<?php echo $item['id']; ?>" class="btn btn-warning">Bearbeiten</a>
                                    <a href="?delete=<?php echo $item['id']; ?>"
                                       class="btn btn-danger"
                                       onclick="return confirm('Sind Sie sicher, dass Sie dieses Produkt löschen möchten?')">
                                        Löschen
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
