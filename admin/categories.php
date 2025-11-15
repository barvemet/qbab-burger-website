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

    // Check if category has menu items
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM menu_items WHERE category_id = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch();

    if ($result['count'] > 0) {
        $error = 'Kategorie kann nicht gelöscht werden! Es gibt noch ' . $result['count'] . ' Produkt(e) in dieser Kategorie.';
    } else {
        try {
            $stmt = $db->prepare("DELETE FROM menu_categories WHERE id = ?");
            $stmt->execute([$id]);
            $message = 'Kategorie erfolgreich gelöscht!';
        } catch (Exception $e) {
            $error = 'Fehler beim Löschen: ' . $e->getMessage();
        }
    }
}

// Handle form submission (add/edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $name_de = trim($_POST['name_de']);
    $name_en = trim($_POST['name_en']);
    $name_tr = trim($_POST['name_tr']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $display_order = (int)$_POST['display_order'];

    try {
        if ($_POST['action'] === 'add') {
            $stmt = $db->prepare("
                INSERT INTO menu_categories
                (name_de, name_en, name_tr, is_active, display_order)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $name_de, $name_en, $name_tr, $is_active, $display_order
            ]);
            $message = 'Kategorie erfolgreich hinzugefügt!';
        } elseif ($_POST['action'] === 'edit') {
            $stmt = $db->prepare("
                UPDATE menu_categories SET
                name_de = ?, name_en = ?, name_tr = ?,
                is_active = ?, display_order = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $name_de, $name_en, $name_tr, $is_active, $display_order, $id
            ]);
            $message = 'Kategorie erfolgreich aktualisiert!';
        }
    } catch (Exception $e) {
        $error = 'Fehler: ' . $e->getMessage();
    }
}

