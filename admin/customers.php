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

// Handle user update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_user') {
    $userId = (int)$_POST['user_id'];
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $postal_code = trim($_POST['postal_code']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    try {
        $stmt = $db->prepare("
            UPDATE users SET
            firstname = ?, lastname = ?, email = ?, phone = ?,
            address = ?, city = ?, postal_code = ?, is_active = ?
            WHERE id = ?
        ");
        $stmt->execute([$firstname, $lastname, $email, $phone, $address, $city, $postal_code, $is_active, $userId]);
        $message = 'Kunde erfolgreich aktualisiert!';
    } catch (Exception $e) {
        $error = 'Fehler beim Aktualisieren: ' . $e->getMessage();
    }
}

// Get all users with their order counts and total spent
$stmt = $db->query("
    SELECT
        u.*,
        COUNT(DISTINCT o.id) as order_count,
        COALESCE(SUM(o.total_amount), 0) as total_spent,
        MAX(o.created_at) as last_order_date
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
$users = $stmt->fetchAll();

// Get statistics
$totalUsers = count($users);
$activeUsers = count(array_filter($users, fn($u) => $u['is_active']));
$verifiedUsers = count(array_filter($users, fn($u) => $u['email_verified']));
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kunden verwalten - Admin</title>
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
            max-width: 1600px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .stats-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }
        .stat-label {
            color: #666;
            margin-top: 5px;
        }
        .customers-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .customers-card h2 {
            margin-bottom: 20px;
            color: #333;
        }
        .search-box {
            margin-bottom: 20px;
        }
        .search-box input {
            width: 100%;
            max-width: 400px;
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
        }
        .search-box input:focus {
            outline: none;
            border-color: #667eea;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
            position: sticky;
            top: 0;
        }
        tr:hover {
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
        .badge-verified {
            background: #d1ecf1;
            color: #0c5460;
        }
        .badge-unverified {
            background: #fff3cd;
            color: #856404;
        }
        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        .btn-view {
            background: #667eea;
            color: white;
        }
        .btn-view:hover {
            background: #5568d3;
        }
        .user-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
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
            position: relative;
        }
        .modal-close {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 28px;
            color: #999;
            cursor: pointer;
            background: none;
            border: none;
        }
        .modal-close:hover {
            color: #333;
        }
        .user-detail-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-top: 20px;
        }
        .detail-item {
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .detail-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }
        .detail-value {
            font-size: 16px;
            color: #333;
            font-weight: 500;
        }
        .orders-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e0e0e0;
        }
        .order-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .order-number {
            font-weight: 600;
            color: #667eea;
        }
        .order-date {
            color: #666;
            font-size: 14px;
        }
        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
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
            font-size: 14px;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        .form-group input:focus, .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: normal;
            cursor: pointer;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            margin-left: 10px;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .btn-edit {
            background: #ffc107;
            color: #333;
            margin-right: 5px;
        }
        .btn-edit:hover {
            background: #e0a800;
        }
        .edit-form-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>👥 Kunden verwalten</h1>
            <div class="nav-links">
                <a href="index.php">← Dashboard</a>
                <a href="logout.php">Abmelden</a>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Statistics -->
        <div class="stats-bar">
            <div class="stat-card">
                <div class="stat-value"><?php echo $totalUsers; ?></div>
                <div class="stat-label">Gesamt Kunden</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $activeUsers; ?></div>
                <div class="stat-label">Aktive Kunden</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $verifiedUsers; ?></div>
                <div class="stat-label">Verifizierte E-Mails</div>
            </div>
        </div>

        <!-- Customers Table -->
        <div class="customers-card">
            <h2>Alle Kunden (<?php echo $totalUsers; ?>)</h2>

            <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="search-box">
                <input type="text" id="searchInput" placeholder="🔍 Suche nach Name, E-Mail oder Telefon..." onkeyup="searchTable()">
            </div>

            <?php if (empty($users)): ?>
            <p style="text-align: center; color: #999; padding: 40px;">Noch keine Kunden registriert.</p>
            <?php else: ?>
            <table id="customersTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>E-Mail</th>
                        <th>Telefon</th>
                        <th>Stadt</th>
                        <th>Bestellungen</th>
                        <th>Gesamt ausgegeben</th>
                        <th>Status</th>
                        <th>Registriert</th>
                        <th>Aktion</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></td>
                        <td>
                            <?php echo htmlspecialchars($user['email']); ?>
                            <?php if ($user['email_verified']): ?>
                            <span class="badge badge-verified">✓</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($user['city'] ?? '-'); ?></td>
                        <td><strong><?php echo $user['order_count']; ?>x</strong></td>
                        <td><strong>€<?php echo number_format($user['total_spent'], 2); ?></strong></td>
                        <td>
                            <span class="badge <?php echo $user['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                                <?php echo $user['is_active'] ? 'Aktiv' : 'Inaktiv'; ?>
                            </span>
                        </td>
                        <td><?php echo date('d.m.Y', strtotime($user['created_at'])); ?></td>
                        <td>
                            <button class="btn btn-edit" onclick="editUser(<?php echo $user['id']; ?>)">Bearbeiten</button>
                            <button class="btn btn-view" onclick="viewUser(<?php echo $user['id']; ?>)">Details</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- User Details Modal -->
    <div id="userModal" class="user-modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal()">&times;</button>
            <div id="modalBody">
                <!-- Will be filled by JavaScript -->
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editModal" class="user-modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeEditModal()">&times;</button>
            <div id="editModalBody">
                <!-- Will be filled by JavaScript -->
            </div>
        </div>
    </div>

    <script>
        // Search functionality
        function searchTable() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toLowerCase();
            const table = document.getElementById('customersTable');
            const tr = table.getElementsByTagName('tr');

            for (let i = 1; i < tr.length; i++) {
                const td = tr[i].getElementsByTagName('td');
                let found = false;

                for (let j = 0; j < td.length; j++) {
                    if (td[j]) {
                        const txtValue = td[j].textContent || td[j].innerText;
                        if (txtValue.toLowerCase().indexOf(filter) > -1) {
                            found = true;
                            break;
                        }
                    }
                }

                tr[i].style.display = found ? '' : 'none';
            }
        }

        // View user details
        async function viewUser(userId) {
            try {
                const response = await fetch(`get-user-details.php?id=${userId}`);
                const data = await response.json();

                if (data.success) {
                    displayUserDetails(data.user, data.orders);
                } else {
                    alert('Fehler beim Laden der Benutzerdaten');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Fehler beim Laden der Benutzerdaten');
            }
        }

        function displayUserDetails(user, orders) {
            const modalBody = document.getElementById('modalBody');

            let ordersHtml = '';
            if (orders && orders.length > 0) {
                ordersHtml = `
                    <div class="orders-section">
                        <h3>Bestellhistorie (${orders.length} Bestellungen)</h3>
                        ${orders.map(order => `
                            <div class="order-item">
                                <div class="order-header">
                                    <span class="order-number">#${order.order_number}</span>
                                    <span class="order-date">${new Date(order.created_at).toLocaleDateString('de-DE')}</span>
                                </div>
                                <div style="display: flex; justify-content: space-between; font-size: 14px;">
                                    <span>Status: <strong>${order.order_status}</strong></span>
                                    <span>Betrag: <strong>€${parseFloat(order.total_amount).toFixed(2)}</strong></span>
                                </div>
                                ${order.notes ? `<div style="margin-top: 8px; color: #666; font-size: 13px;">Notiz: ${order.notes}</div>` : ''}
                            </div>
                        `).join('')}
                    </div>
                `;
            } else {
                ordersHtml = `
                    <div class="orders-section">
                        <h3>Bestellhistorie</h3>
                        <p style="color: #999; text-align: center; padding: 20px;">Noch keine Bestellungen</p>
                    </div>
                `;
            }

            modalBody.innerHTML = `
                <h2>${user.firstname} ${user.lastname}</h2>

                <div class="user-detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">E-Mail</div>
                        <div class="detail-value">${user.email}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Telefon</div>
                        <div class="detail-value">${user.phone || '-'}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Adresse</div>
                        <div class="detail-value">${user.address || '-'}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Stadt</div>
                        <div class="detail-value">${user.city || '-'}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">PLZ</div>
                        <div class="detail-value">${user.postal_code || '-'}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Status</div>
                        <div class="detail-value">
                            <span class="badge ${user.is_active ? 'badge-active' : 'badge-inactive'}">
                                ${user.is_active ? 'Aktiv' : 'Inaktiv'}
                            </span>
                            ${user.email_verified ? '<span class="badge badge-verified">E-Mail verifiziert</span>' : '<span class="badge badge-unverified">E-Mail nicht verifiziert</span>'}
                        </div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Registriert am</div>
                        <div class="detail-value">${new Date(user.created_at).toLocaleDateString('de-DE', {year: 'numeric', month: 'long', day: 'numeric'})}</div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Letzter Login</div>
                        <div class="detail-value">${user.last_login ? new Date(user.last_login).toLocaleDateString('de-DE', {year: 'numeric', month: 'long', day: 'numeric'}) : 'Nie'}</div>
                    </div>
                </div>

                ${ordersHtml}
            `;

            document.getElementById('userModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('userModal').style.display = 'none';
        }

        // Edit user
        async function editUser(userId) {
            try {
                const response = await fetch(`get-user-details.php?id=${userId}`);
                const data = await response.json();

                if (data.success) {
                    displayEditForm(data.user);
                } else {
                    alert('Fehler beim Laden der Benutzerdaten');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Fehler beim Laden der Benutzerdaten');
            }
        }

        function displayEditForm(user) {
            const editModalBody = document.getElementById('editModalBody');

            editModalBody.innerHTML = `
                <h2>Kunde bearbeiten</h2>
                <form method="POST" class="edit-form-section">
                    <input type="hidden" name="action" value="update_user">
                    <input type="hidden" name="user_id" value="${user.id}">

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label for="firstname">Vorname *</label>
                            <input type="text" id="firstname" name="firstname" value="${user.firstname}" required>
                        </div>

                        <div class="form-group">
                            <label for="lastname">Nachname *</label>
                            <input type="text" id="lastname" name="lastname" value="${user.lastname}" required>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label for="email">E-Mail *</label>
                            <input type="email" id="email" name="email" value="${user.email}" required>
                        </div>

                        <div class="form-group">
                            <label for="phone">Telefon</label>
                            <input type="text" id="phone" name="phone" value="${user.phone || ''}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="address">Adresse</label>
                        <textarea id="address" name="address">${user.address || ''}</textarea>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label for="city">Stadt</label>
                            <input type="text" id="city" name="city" value="${user.city || ''}">
                        </div>

                        <div class="form-group">
                            <label for="postal_code">PLZ</label>
                            <input type="text" id="postal_code" name="postal_code" value="${user.postal_code || ''}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_active" value="1" ${user.is_active ? 'checked' : ''}>
                            Kunde ist aktiv
                        </label>
                    </div>

                    <div style="margin-top: 20px;">
                        <button type="submit" class="btn-primary">Änderungen speichern</button>
                        <button type="button" class="btn-secondary" onclick="closeEditModal()">Abbrechen</button>
                    </div>
                </form>
            `;

            document.getElementById('editModal').style.display = 'flex';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('userModal');
            const editModal = document.getElementById('editModal');

            if (event.target == modal) {
                closeModal();
            }
            if (event.target == editModal) {
                closeEditModal();
            }
        }
    </script>
</body>
</html>
