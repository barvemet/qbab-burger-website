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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        die('CSRF token validation failed');
    }
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $stmt = $db->prepare("
                    INSERT INTO menu_extras (name_en, name_de, name_tr, price, category, display_order, is_active)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    sanitize($_POST['name_en']),
                    sanitize($_POST['name_de']),
                    sanitize($_POST['name_tr']),
                    floatval($_POST['price']),
                    sanitize($_POST['category']),
                    intval($_POST['display_order']),
                    isset($_POST['is_active']) ? 1 : 0
                ]);
                
                $extraId = $db->lastInsertId();
                logAdminAction('CREATE', "Added extra: {$_POST['name_de']}", 'menu_extras', $extraId);
                
                header('Location: extras.php?success=added');
                exit;
                break;

            case 'edit':
                $stmt = $db->prepare("
                    UPDATE menu_extras 
                    SET name_en=?, name_de=?, name_tr=?, price=?, category=?, display_order=?, is_active=?
                    WHERE id=?
                ");
                $stmt->execute([
                    sanitize($_POST['name_en']),
                    sanitize($_POST['name_de']),
                    sanitize($_POST['name_tr']),
                    floatval($_POST['price']),
                    sanitize($_POST['category']),
                    intval($_POST['display_order']),
                    isset($_POST['is_active']) ? 1 : 0,
                    intval($_POST['id'])
                ]);
                
                logAdminAction('UPDATE', "Updated extra: {$_POST['name_de']}", 'menu_extras', intval($_POST['id']));
                
                header('Location: extras.php?success=updated');
                exit;
                break;

            case 'delete':
                $extraId = intval($_POST['id']);
                
                // Get extra name before deleting
                $getStmt = $db->prepare("SELECT name_de FROM menu_extras WHERE id=?");
                $getStmt->execute([$extraId]);
                $extraName = $getStmt->fetchColumn();
                
                $stmt = $db->prepare("DELETE FROM menu_extras WHERE id=?");
                $stmt->execute([$extraId]);
                
                logAdminAction('DELETE', "Deleted extra: {$extraName}", 'menu_extras', $extraId);
                
                header('Location: extras.php?success=deleted');
                exit;
                break;
        }
    }
}

