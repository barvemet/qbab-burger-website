<?php
/**
 * Q-Bab Burger - Admin Newsletter Management
 */

// Start session
session_start();

// Check if logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

define('ALLOW_INCLUDE', true);
require_once __DIR__ . '/../includes/config.php';

// Get database connection
$db = getDBConnection();

// Handle actions
$action_message = '';
$action_type = '';

// Delete subscriber
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $stmt = $db->prepare("DELETE FROM newsletter_subscribers WHERE id = ?");
        $stmt->execute([$id]);
        $action_message = 'Abonnent erfolgreich gelöscht.';
        $action_type = 'success';
    } catch (PDOException $e) {
        $action_message = 'Fehler beim Löschen: ' . $e->getMessage();
        $action_type = 'error';
    }
}

// Toggle active status
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    try {
        $stmt = $db->prepare("UPDATE newsletter_subscribers SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$id]);
        $action_message = 'Status erfolgreich geändert.';
        $action_type = 'success';
    } catch (PDOException $e) {
        $action_message = 'Fehler beim Ändern: ' . $e->getMessage();
        $action_type = 'error';
    }
}

// Export to CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    try {
        $stmt = $db->query("SELECT email, subscribed_at, is_active FROM newsletter_subscribers ORDER BY subscribed_at DESC");
        $subscribers = $stmt->fetchAll();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=newsletter-subscribers-' . date('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Email', 'Subscribed At', 'Status']);

        foreach ($subscribers as $sub) {
            fputcsv($output, [
                $sub['email'],
                $sub['subscribed_at'],
                $sub['is_active'] ? 'Active' : 'Inactive'
            ]);
        }

        fclose($output);
        exit;
    } catch (PDOException $e) {
        $action_message = 'Fehler beim Export: ' . $e->getMessage();
        $action_type = 'error';
    }
}

// Check if table exists
$tableExists = false;
try {
    $db->query("SELECT 1 FROM newsletter_subscribers LIMIT 1");
    $tableExists = true;
} catch (PDOException $e) {
    // Table doesn't exist
}

// Get all subscribers
try {
    if ($tableExists) {
        $stmt = $db->query("SELECT * FROM newsletter_subscribers ORDER BY subscribed_at DESC");
        $subscribers = $stmt->fetchAll();

        // Get statistics
        $stmt = $db->query("SELECT COUNT(*) as total, COALESCE(SUM(is_active), 0) as active FROM newsletter_subscribers");
        $stats = $stmt->fetch();
    } else {
        $subscribers = [];
        $stats = ['total' => 0, 'active' => 0];
    }
} catch (PDOException $e) {
    $subscribers = [];
    $stats = ['total' => 0, 'active' => 0];
}