// Get all categories with item counts
$stmt = $db->query("
    SELECT mc.*, COUNT(mi.id) as item_count
    FROM menu_categories mc
    LEFT JOIN menu_items mi ON mc.id = mi.category_id
    GROUP BY mc.id
    ORDER BY mc.display_order
");
$categories = $stmt->fetchAll();

// Get category for editing
$editCategory = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM menu_categories WHERE id = ?");
    $stmt->execute([$editId]);
    $editCategory = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kategorien Verwaltung - Q-Bab Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .header h1 {
            color: #e10000;
            font-size: 28px;
        }

        .header-actions {
            display: flex;
            gap: 15px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #e10000;
            color: white;
        }

        .btn-primary:hover {
            background: #c20000;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-small {
            padding: 6px 12px;
            font-size: 12px;
        }

        .btn-edit {
            background: #007bff;
            color: white;
        }

        .btn-edit:hover {
            background: #0056b3;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .btn-delete:hover {
            background: #c82333;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .card h2 {
            color: #333;
            margin-bottom: 25px;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 600;
            font-size: 14px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #e10000;
        }

        .form-group-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
        }

        .checkbox-group input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .categories-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
        }

        .categories-table thead th {
            background: #f8f9fa;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            color: #555;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .categories-table tbody tr {
            background: white;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .categories-table tbody tr:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .categories-table tbody td {
            padding: 18px 15px;
            border-top: 1px solid #f0f0f0;
            border-bottom: 1px solid #f0f0f0;
        }

        .categories-table tbody td:first-child {
            border-left: 1px solid #f0f0f0;
            border-radius: 8px 0 0 8px;
        }

        .categories-table tbody td:last-child {
            border-right: 1px solid #f0f0f0;
            border-radius: 0 8px 8px 0;
        }

        .category-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 4px;
        }

        .category-translations {
            font-size: 12px;
            color: #999;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-active {
            background: #d4edda;
            color: #155724;
        }

        .badge-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-count {
            background: #e3f2fd;
            color: #1976d2;
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        @media (max-width: 1200px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .form-group-row {
                grid-template-columns: 1fr;
            }

            .categories-table {
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>📁 Kategorien Verwaltung</h1>
            <div class="header-actions">
                <a href="menu-items.php" class="btn btn-secondary">← Zurück zu Produkten</a>
                <a href="index.php" class="btn btn-secondary">🏠 Dashboard</a>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
        <div class="alert alert-success">
            ✓ <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-error">
            ✗ <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Categories List -->
            <div class="card">
                <h2>📋 Alle Kategorien (<?php echo count($categories); ?>)</h2>

                <?php if (empty($categories)): ?>
                <p style="text-align: center; color: #999; padding: 40px 0;">
                    Keine Kategorien vorhanden. Fügen Sie die erste Kategorie hinzu!
                </p>
                <?php else: ?>
                <table class="categories-table">
                    <thead>
                        <tr>
                            <th>Kategorie</th>
                            <th>Status</th>
                            <th>Produkte</th>
                            <th>Reihenfolge</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                        <tr>
                            <td>
                                <div class="category-name"><?php echo htmlspecialchars($category['name_de']); ?></div>
                                <div class="category-translations">
                                    EN: <?php echo htmlspecialchars($category['name_en']); ?> |
                                    TR: <?php echo htmlspecialchars($category['name_tr']); ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge <?php echo $category['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                                    <?php echo $category['is_active'] ? 'Aktiv' : 'Inaktiv'; ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge-count"><?php echo $category['item_count']; ?> Produkt(e)</span>
                            </td>
                            <td><?php echo $category['display_order']; ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="?edit=<?php echo $category['id']; ?>" class="btn btn-edit btn-small">✏️ Bearbeiten</a>
                                    <?php if ($category['item_count'] == 0): ?>
                                    <a href="?delete=<?php echo $category['id']; ?>"
                                       class="btn btn-delete btn-small"
                                       onclick="return confirm('Kategorie wirklich löschen?')">🗑️ Löschen</a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>

            <!-- Add/Edit Form -->
            <div class="card">
                <h2><?php echo $editCategory ? '✏️ Kategorie Bearbeiten' : '➕ Neue Kategorie'; ?></h2>

                <form method="POST">
                    <input type="hidden" name="action" value="<?php echo $editCategory ? 'edit' : 'add'; ?>">
                    <?php if ($editCategory): ?>
                    <input type="hidden" name="id" value="<?php echo $editCategory['id']; ?>">
                    <?php endif; ?>

                    <div class="form-group">
                        <label>🇩🇪 Name (Deutsch) *</label>
                        <input type="text" name="name_de" required
                               value="<?php echo $editCategory ? htmlspecialchars($editCategory['name_de']) : ''; ?>"
                               placeholder="z.B. Burger">
                    </div>

                    <div class="form-group">
                        <label>🇬🇧 Name (English) *</label>
                        <input type="text" name="name_en" required
                               value="<?php echo $editCategory ? htmlspecialchars($editCategory['name_en']) : ''; ?>"
                               placeholder="e.g. Burgers">
                    </div>

                    <div class="form-group">
                        <label>🇹🇷 Name (Türkçe) *</label>
                        <input type="text" name="name_tr" required
                               value="<?php echo $editCategory ? htmlspecialchars($editCategory['name_tr']) : ''; ?>"
                               placeholder="örn. Burgerler">
                    </div>

                    <div class="form-group-row">
                        <div class="form-group">
                            <label>📊 Reihenfolge</label>
                            <input type="number" name="display_order" min="0"
                                   value="<?php echo $editCategory ? $editCategory['display_order'] : count($categories); ?>">
                        </div>

                        <div class="form-group">
                            <label>Status</label>
                            <div class="checkbox-group">
                                <input type="checkbox" name="is_active" id="is_active"
                                       <?php echo (!$editCategory || $editCategory['is_active']) ? 'checked' : ''; ?>>
                                <label for="is_active" style="margin: 0;">Aktiv</label>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 10px;">
                        <?php echo $editCategory ? '💾 Kategorie Aktualisieren' : '➕ Kategorie Hinzufügen'; ?>
                    </button>

                    <?php if ($editCategory): ?>
                    <a href="categories.php" class="btn btn-secondary" style="width: 100%; margin-top: 10px; text-align: center;">
                        ✖ Abbrechen
                    </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

</body>
</html>
