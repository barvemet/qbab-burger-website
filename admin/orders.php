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

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = $_POST['order_id'];
    $newStatus = $_POST['order_status'];

    try {
        $db->beginTransaction();

        // Update order status
        $stmt = $db->prepare("UPDATE orders SET order_status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$newStatus, $orderId]);

        // Add to status history
        $stmt = $db->prepare("
            INSERT INTO order_status_history (order_id, status, comment)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$orderId, $newStatus, 'Status von Admin geändert']);

        $db->commit();
        $message = 'Bestellstatus erfolgreich aktualisiert!';
    } catch (Exception $e) {
        $db->rollBack();
        $error = 'Fehler beim Aktualisieren: ' . $e->getMessage();
    }
}

// Get filter parameters
$filterStatus = $_GET['status'] ?? 'all';
$filterPayment = $_GET['payment'] ?? 'all';
$searchTerm = $_GET['search'] ?? '';

// Build query
$query = "
    SELECT
        o.*,
        COUNT(oi.id) as item_count
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    WHERE 1=1
";

$params = [];

if ($filterStatus !== 'all') {
    $query .= " AND o.order_status = ?";
    $params[] = $filterStatus;
}

if ($filterPayment !== 'all') {
    $query .= " AND o.payment_method = ?";
    $params[] = $filterPayment;
}

