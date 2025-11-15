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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'add':
                    $stmt = $db->prepare("
                        INSERT INTO menu_items 
                        (category_id, name_en, name_de, name_tr, description_en, description_de, description_tr, 
                         price, is_popular, is_active, display_order)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)
                    ");
                    $stmt->execute([
                        $_POST['category_id'],
                        $_POST['name_en'],
                        $_POST['name_de'],
                        $_POST['name_tr'],
                        $_POST['description_en'],
                        $_POST['description_de'],
                        $_POST['description_tr'],
                        $_POST['price'],
                        isset($_POST['is_popular']) ? 1 : 0,
                        $_POST['display_order'] ?? 0
                    ]);
                    $message = "Menü-Element erfolgreich hinzugefügt!";
                    break;

                case 'update':
                    $stmt = $db->prepare("
                        UPDATE menu_items SET
                        category_id = ?, name_en = ?, name_de = ?, name_tr = ?,
                        description_en = ?, description_de = ?, description_tr = ?,
                        price = ?, is_popular = ?, display_order = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([
                        $_POST['category_id'],
                        $_POST['name_en'],
                        $_POST['name_de'],
                        $_POST['name_tr'],
                        $_POST['description_en'],
                        $_POST['description_de'],
                        $_POST['description_tr'],
                        $_POST['price'],
                        isset($_POST['is_popular']) ? 1 : 0,
                        $_POST['display_order'] ?? 0,
                        $_POST['item_id']
                    ]);
                    $message = "Menü-Element erfolgreich aktualisiert!";
                    break;

                case 'delete':
                    $stmt = $db->prepare("DELETE FROM menu_items WHERE id = ?");
                    $stmt->execute([$_POST['item_id']]);
                    $message = "Menü-Element gelöscht!";
                    break;

                case 'toggle_active':
                    $stmt = $db->prepare("UPDATE menu_items SET is_active = NOT is_active WHERE id = ?");
                    $stmt->execute([$_POST['item_id']]);
                    $message = "Status aktualisiert!";
                    break;
            }
        } catch (Exception $e) {
            $error = "Fehler: " . $e->getMessage();
        }
    }
}

