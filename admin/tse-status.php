<?php
/**
 * Admin TSE Status Dashboard
 * Displays TSE health, configuration, and recent transactions
 *
 * @author Q-Bab Kasse System
 */

session_start();
define('ALLOW_INCLUDE', true);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/tse-service.php';

// Require admin login
requireAdminLogin();

// Get TSE service
$tseService = getTSEService();
$config = $tseService->getConfig();
$health = $tseService->checkHealth();

// Get recent TSE transactions from database
$db = getDBConnection();
$stmt = $db->prepare("
    SELECT
        order_number,
        tse_transaction_id,
        tse_signature,
        total_amount,
        payment_method,
        created_at,
        cashier_name,
        is_synced
    FROM orders
    WHERE order_source = 'KASSE'
    AND tse_transaction_id IS NOT NULL
    ORDER BY created_at DESC
    LIMIT 50
");
$stmt->execute();
$recentTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count statistics
$stmt = $db->query("
    SELECT
        COUNT(*) as total_transactions,
        COUNT(CASE WHEN tse_transaction_id IS NOT NULL THEN 1 END) as signed_transactions,
        COUNT(CASE WHEN tse_transaction_id IS NULL THEN 1 END) as unsigned_transactions,
        SUM(CASE WHEN tse_transaction_id IS NOT NULL THEN total_amount ELSE 0 END) as signed_total
    FROM orders
    WHERE order_source = 'KASSE'
    AND DATE(created_at) = CURDATE()
");
$todayStats = $stmt->fetch(PDO::FETCH_ASSOC);

$pageTitle = 'TSE Status Dashboard';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Q-Bab Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .header h1 {
            color: #333;
            margin-bottom: 5px;
        }

        .header p {
            color: #666;
        }

        .back-btn {
            display: inline-block;
            padding: 8px 16px;
            background: #666;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 10px;
        }

        .back-btn:hover {
            background: #555;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .card h2 {
            color: #333;
            margin-bottom: 15px;
            font-size: 18px;
            border-bottom: 2px solid #e0e0e0;
            padding-bottom: 10px;
        }

        .status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
        }

        .status-online {
            background: #4CAF50;
        }

        .status-offline {
            background: #f44336;
        }

        .status-warning {
            background: #ff9800;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #666;
            font-weight: 500;
        }

        .info-value {
            color: #333;
            font-family: monospace;
        }

        .stat-box {
            text-align: center;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 4px;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #333;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
            margin-top: 5px;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f5f5f5;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #e0e0e0;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
        }

        tr:hover {
            background: #f9f9f9;
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-success {
            background: #e8f5e9;
            color: #4CAF50;
        }

        .badge-warning {
            background: #fff3e0;
            color: #ff9800;
        }

        .badge-error {
            background: #ffebee;
            color: #f44336;
        }

        .truncate {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .action-btn {
            padding: 6px 12px;
            background: #2196F3;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        .action-btn:hover {
            background: #1976D2;
        }

        .refresh-btn {
            background: #4CAF50;
        }

        .refresh-btn:hover {
            background: #388E3C;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><?php echo $pageTitle; ?></h1>
            <p>Überwachung und Verwaltung der Fiskaly TSE Integration</p>
            <a href="index.php" class="back-btn">← Zurück zum Dashboard</a>
            <button class="action-btn refresh-btn" onclick="location.reload()" style="float: right;">Aktualisieren</button>
        </div>

        <!-- Status Cards -->
        <div class="grid">
            <!-- Health Status Card -->
            <div class="card">
                <h2>
                    <span class="status-indicator <?php echo $health['healthy'] ? 'status-online' : 'status-offline'; ?>"></span>
                    TSE Status
                </h2>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="info-value"><?php echo strtoupper($health['status']); ?></span>
                </div>
                <?php if (isset($health['tss_state'])): ?>
                <div class="info-row">
                    <span class="info-label">TSS State:</span>
                    <span class="info-value"><?php echo $health['tss_state']; ?></span>
                </div>
                <?php endif; ?>
                <div class="info-row">
                    <span class="info-label">Healthy:</span>
                    <span class="info-value"><?php echo $health['healthy'] ? '✅ Yes' : '❌ No'; ?></span>
                </div>
                <?php if (isset($health['message'])): ?>
                <div class="info-row">
                    <span class="info-label">Message:</span>
                    <span class="info-value"><?php echo htmlspecialchars($health['message']); ?></span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Configuration Card -->
            <div class="card">
                <h2>Konfiguration</h2>
                <div class="info-row">
                    <span class="info-label">Enabled:</span>
                    <span class="info-value"><?php echo $config['enabled'] ? 'Yes' : 'No'; ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">TSS ID:</span>
                    <span class="info-value truncate" title="<?php echo htmlspecialchars($config['tss_id']); ?>">
                        <?php echo htmlspecialchars(substr($config['tss_id'], 0, 20)) . '...'; ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">Client ID:</span>
                    <span class="info-value truncate" title="<?php echo htmlspecialchars($config['client_id']); ?>">
                        <?php echo htmlspecialchars(substr($config['client_id'], 0, 20)) . '...'; ?>
                    </span>
                </div>
                <div class="info-row">
                    <span class="info-label">API URL:</span>
                    <span class="info-value truncate" title="<?php echo htmlspecialchars($config['api_base_url']); ?>">
                        <?php echo htmlspecialchars(substr($config['api_base_url'], 0, 30)) . '...'; ?>
                    </span>
                </div>
            </div>

            <!-- Token Status Card -->
            <div class="card">
                <h2>Token Status</h2>
                <div class="info-row">
                    <span class="info-label">Token Cached:</span>
                    <span class="info-value"><?php echo $config['has_token'] ? '✅ Yes' : '❌ No'; ?></span>
                </div>
                <?php if ($config['has_token']): ?>
                <div class="info-row">
                    <span class="info-label">Expires In:</span>
                    <span class="info-value">
                        <?php
                        $seconds = $config['token_expires_in'];
                        $minutes = floor($seconds / 60);
                        echo "{$minutes} min {$seconds % 60} sec";
                        ?>
                    </span>
                </div>
                <?php endif; ?>
                <div class="info-row">
                    <span class="info-label">Cache Duration:</span>
                    <span class="info-value">60 minutes</span>
                </div>
                <div style="margin-top: 15px;">
                    <button class="action-btn" onclick="clearTokenCache()">Clear Token Cache</button>
                </div>
            </div>
        </div>

        <!-- Today's Statistics -->
        <div class="card">
            <h2>Heutige Statistiken</h2>
            <div class="grid">
                <div class="stat-box">
                    <div class="stat-value"><?php echo $todayStats['total_transactions']; ?></div>
                    <div class="stat-label">Gesamt Transaktionen</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?php echo $todayStats['signed_transactions']; ?></div>
                    <div class="stat-label">TSE Signiert</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?php echo $todayStats['unsigned_transactions']; ?></div>
                    <div class="stat-label">Nicht Signiert</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?php echo formatPrice($todayStats['signed_total'] ?? 0); ?></div>
                    <div class="stat-label">Signierter Gesamtbetrag</div>
                </div>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="card">
            <h2>Letzte TSE Transaktionen (50)</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Bestellnummer</th>
                            <th>TSE TX ID</th>
                            <th>Betrag</th>
                            <th>Zahlung</th>
                            <th>Kassierer</th>
                            <th>Datum</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentTransactions)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: #999;">
                                Keine TSE Transaktionen gefunden
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($recentTransactions as $tx): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($tx['order_number']); ?></td>
                            <td class="truncate" title="<?php echo htmlspecialchars($tx['tse_transaction_id']); ?>">
                                <?php echo htmlspecialchars(substr($tx['tse_transaction_id'], 0, 20)) . '...'; ?>
                            </td>
                            <td><?php echo formatPrice($tx['total_amount']); ?></td>
                            <td><?php echo htmlspecialchars($tx['payment_method']); ?></td>
                            <td><?php echo htmlspecialchars($tx['cashier_name']); ?></td>
                            <td><?php echo date('d.m.Y H:i', strtotime($tx['created_at'])); ?></td>
                            <td>
                                <?php if ($tx['is_synced']): ?>
                                    <span class="badge badge-success">Synced</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Offline</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <h2>Schnellaktionen</h2>
            <div class="grid">
                <button class="action-btn" onclick="window.open('../api/kasse/health.php', '_blank')">
                    Health Check API
                </button>
                <button class="action-btn" onclick="window.open('../api/kasse/test.php', '_blank')">
                    Run TSE Tests
                </button>
                <button class="action-btn" onclick="window.location.href='export-dsfinvk.php'">
                    DSFinV-K Export
                </button>
            </div>
        </div>
    </div>

    <script>
        function clearTokenCache() {
            if (confirm('Token-Cache wirklich löschen? Dies erzwingt eine neue Authentifizierung.')) {
                fetch('../api/kasse/clear-token-cache.php', {
                    method: 'POST'
                })
                .then(res => res.json())
                .then(data => {
                    alert(data.message || 'Token-Cache gelöscht');
                    location.reload();
                })
                .catch(err => {
                    alert('Fehler: ' + err.message);
                });
            }
        }
    </script>
</body>
</html>