$page_title = 'Newsletter Management';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Q-Bab Burger Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }

        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .admin-header {
            background: #1a1a1a;
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .admin-header h1 {
            font-size: 2rem;
            color: #f9a825;
        }

        .admin-nav {
            display: flex;
            gap: 15px;
        }

        .nav-btn {
            padding: 10px 20px;
            background: #f9a825;
            color: #1a1a1a;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            transition: all 0.3s;
        }

        .nav-btn:hover {
            background: #e10000;
            color: white;
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
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .stat-card h3 {
            color: #666;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .stat-card .number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #f9a825;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .alert.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .action-bar {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.95rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #f9a825;
            color: #1a1a1a;
        }

        .btn-primary:hover {
            background: #e10000;
            color: white;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        .table-container {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #1a1a1a;
            color: white;
        }

        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
        }

        tbody tr:hover {
            background: #f9f9f9;
        }

        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
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

        .actions {
            display: flex;
            gap: 10px;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 0.85rem;
        }

        .compose-section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-top: 30px;
        }

        .compose-section h2 {
            margin-bottom: 20px;
            color: #1a1a1a;
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

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #f9a825;
        }

        .form-group textarea {
            min-height: 200px;
            resize: vertical;
        }

        .email-preview {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin-top: 10px;
            font-size: 0.9rem;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Header -->
        <div class="admin-header">
            <h1>📧 Newsletter Verwaltung</h1>
            <div class="admin-nav">
                <a href="index.php" class="nav-btn">Dashboard</a>
                <a href="logout.php" class="nav-btn">Abmelden</a>
            </div>
        </div>

        <!-- Action Message -->
        <?php if ($action_message): ?>
        <div class="alert <?php echo $action_type; ?>">
            <?php echo htmlspecialchars($action_message); ?>
        </div>
        <?php endif; ?>

        <!-- Database Warning -->
        <?php if (!$tableExists): ?>
        <div class="alert error">
            <strong>⚠️ Veritabanı Tablosu Bulunamadı!</strong><br>
            Newsletter sistemi çalışabilmesi için veritabanı tablosunu oluşturmanız gerekiyor.<br><br>
            <strong>Adım 1:</strong> PhpMyAdmin'e gidin<br>
            <strong>Adım 2:</strong> Aşağıdaki SQL kodunu çalıştırın:<br><br>
            <code style="background: #f5f5f5; padding: 10px; display: block; margin: 10px 0; border-radius: 5px; font-size: 0.9rem;">
                CREATE TABLE IF NOT EXISTS newsletter_subscribers (<br>
                &nbsp;&nbsp;&nbsp;&nbsp;id INT AUTO_INCREMENT PRIMARY KEY,<br>
                &nbsp;&nbsp;&nbsp;&nbsp;email VARCHAR(255) NOT NULL UNIQUE,<br>
                &nbsp;&nbsp;&nbsp;&nbsp;subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,<br>
                &nbsp;&nbsp;&nbsp;&nbsp;ip_address VARCHAR(45),<br>
                &nbsp;&nbsp;&nbsp;&nbsp;user_agent TEXT,<br>
                &nbsp;&nbsp;&nbsp;&nbsp;is_active TINYINT(1) DEFAULT 1,<br>
                &nbsp;&nbsp;&nbsp;&nbsp;INDEX idx_email (email),<br>
                &nbsp;&nbsp;&nbsp;&nbsp;INDEX idx_subscribed_at (subscribed_at)<br>
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            </code>
            <br>
            Veya <code>database/newsletter_table.sql</code> dosyasını import edin.
        </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Gesamt Abonnenten</h3>
                <div class="number"><?php echo number_format($stats['total'], 0, ',', '.'); ?></div>
            </div>
            <div class="stat-card">
                <h3>Aktive Abonnenten</h3>
                <div class="number"><?php echo number_format($stats['active'], 0, ',', '.'); ?></div>
            </div>
            <div class="stat-card">
                <h3>Inaktiv</h3>
                <div class="number"><?php echo number_format($stats['total'] - $stats['active'], 0, ',', '.'); ?></div>
            </div>
        </div>

        <!-- Action Bar -->
        <div class="action-bar">
            <h3>Abonnenten Liste</h3>
            <div>
                <a href="?export=csv" class="btn btn-success">📥 CSV Exportieren</a>
                <button onclick="document.getElementById('composeSection').scrollIntoView({behavior: 'smooth'})" class="btn btn-primary">✉️ E-Mail Verfassen</button>
            </div>
        </div>

        <!-- Subscribers Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>E-Mail</th>
                        <th>Angemeldet am</th>
                        <th>IP-Adresse</th>
                        <th>Status</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($subscribers)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px; color: #999;">
                            Noch keine Abonnenten vorhanden.
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($subscribers as $sub): ?>
                        <tr>
                            <td><?php echo $sub['id']; ?></td>
                            <td><?php echo htmlspecialchars($sub['email']); ?></td>
                            <td><?php echo date('d.m.Y H:i', strtotime($sub['subscribed_at'])); ?></td>
                            <td><?php echo htmlspecialchars($sub['ip_address']); ?></td>
                            <td>
                                <span class="badge <?php echo $sub['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                                    <?php echo $sub['is_active'] ? 'Aktiv' : 'Inaktiv'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="actions">
                                    <a href="?toggle=<?php echo $sub['id']; ?>" class="btn btn-sm btn-primary">
                                        <?php echo $sub['is_active'] ? 'Deaktivieren' : 'Aktivieren'; ?>
                                    </a>
                                    <a href="?delete=<?php echo $sub['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Sind Sie sicher?')">
                                        Löschen
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Compose Email Section -->
        <div class="compose-section" id="composeSection">
            <h2>✉️ Newsletter E-Mail Verfassen</h2>
            <form id="composeForm">
                <div class="form-group">
                    <label for="emailSubject">Betreff</label>
                    <input type="text" id="emailSubject" name="subject" placeholder="E-Mail Betreff eingeben" required>
                </div>

                <div class="form-group">
                    <label for="emailMessage">Nachricht</label>
                    <textarea id="emailMessage" name="message" placeholder="Ihre Nachricht hier eingeben..." required></textarea>
                </div>

                <div class="email-preview">
                    <strong>📤 Empfänger:</strong> <?php echo number_format($stats['active'], 0, ',', '.'); ?> aktive Abonnenten
                </div>

                <button type="submit" class="btn btn-primary" style="margin-top: 20px;">
                    Newsletter Senden
                </button>
            </form>
        </div>
    </div>

    <script>
        // Handle compose form
        document.getElementById('composeForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const subject = document.getElementById('emailSubject').value;
            const message = document.getElementById('emailMessage').value;

            if (confirm('Sind Sie sicher, dass Sie diese E-Mail an alle aktiven Abonnenten senden möchten?')) {
                // Here you would send the email via AJAX
                alert('E-Mail-Versandfunktion wird in der Produktion implementiert.\n\nBetreff: ' + subject + '\nNachricht: ' + message);
            }
        });
    </script>
</body>
</html>