// Get all menu items
$menu_items = $db->query("
    SELECT m.*, c.name_de as category_name 
    FROM menu_items m 
    LEFT JOIN menu_categories c ON m.category_id = c.id 
    ORDER BY m.display_order, m.id
")->fetchAll();

// Get categories for dropdown
$categories = $db->query("SELECT * FROM menu_categories ORDER BY display_order")->fetchAll();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menü-Liste Verwaltung - Q-Bab Admin</title>
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
        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
        }
        .card h2 {
            margin-bottom: 20px;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
        }
        .badge {
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        .badge-secondary {
            background: #e2e3e5;
            color: #383d41;
        }
        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }
        .item-actions {
            display: flex;
            gap: 5px;
        }
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }
        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .modal-content {
            background: white;
            border-radius: 10px;
            padding: 30px;
            max-width: 800px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>🍔 Menü-Liste Verwaltung</h1>
            <div>
                <a href="index.php" class="btn btn-secondary">← Zurück zum Dashboard</a>
                <a href="logout.php" class="btn btn-danger">Abmelden</a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2>Menü-Elemente (<?php echo count($menu_items); ?>)</h2>
                <button onclick="openAddModal()" class="btn btn-success">➕ Neues Element Hinzufügen</button>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Reihenfolge</th>
                        <th>Name (DE)</th>
                        <th>Kategorie</th>
                        <th>Preis</th>
                        <th>Status</th>
                        <th>Beliebt</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($menu_items as $item): ?>
                    <tr>
                        <td><strong><?php echo $item['display_order']; ?></strong></td>
                        <td><strong><?php echo htmlspecialchars($item['name_de']); ?></strong></td>
                        <td><?php echo htmlspecialchars($item['category_name'] ?? 'N/A'); ?></td>
                        <td><strong>€<?php echo number_format($item['price'], 2, ',', '.'); ?></strong></td>
                        <td>
                            <?php if ($item['is_active']): ?>
                                <span class="badge badge-success">Aktiv</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Inaktiv</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($item['is_popular']): ?>
                                <span class="badge badge-danger">⭐ Beliebt</span>
                            <?php endif; ?>
                        </td>
                        <td class="item-actions">
                            <button onclick='editItem(<?php echo json_encode($item); ?>)' class="btn btn-primary btn-sm">Bearbeiten</button>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="toggle_active">
                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="btn btn-secondary btn-sm">
                                    <?php echo $item['is_active'] ? 'Deaktivieren' : 'Aktivieren'; ?>
                                </button>
                            </form>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Wirklich löschen?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Löschen</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($menu_items)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px; color: #999;">
                            Keine Menü-Elemente vorhanden. Fügen Sie das erste Element hinzu!
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div id="itemModal" class="modal">
        <div class="modal-content">
            <h2 id="modalTitle">Menü-Element Hinzufügen</h2>
            <form method="POST" id="itemForm">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="item_id" id="itemId">

                <div class="form-group">
                    <label>Kategorie *</label>
                    <select name="category_id" id="categoryId" required>
                        <option value="">-- Kategorie auswählen --</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name_de']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Name (Deutsch) *</label>
                        <input type="text" name="name_de" id="nameDe" required>
                    </div>
                    <div class="form-group">
                        <label>Name (English) *</label>
                        <input type="text" name="name_en" id="nameEn" required>
                    </div>
                    <div class="form-group">
                        <label>Name (Türkçe) *</label>
                        <input type="text" name="name_tr" id="nameTr" required>
                    </div>
                </div>

                <div class="form-group">
                    <label>Beschreibung (Deutsch) *</label>
                    <textarea name="description_de" id="descriptionDe" required></textarea>
                </div>

                <div class="form-group">
                    <label>Description (English) *</label>
                    <textarea name="description_en" id="descriptionEn" required></textarea>
                </div>

                <div class="form-group">
                    <label>Açıklama (Türkçe) *</label>
                    <textarea name="description_tr" id="descriptionTr" required></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Preis (€) *</label>
                        <input type="number" step="0.01" name="price" id="price" required>
                    </div>
                    <div class="form-group">
                        <label>Reihenfolge</label>
                        <input type="number" name="display_order" id="displayOrder" value="0">
                    </div>
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 10px; margin-top: 30px;">
                            <input type="checkbox" name="is_popular" id="isPopular" style="width: auto;">
                            Als "Beliebt" markieren
                        </label>
                    </div>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 30px;">
                    <button type="submit" class="btn btn-success">Speichern</button>
                    <button type="button" onclick="closeModal()" class="btn btn-secondary">Abbrechen</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Menü-Element Hinzufügen';
            document.getElementById('formAction').value = 'add';
            document.getElementById('itemForm').reset();
            document.getElementById('itemModal').classList.add('active');
        }

        function editItem(item) {
            document.getElementById('modalTitle').textContent = 'Menü-Element Bearbeiten';
            document.getElementById('formAction').value = 'update';
            document.getElementById('itemId').value = item.id;
            document.getElementById('categoryId').value = item.category_id;
            document.getElementById('nameDe').value = item.name_de;
            document.getElementById('nameEn').value = item.name_en;
            document.getElementById('nameTr').value = item.name_tr;
            document.getElementById('descriptionDe').value = item.description_de;
            document.getElementById('descriptionEn').value = item.description_en;
            document.getElementById('descriptionTr').value = item.description_tr;
            document.getElementById('price').value = item.price;
            document.getElementById('displayOrder').value = item.display_order;
            document.getElementById('isPopular').checked = item.is_popular == 1;
            document.getElementById('itemModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('itemModal').classList.remove('active');
        }

        // Close modal on outside click
        document.getElementById('itemModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>