// Get all extras
try {
    $extras = $db->query("
        SELECT * FROM menu_extras 
        ORDER BY category, display_order, name_de
    ")->fetchAll();
} catch (Exception $e) {
    // Table might not exist yet
    $extras = [];
}

// Group by category
$salatbar = [];
$toppings = [];
foreach ($extras as $extra) {
    if ($extra['category'] === 'salatbar') {
        $salatbar[] = $extra;
    } elseif ($extra['category'] === 'toppings') {
        $toppings[] = $extra;
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Extras Verwaltung - Q-Bab Burger Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 2rem;
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
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .section h2 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 1.8rem;
        }

        .extras-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .extra-card {
            background: #f8f9fa;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            transition: all 0.3s;
        }

        .extra-card:hover {
            border-color: #667eea;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
        }

        .extra-card h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 1.3rem;
        }

        .extra-price {
            color: #e74c3c;
            font-size: 1.5rem;
            font-weight: bold;
            margin: 10px 0;
        }

        .extra-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .extra-actions {
            margin-top: 15px;
            display: flex;
            gap: 10px;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
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
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .checkbox-group input[type="checkbox"] {
            width: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1><i class="fas fa-puzzle-piece"></i> Extras Verwaltung</h1>
                <p>Salatbar und Zusätzliche Toppings</p>
            </div>
            <div>
                <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Zurück</a>
                <button onclick="openAddModal()" class="btn btn-success"><i class="fas fa-plus"></i> Neues Extra</button>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success">
            <?php
            switch ($_GET['success']) {
                case 'added': echo 'Extra erfolgreich hinzugefügt!'; break;
                case 'updated': echo 'Extra erfolgreich aktualisiert!'; break;
                case 'deleted': echo 'Extra erfolgreich gelöscht!'; break;
            }
            ?>
        </div>
        <?php endif; ?>

        <!-- Aus der Salatbar -->
        <div class="section">
            <h2><i class="fas fa-salad"></i> Aus der Salatbar (<?php echo count($salatbar); ?>)</h2>
            <div class="extras-grid">
                <?php foreach ($salatbar as $extra): ?>
                <div class="extra-card">
                    <h3><?php echo htmlspecialchars($extra['name_de']); ?></h3>
                    <div style="font-size: 0.9rem; color: #666;">
                        EN: <?php echo htmlspecialchars($extra['name_en']); ?><br>
                        TR: <?php echo htmlspecialchars($extra['name_tr']); ?>
                    </div>
                    <div class="extra-price"><?php echo formatPrice($extra['price']); ?></div>
                    <span class="extra-status <?php echo $extra['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                        <?php echo $extra['is_active'] ? 'Aktiv' : 'Inaktiv'; ?>
                    </span>
                    <div class="extra-actions">
                        <button onclick='editExtra(<?php echo json_encode($extra); ?>)' class="btn btn-primary btn-sm">
                            <i class="fas fa-edit"></i> Bearbeiten
                        </button>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Wirklich löschen?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $extra['id']; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <button type="submit" class="btn btn-danger btn-sm">
                                <i class="fas fa-trash"></i> Löschen
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Zusätzliche Toppings -->
        <div class="section">
            <h2><i class="fas fa-cheese"></i> Zusätzliche Toppings (<?php echo count($toppings); ?>)</h2>
            <div class="extras-grid">
                <?php foreach ($toppings as $extra): ?>
                <div class="extra-card">
                    <h3><?php echo htmlspecialchars($extra['name_de']); ?></h3>
                    <div style="font-size: 0.9rem; color: #666;">
                        EN: <?php echo htmlspecialchars($extra['name_en']); ?><br>
                        TR: <?php echo htmlspecialchars($extra['name_tr']); ?>
                    </div>
                    <div class="extra-price"><?php echo formatPrice($extra['price']); ?></div>
                    <span class="extra-status <?php echo $extra['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                        <?php echo $extra['is_active'] ? 'Aktiv' : 'Inaktiv'; ?>
                    </span>
                    <div class="extra-actions">
                        <button onclick='editExtra(<?php echo json_encode($extra); ?>)' class="btn btn-primary btn-sm">
                            <i class="fas fa-edit"></i> Bearbeiten
                        </button>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Wirklich löschen?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo $extra['id']; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <button type="submit" class="btn btn-danger btn-sm">
                                <i class="fas fa-trash"></i> Löschen
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Add/Edit Modal -->
    <div id="extraModal" class="modal">
        <div class="modal-content">
            <h2 id="modalTitle">Neues Extra</h2>
            <form method="POST" id="extraForm">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="extraId">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

                <div class="form-group">
                    <label>Name (Deutsch) *</label>
                    <input type="text" name="name_de" id="name_de" required>
                </div>

                <div class="form-group">
                    <label>Name (English) *</label>
                    <input type="text" name="name_en" id="name_en" required>
                </div>

                <div class="form-group">
                    <label>Name (Türkçe) *</label>
                    <input type="text" name="name_tr" id="name_tr" required>
                </div>

                <div class="form-group">
                    <label>Preis (€) *</label>
                    <input type="number" step="0.01" name="price" id="price" required>
                </div>

                <div class="form-group">
                    <label>Kategorie *</label>
                    <select name="category" id="category" required>
                        <option value="salatbar">Aus der Salatbar</option>
                        <option value="toppings">Zusätzliche Toppings</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Reihenfolge</label>
                    <input type="number" name="display_order" id="display_order" value="0">
                </div>

                <div class="form-group checkbox-group">
                    <input type="checkbox" name="is_active" id="is_active" checked>
                    <label for="is_active">Aktiv</label>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn btn-success">Speichern</button>
                    <button type="button" onclick="closeModal()" class="btn btn-secondary">Abbrechen</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Neues Extra';
            document.getElementById('formAction').value = 'add';
            document.getElementById('extraForm').reset();
            document.getElementById('extraModal').classList.add('active');
        }

        function editExtra(extra) {
            document.getElementById('modalTitle').textContent = 'Extra bearbeiten';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('extraId').value = extra.id;
            document.getElementById('name_de').value = extra.name_de;
            document.getElementById('name_en').value = extra.name_en;
            document.getElementById('name_tr').value = extra.name_tr;
            document.getElementById('price').value = extra.price;
            document.getElementById('category').value = extra.category;
            document.getElementById('display_order').value = extra.display_order;
            document.getElementById('is_active').checked = extra.is_active == 1;
            document.getElementById('extraModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('extraModal').classList.remove('active');
        }

        // Close modal on outside click
        document.getElementById('extraModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>

