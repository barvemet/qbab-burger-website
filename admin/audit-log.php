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

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 50;
$offset = ($page - 1) * $perPage;

// Filters
$filterAction = isset($_GET['action']) ? $_GET['action'] : '';
$filterAdmin = isset($_GET['admin']) ? $_GET['admin'] : '';
$filterTable = isset($_GET['table']) ? $_GET['table'] : '';

// Build query
$where = [];
$params = [];

if ($filterAction) {
    $where[] = "action = ?";
    $params[] = $filterAction;
}
if ($filterAdmin) {
    $where[] = "admin_username = ?";
    $params[] = $filterAdmin;
}
if ($filterTable) {
    $where[] = "table_name = ?";
    $params[] = $filterTable;
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$countStmt = $db->prepare("SELECT COUNT(*) FROM admin_audit_log $whereSQL");
$countStmt->execute($params);
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $perPage);

// Get logs
$stmt = $db->prepare("
    SELECT * FROM admin_audit_log 
    $whereSQL
    ORDER BY created_at DESC 
    LIMIT ? OFFSET ?
");
$params[] = $perPage;
$params[] = $offset;
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Get unique values for filters
$actions = $db->query("SELECT DISTINCT action FROM admin_audit_log ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);
$admins = $db->query("SELECT DISTINCT admin_username FROM admin_audit_log ORDER BY admin_username")->fetchAll(PDO::FETCH_COLUMN);
$tables = $db->query("SELECT DISTINCT table_name FROM admin_audit_log WHERE table_name IS NOT NULL ORDER BY table_name")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Log - Q-Bab Burger Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .header {
            background: #2c3e50;
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .container { max-width: 1400px; margin: 20px auto; padding: 0 20px; }
        .filters {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .filters form {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: end;
        }
        .filter-group { display: flex; flex-direction: column; }
        .filter-group label { font-size: 0.9rem; margin-bottom: 5px; color: #666; }
        .filter-group select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            min-width: 150px;
        }
        .btn {
            padding: 8px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        .btn-primary { background: #3498db; color: white; }
        .btn-secondary { background: #95a5a6; color: white; }
        .log-table {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background: #34495e;
            color: white;
            font-weight: 600;
        }
        tr:hover { background: #f8f9fa; }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .badge-create { background: #d4edda; color: #155724; }
        .badge-update { background: #fff3cd; color: #856404; }
        .badge-delete { background: #f8d7da; color: #721c24; }
        .badge-login { background: #d1ecf1; color: #0c5460; }
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
        }
        .pagination a {
            padding: 8px 12px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
        }
        .pagination a.active { background: #3498db; color: white; border-color: #3498db; }
        .back-link {
            color: white;
            text-decoration: none;
            padding: 8px 16px;
            background: rgba(255,255,255,0.2);
            border-radius: 4px;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-card h3 { font-size: 2rem; color: #3498db; }
        .stat-card p { color: #666; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-history"></i> Audit Log</h1>
        <a href="index.php" class="back-link"><i class="fas fa-arrow-left"></i> Zurück zum Dashboard</a>
    </div>

    <div class="container">
        <!-- Stats -->
        <div class="stats">
            <div class="stat-card">
                <h3><?php echo number_format($totalRecords); ?></h3>
                <p>Gesamte Aktionen</p>
            </div>
            <div class="stat-card">
                <h3><?php echo count($admins); ?></h3>
                <p>Admins</p>
            </div>
            <div class="stat-card">
                <h3><?php echo count($actions); ?></h3>
                <p>Aktionstypen</p>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters">
            <form method="GET">
                <div class="filter-group">
                    <label>Aktion</label>
                    <select name="action">
                        <option value="">Alle</option>
                        <?php foreach ($actions as $action): ?>
                            <option value="<?php echo $action; ?>" <?php echo $filterAction === $action ? 'selected' : ''; ?>>
                                <?php echo $action; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Admin</label>
                    <select name="admin">
                        <option value="">Alle</option>
                        <?php foreach ($admins as $admin): ?>
                            <option value="<?php echo $admin; ?>" <?php echo $filterAdmin === $admin ? 'selected' : ''; ?>>
                                <?php echo $admin; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Tabelle</label>
                    <select name="table">
                        <option value="">Alle</option>
                        <?php foreach ($tables as $table): ?>
                            <option value="<?php echo $table; ?>" <?php echo $filterTable === $table ? 'selected' : ''; ?>>
                                <?php echo $table; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="audit-log.php" class="btn btn-secondary">Reset</a>
            </form>
        </div>

        <!-- Logs Table -->
        <div class="log-table">
            <table>
                <thead>
                    <tr>
                        <th>Zeitstempel</th>
                        <th>Admin</th>
                        <th>Aktion</th>
                        <th>Details</th>
                        <th>Tabelle</th>
                        <th>IP Adresse</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: #999;">
                                Keine Audit Logs gefunden
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?php echo date('d.m.Y H:i:s', strtotime($log['created_at'])); ?></td>
                                <td><?php echo htmlspecialchars($log['admin_username']); ?></td>
                                <td>
                                    <?php
                                    $badgeClass = 'badge-' . strtolower($log['action']);
                                    ?>
                                    <span class="badge <?php echo $badgeClass; ?>"><?php echo $log['action']; ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($log['details']); ?></td>
                                <td>
                                    <?php if ($log['table_name']): ?>
                                        <?php echo htmlspecialchars($log['table_name']); ?>
                                        <?php if ($log['record_id']): ?>
                                            (ID: <?php echo $log['record_id']; ?>)
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color: #999;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&action=<?php echo $filterAction; ?>&admin=<?php echo $filterAdmin; ?>&table=<?php echo $filterTable; ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <a href="?page=<?php echo $i; ?>&action=<?php echo $filterAction; ?>&admin=<?php echo $filterAdmin; ?>&table=<?php echo $filterTable; ?>" 
                       class="<?php echo $i === $page ? 'active' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&action=<?php echo $filterAction; ?>&admin=<?php echo $filterAdmin; ?>&table=<?php echo $filterTable; ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>

