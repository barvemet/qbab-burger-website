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
    try {
        $stmt = $db->prepare("DELETE FROM reviews WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'Bewertung erfolgreich gelöscht!';
    } catch (Exception $e) {
        $error = 'Fehler beim Löschen: ' . $e->getMessage();
    }
}

// Handle approve/feature toggle
if (isset($_GET['toggle_approve'])) {
    $id = (int)$_GET['toggle_approve'];
    try {
        $stmt = $db->prepare("UPDATE reviews SET is_approved = NOT is_approved WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'Status aktualisiert!';
    } catch (Exception $e) {
        $error = 'Fehler: ' . $e->getMessage();
    }
}


// Handle form submission (add/edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $customer_name = trim($_POST['customer_name']);
    $customer_email = trim($_POST['customer_email']);
    $rating = (int)$_POST['rating'];
    $review_text = trim($_POST['review_text']);
    $is_approved = isset($_POST['is_approved']) ? 1 : 0;

    try {
        if ($_POST['action'] === 'add') {
            $stmt = $db->prepare("
                INSERT INTO reviews (customer_name, customer_email, rating, review_text, is_approved, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$customer_name, $customer_email, $rating, $review_text, $is_approved]);
            $message = 'Bewertung erfolgreich hinzugefügt!';
        } elseif ($_POST['action'] === 'edit') {
            $stmt = $db->prepare("
                UPDATE reviews SET
                customer_name = ?, customer_email = ?, rating = ?, review_text = ?,
                is_approved = ?
                WHERE id = ?
            ");
            $stmt->execute([$customer_name, $customer_email, $rating, $review_text, $is_approved, $id]);
            $message = 'Bewertung erfolgreich aktualisiert!';
        }
    } catch (Exception $e) {
        $error = 'Fehler: ' . $e->getMessage();
    }
}

// Get all reviews
$stmt = $db->query("SELECT * FROM reviews ORDER BY created_at DESC");
$reviews = $stmt->fetchAll();

// Get review for editing
$editReview = null;
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM reviews WHERE id = ?");
    $stmt->execute([$editId]);
    $editReview = $stmt->fetch();
}

// Get statistics
$approvedCount = count(array_filter($reviews, fn($r) => $r['is_approved']));
$avgRating = count($reviews) > 0 ? array_sum(array_column($reviews, 'rating')) / count($reviews) : 0;
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bewertungen verwalten - Admin</title>
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
        .stats-bar {
            grid-column: 1 / -1;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 10px;
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
            min-height: 100px;
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
            font-size: 14px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-warning {
            background: #ffc107;
            color: #333;
            padding: 8px 16px;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
            padding: 8px 16px;
        }
        .btn-success {
            background: #28a745;
            color: white;
            padding: 8px 16px;
        }
        .review-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        .review-customer {
            font-weight: 600;
            color: #333;
            font-size: 16px;
        }
        .review-email {
            color: #666;
            font-size: 14px;
            margin-top: 3px;
        }
        .review-rating {
            color: #ffc107;
            font-size: 18px;
        }
        .review-text {
            color: #555;
            line-height: 1.6;
            margin-bottom: 15px;
        }
        .review-meta {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-approved {
            background: #d4edda;
            color: #155724;
        }
        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }
        .badge-featured {
            background: #d1ecf1;
            color: #0c5460;
        }
        .review-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
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
            <h1>⭐ Bewertungen verwalten</h1>
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
                <div class="stat-value"><?php echo count($reviews); ?></div>
                <div class="stat-label">Gesamt Bewertungen</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $approvedCount; ?></div>
                <div class="stat-label">Genehmigt</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($avgRating, 1); ?>★</div>
                <div class="stat-label">Durchschnitt</div>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Add/Edit Form -->
        <div class="form-card">
            <h2><?php echo $editReview ? 'Bewertung bearbeiten' : 'Neue Bewertung hinzufügen'; ?></h2>
            <form method="POST">
                <input type="hidden" name="action" value="<?php echo $editReview ? 'edit' : 'add'; ?>">
                <?php if ($editReview): ?>
                <input type="hidden" name="id" value="<?php echo $editReview['id']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="customer_name">Kundenname *</label>
                    <input type="text" id="customer_name" name="customer_name"
                           value="<?php echo $editReview ? htmlspecialchars($editReview['customer_name']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label for="customer_email">E-Mail (Optional)</label>
                    <input type="email" id="customer_email" name="customer_email"
                           value="<?php echo $editReview ? htmlspecialchars($editReview['customer_email']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="rating">Bewertung (1-5 Sterne) *</label>
                    <select id="rating" name="rating" required>
                        <option value="">Bitte wählen...</option>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <option value="<?php echo $i; ?>"
                                <?php echo ($editReview && $editReview['rating'] == $i) ? 'selected' : ''; ?>>
                            <?php echo $i; ?> <?php echo str_repeat('★', $i); ?>
                        </option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="review_text">Bewertungstext</label>
                    <textarea id="review_text" name="review_text"><?php echo $editReview ? htmlspecialchars($editReview['review_text']) : ''; ?></textarea>
                </div>

                <div class="form-group">
                    <div class="checkbox-group">
                        <label>
                            <input type="checkbox" name="is_approved" value="1"
                                   <?php echo (!$editReview || $editReview['is_approved']) ? 'checked' : ''; ?>>
                            Genehmigt (auf Website anzeigen)
                        </label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">
                    <?php echo $editReview ? 'Aktualisieren' : 'Hinzufügen'; ?>
                </button>
                <?php if ($editReview): ?>
                <a href="reviews.php" class="btn" style="background: #6c757d; color: white; margin-left: 10px;">Abbrechen</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Reviews List -->
        <div class="list-card">
            <h2>Alle Bewertungen (<?php echo count($reviews); ?>)</h2>
            <?php if (empty($reviews)): ?>
            <p style="text-align: center; color: #999; padding: 40px;">Noch keine Bewertungen vorhanden.</p>
            <?php else: ?>
            <?php foreach ($reviews as $review): ?>
            <div class="review-card">
                <div class="review-header">
                    <div>
                        <div class="review-customer"><?php echo htmlspecialchars($review['customer_name']); ?></div>
                        <?php if ($review['customer_email']): ?>
                        <div class="review-email"><?php echo htmlspecialchars($review['customer_email']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="review-rating">
                        <?php echo str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']); ?>
                    </div>
                </div>

                <?php if ($review['review_text']): ?>
                <div class="review-text"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></div>
                <?php endif; ?>

                <div class="review-meta">
                    <span class="badge <?php echo $review['is_approved'] ? 'badge-approved' : 'badge-pending'; ?>">
                        <?php echo $review['is_approved'] ? '✓ Genehmigt' : '⏳ Ausstehend'; ?>
                    </span>
                    <span style="color: #999; font-size: 12px;">
                        <?php echo date('d.m.Y H:i', strtotime($review['created_at'])); ?>
                    </span>
                </div>

                <div class="review-actions">
                    <a href="?edit=<?php echo $review['id']; ?>" class="btn btn-warning">Bearbeiten</a>
                    <a href="?toggle_approve=<?php echo $review['id']; ?>" class="btn btn-success">
                        <?php echo $review['is_approved'] ? 'Ablehnen' : 'Genehmigen'; ?>
                    </a>
                    <a href="?delete=<?php echo $review['id']; ?>"
                       class="btn btn-danger"
                       onclick="return confirm('Sind Sie sicher, dass Sie diese Bewertung löschen möchten?')">
                        Löschen
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