if ($searchTerm) {
    $query .= " AND (o.order_number LIKE ? OR o.customer_email LIKE ? OR o.customer_firstname LIKE ? OR o.customer_lastname LIKE ?)";
    $searchParam = "%$searchTerm%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$query .= " GROUP BY o.id ORDER BY o.created_at DESC LIMIT 100";

$stmt = $db->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Get statistics
$statsQuery = $db->query("
    SELECT
        COUNT(*) as total_orders,
        SUM(CASE WHEN order_status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN order_status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
        SUM(CASE WHEN order_status = 'preparing' THEN 1 ELSE 0 END) as preparing,
        SUM(CASE WHEN order_status = 'ready' THEN 1 ELSE 0 END) as ready,
        SUM(CASE WHEN order_status = 'delivered' THEN 1 ELSE 0 END) as delivered,
        SUM(total_amount) as total_revenue
    FROM orders
");
$stats = $statsQuery->fetch();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bestellungen Verwalten - Q-Bab Burger Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        h1 {
            color: #333;
            margin-bottom: 30px;
            font-size: 2rem;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #007bff;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        /* Alert Messages */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
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

        /* Statistics */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-card.pending { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .stat-card.confirmed { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .stat-card.preparing { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        .stat-card.delivered { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
        .stat-card.revenue { background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            margin: 10px 0;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        /* Filters */
        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        .filters select,
        .filters input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.95rem;
        }

        .filters input[type="text"] {
            flex: 1;
            min-width: 250px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.95rem;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background: #0056b3;
        }

        /* Orders Table */
        .table-container {
            overflow-x: auto;
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
            color: #333;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d1ecf1; color: #0c5460; }
        .status-preparing { background: #d4edda; color: #155724; }
        .status-ready { background: #cce5ff; color: #004085; }
        .status-delivered { background: #d1e7dd; color: #0f5132; }
        .status-cancelled { background: #f8d7da; color: #721c24; }

        .payment-badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .payment-cash { background: #e7f3ff; color: #0066cc; }
        .payment-stripe { background: #f0e5ff; color: #6600cc; }
        .payment-paypal { background: #fff5e6; color: #cc6600; }

        .order-actions {
            display: flex;
            gap: 5px;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 0.85rem;
        }

        .btn-info {
            background: #17a2b8;
            color: white;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        /* Modal */
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

        .modal-content {
            position: relative;
            background: white;
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            border-radius: 8px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-close {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 2rem;
            cursor: pointer;
            color: #999;
        }

        .modal-close:hover {
            color: #333;
        }

        .order-detail-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin: 20px 0;
        }

        .detail-item {
            padding: 10px;
            background: #f8f9fa;
            border-radius: 4px;
        }

        .detail-label {
            font-weight: 600;
            color: #666;
            font-size: 0.85rem;
            margin-bottom: 5px;
        }

        .detail-value {
            color: #333;
        }

        .order-items-list {
            margin: 20px 0;
        }

        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="back-link">← Zurück zum Dashboard</a>

        <h1>📦 Bestellungen Verwalten</h1>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Gesamt Bestellungen</div>
                <div class="stat-value"><?php echo $stats['total_orders']; ?></div>
            </div>
            <div class="stat-card pending">
                <div class="stat-label">Ausstehend</div>
                <div class="stat-value"><?php echo $stats['pending']; ?></div>
            </div>
            <div class="stat-card confirmed">
                <div class="stat-label">Bestätigt</div>
                <div class="stat-value"><?php echo $stats['confirmed']; ?></div>
            </div>
            <div class="stat-card preparing">
                <div class="stat-label">In Vorbereitung</div>
                <div class="stat-value"><?php echo $stats['preparing']; ?></div>
            </div>
            <div class="stat-card delivered">
                <div class="stat-label">Geliefert</div>
                <div class="stat-value"><?php echo $stats['delivered']; ?></div>
            </div>
            <div class="stat-card revenue">
                <div class="stat-label">Gesamtumsatz</div>
                <div class="stat-value">€<?php echo number_format($stats['total_revenue'], 2, ',', '.'); ?></div>
            </div>
        </div>

        <!-- Filters -->
        <form method="GET" class="filters">
            <select name="status">
                <option value="all" <?php echo $filterStatus === 'all' ? 'selected' : ''; ?>>Alle Status</option>
                <option value="pending" <?php echo $filterStatus === 'pending' ? 'selected' : ''; ?>>Ausstehend</option>
                <option value="confirmed" <?php echo $filterStatus === 'confirmed' ? 'selected' : ''; ?>>Bestätigt</option>
                <option value="preparing" <?php echo $filterStatus === 'preparing' ? 'selected' : ''; ?>>In Vorbereitung</option>
                <option value="ready" <?php echo $filterStatus === 'ready' ? 'selected' : ''; ?>>Bereit</option>
                <option value="delivered" <?php echo $filterStatus === 'delivered' ? 'selected' : ''; ?>>Geliefert</option>
                <option value="cancelled" <?php echo $filterStatus === 'cancelled' ? 'selected' : ''; ?>>Storniert</option>
            </select>

            <select name="payment">
                <option value="all" <?php echo $filterPayment === 'all' ? 'selected' : ''; ?>>Alle Zahlungen</option>
                <option value="cash" <?php echo $filterPayment === 'cash' ? 'selected' : ''; ?>>Barzahlung</option>
                <option value="stripe" <?php echo $filterPayment === 'stripe' ? 'selected' : ''; ?>>Kreditkarte</option>
                <option value="paypal" <?php echo $filterPayment === 'paypal' ? 'selected' : ''; ?>>PayPal</option>
            </select>

            <input type="text" name="search" placeholder="Suche nach Bestellnr., E-Mail, Name..." value="<?php echo htmlspecialchars($searchTerm); ?>">

            <button type="submit" class="btn btn-primary">Filtern</button>
            <a href="orders.php" class="btn btn-primary">Reset</a>
        </form>

        <!-- Orders Table -->
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Bestellnr.</th>
                        <th>Datum</th>
                        <th>Kunde</th>
                        <th>Artikel</th>
                        <th>Betrag</th>
                        <th>Zahlung</th>
                        <th>Status</th>
                        <th>Aktionen</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                        <td><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></td>
                        <td>
                            <?php echo htmlspecialchars($order['customer_firstname'] . ' ' . $order['customer_lastname']); ?><br>
                            <small style="color: #666;"><?php echo htmlspecialchars($order['customer_email']); ?></small>
                        </td>
                        <td><?php echo $order['item_count']; ?> Artikel</td>
                        <td><strong>€<?php echo number_format($order['total_amount'], 2, ',', '.'); ?></strong></td>
                        <td>
                            <span class="payment-badge payment-<?php echo $order['payment_method']; ?>">
                                <?php
                                    $paymentLabels = [
                                        'cash' => 'Barzahlung',
                                        'stripe' => 'Kreditkarte',
                                        'paypal' => 'PayPal'
                                    ];
                                    echo $paymentLabels[$order['payment_method']] ?? $order['payment_method'];
                                ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo $order['order_status']; ?>">
                                <?php
                                    $statusLabels = [
                                        'pending' => 'Ausstehend',
                                        'confirmed' => 'Bestätigt',
                                        'preparing' => 'In Vorbereitung',
                                        'ready' => 'Bereit',
                                        'delivered' => 'Geliefert',
                                        'cancelled' => 'Storniert'
                                    ];
                                    echo $statusLabels[$order['order_status']] ?? $order['order_status'];
                                ?>
                            </span>
                        </td>
                        <td class="order-actions">
                            <button onclick="viewOrder(<?php echo $order['id']; ?>)" class="btn btn-sm btn-info">Details</button>
                            <button onclick="updateStatus(<?php echo $order['id']; ?>, '<?php echo $order['order_status']; ?>')" class="btn btn-sm btn-success">Status</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>

                    <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 40px; color: #999;">
                            Keine Bestellungen gefunden.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Order Details Modal -->
    <div id="orderModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeModal()">&times;</span>
            <div id="orderDetails"></div>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <span class="modal-close" onclick="closeStatusModal()">&times;</span>
            <h2>Status Aktualisieren</h2>
            <form method="POST">
                <input type="hidden" name="order_id" id="status_order_id">
                <div style="margin: 20px 0;">
                    <label style="display: block; margin-bottom: 10px; font-weight: 600;">Neuer Status:</label>
                    <select name="order_status" id="status_select" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="pending">Ausstehend</option>
                        <option value="confirmed">Bestätigt</option>
                        <option value="preparing">In Vorbereitung</option>
                        <option value="ready">Bereit zur Abholung</option>
                        <option value="delivered">Geliefert</option>
                        <option value="cancelled">Storniert</option>
                    </select>
                </div>
                <button type="submit" name="update_status" class="btn btn-primary" style="width: 100%;">Status Aktualisieren</button>
            </form>
        </div>
    </div>

    <script>
        function viewOrder(orderId) {
            // Fetch order details via AJAX
            fetch('get-order-details.php?id=' + orderId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayOrderDetails(data.order);
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        function displayOrderDetails(order) {
            const html = `
                <h2>Bestellung #${order.order_number}</h2>
                <div class="order-detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Kunde</div>
                        <div class="detail-value">${order.customer_firstname} ${order.customer_lastname}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">E-Mail</div>
                        <div class="detail-value">${order.customer_email}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Telefon</div>
                        <div class="detail-value">${order.customer_phone}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Bestelldatum</div>
                        <div class="detail-value">${new Date(order.created_at).toLocaleString('de-DE')}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Lieferadresse</div>
                        <div class="detail-value">${order.delivery_address}<br>${order.delivery_postal_code} ${order.delivery_city}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Zahlungsmethode</div>
                        <div class="detail-value">${order.payment_method}</div>
                    </div>
                </div>
                <h3 style="margin-top: 20px;">Bestellte Artikel</h3>
                <div class="order-items-list">
                    ${order.items.map(item => `
                        <div class="order-item">
                            <div>
                                <strong>${item.product_name}</strong><br>
                                <small>${item.quantity} × €${parseFloat(item.product_price).toFixed(2)}</small>
                            </div>
                            <div><strong>€${parseFloat(item.subtotal).toFixed(2)}</strong></div>
                        </div>
                    `).join('')}
                </div>
                <div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #ddd;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span>Zwischensumme:</span>
                        <span>€${parseFloat(order.subtotal).toFixed(2)}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span>MwSt. (19%):</span>
                        <span>€${parseFloat(order.tax).toFixed(2)}</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 1.2rem; font-weight: bold;">
                        <span>Gesamt:</span>
                        <span>€${parseFloat(order.total_amount).toFixed(2)}</span>
                    </div>
                </div>
            `;
            document.getElementById('orderDetails').innerHTML = html;
            document.getElementById('orderModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('orderModal').style.display = 'none';
        }

        function updateStatus(orderId, currentStatus) {
            document.getElementById('status_order_id').value = orderId;
            document.getElementById('status_select').value = currentStatus;
            document.getElementById('statusModal').style.display = 'block';
        }

        function closeStatusModal() {
            document.getElementById('statusModal').style.display = 'none';
        }

        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>
