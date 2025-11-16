<?php
/**
 * DSFinV-K Export for German Tax Authorities
 * Exports transaction data in compliance with DSFinV-K standard
 *
 * @author Q-Bab Kasse System
 */

session_start();
define('ALLOW_INCLUDE', true);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/tse-service.php';

// Require admin login
requireAdminLogin();

$pageTitle = 'DSFinV-K Export';
$exportGenerated = false;
$exportData = null;
$error = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $startDate = $_POST['start_date'] ?? null;
    $endDate = $_POST['end_date'] ?? null;

    if ($startDate && $endDate) {
        try {
            $tseService = getTSEService();
            $exportData = $tseService->exportDSFinVK($startDate, $endDate);
            $exportGenerated = true;

            // Log export action
            logAdminAction(
                'DSFINVK_EXPORT',
                "Exported data from {$startDate} to {$endDate}",
                'orders',
                null
            );

        } catch (Exception $e) {
            $error = 'Export failed: ' . $e->getMessage();
            error_log('DSFinV-K export error: ' . $e->getMessage());
        }
    } else {
        $error = 'Please select both start and end dates';
    }
}

// Default dates (last 30 days)
$defaultEndDate = date('Y-m-d');
$defaultStartDate = date('Y-m-d', strtotime('-30 days'));
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
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .card {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        h1 {
            color: #333;
            margin-bottom: 10px;
        }

        .subtitle {
            color: #666;
            margin-bottom: 20px;
        }

        .back-btn {
            display: inline-block;
            padding: 8px 16px;
            background: #666;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .back-btn:hover {
            background: #555;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        input[type="date"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        .button {
            padding: 12px 24px;
            background: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
        }

        .button:hover {
            background: #388E3C;
        }

        .button-secondary {
            background: #2196F3;
        }

        .button-secondary:hover {
            background: #1976D2;
        }

        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #e8f5e9;
            color: #4CAF50;
            border: 1px solid #4CAF50;
        }

        .alert-error {
            background: #ffebee;
            color: #f44336;
            border: 1px solid #f44336;
        }

        .info-box {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 4px;
            border-left: 4px solid #2196F3;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th {
            background: #f5f5f5;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #ddd;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #f0f0f0;
        }

        .export-actions {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }

        .truncate {
            max-width: 250px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <a href="tse-status.php" class="back-btn">← Zurück zu TSE Status</a>

            <h1><?php echo $pageTitle; ?></h1>
            <p class="subtitle">Datenexport für Finanzamt (DSFinV-K Standard)</p>

            <div class="info-box">
                <strong>ℹ️ Hinweis:</strong> Dieser Export enthält alle TSE-signierten Transaktionen im gewählten Zeitraum
                gemäß DSFinV-K (Digitale Schnittstelle der Finanzverwaltung für Kassensysteme).
            </div>

            <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <!-- Export Form -->
            <form method="POST" action="">
                <div class="form-group">
                    <label for="start_date">Startdatum:</label>
                    <input type="date" id="start_date" name="start_date"
                           value="<?php echo $_POST['start_date'] ?? $defaultStartDate; ?>" required>
                </div>

                <div class="form-group">
                    <label for="end_date">Enddatum:</label>
                    <input type="date" id="end_date" name="end_date"
                           value="<?php echo $_POST['end_date'] ?? $defaultEndDate; ?>" required>
                </div>

                <button type="submit" class="button">Export Generieren</button>
            </form>
        </div>

        <?php if ($exportGenerated && $exportData !== null): ?>
        <div class="card">
            <h2>Export Ergebnisse</h2>

            <?php if (empty($exportData)): ?>
            <div class="alert alert-error">
                Keine TSE-signierten Transaktionen im gewählten Zeitraum gefunden.
            </div>
            <?php else: ?>
            <div class="alert alert-success">
                ✅ <?php echo count($exportData); ?> Transaktionen exportiert
            </div>

            <div class="export-actions">
                <button class="button button-secondary" onclick="downloadCSV()">Als CSV Herunterladen</button>
                <button class="button button-secondary" onclick="downloadJSON()">Als JSON Herunterladen</button>
                <button class="button" onclick="window.print()">Drucken</button>
            </div>

            <table id="exportTable">
                <thead>
                    <tr>
                        <th>Bestellnummer</th>
                        <th>TSE TX ID</th>
                        <th>TSE Signatur</th>
                        <th>Betrag (€)</th>
                        <th>Zahlungsart</th>
                        <th>Kassierer</th>
                        <th>Datum/Zeit</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($exportData as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['order_number']); ?></td>
                        <td class="truncate" title="<?php echo htmlspecialchars($row['tse_transaction_id']); ?>">
                            <?php echo htmlspecialchars(substr($row['tse_transaction_id'], 0, 30)) . '...'; ?>
                        </td>
                        <td class="truncate" title="<?php echo htmlspecialchars($row['tse_signature']); ?>">
                            <?php echo htmlspecialchars(substr($row['tse_signature'], 0, 30)) . '...'; ?>
                        </td>
                        <td><?php echo formatPrice($row['total_amount']); ?></td>
                        <td><?php echo htmlspecialchars($row['payment_method']); ?></td>
                        <td><?php echo htmlspecialchars($row['cashier_name']); ?></td>
                        <td><?php echo date('d.m.Y H:i:s', strtotime($row['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
        const exportData = <?php echo json_encode($exportData ?? []); ?>;

        function downloadCSV() {
            if (!exportData || exportData.length === 0) {
                alert('Keine Daten zum Exportieren');
                return;
            }

            // CSV headers
            let csv = 'Bestellnummer,TSE Transaction ID,TSE Signatur,Betrag,Zahlungsart,Kassierer,Datum\n';

            // CSV rows
            exportData.forEach(row => {
                csv += [
                    row.order_number,
                    row.tse_transaction_id,
                    '"' + row.tse_signature + '"',
                    row.total_amount,
                    row.payment_method,
                    row.cashier_name,
                    row.created_at
                ].join(',') + '\n';
            });

            // Download
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            const filename = 'dsfinvk_export_' + new Date().toISOString().split('T')[0] + '.csv';

            link.setAttribute('href', url);
            link.setAttribute('download', filename);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        function downloadJSON() {
            if (!exportData || exportData.length === 0) {
                alert('Keine Daten zum Exportieren');
                return;
            }

            const blob = new Blob([JSON.stringify(exportData, null, 2)], { type: 'application/json' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            const filename = 'dsfinvk_export_' + new Date().toISOString().split('T')[0] + '.json';

            link.setAttribute('href', url);
            link.setAttribute('download', filename);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>
</html>
