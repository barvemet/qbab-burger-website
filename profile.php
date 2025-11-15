<?php
/**
 * User Profile Page
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || !$_SESSION['user_logged_in']) {
    header('Location: index.php');
    exit;
}

// Load configuration
if (!defined('ALLOW_INCLUDE')) {
    define('ALLOW_INCLUDE', true);
}
require_once __DIR__ . '/includes/config.php';

// Get user data from database
$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mein Profil - Q-Bab Burger</title>
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/navbar.css?v=<?php echo ASSET_VERSION; ?>">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 120px 20px 20px;
        }

        /* Navbar styles in navbar.css */

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .profile-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            margin-bottom: 20px;
        }

        .profile-header {
            background: linear-gradient(135deg, #e10000 0%, #c90000 100%);
            color: #ffffff;
            padding: 40px;
            text-align: center;
        }

        .profile-header h1 {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .profile-header p {
            font-size: 16px;
            opacity: 0.9;
        }

        .profile-body {
            padding: 40px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
            font-size: 14px;
        }

        input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
        }

        input:focus {
            outline: none;
            border-color: #e10000;
            box-shadow: 0 0 0 4px rgba(225, 0, 0, 0.1);
        }

        input:disabled {
            background: #f5f5f5;
            cursor: not-allowed;
        }

        .btn {
            padding: 14px 30px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #e10000 0%, #c90000 100%);
            color: #ffffff;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(225, 0, 0, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            color: #ffffff;
            margin-left: 10px;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: none;
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

        .back-link {
            color: #ffffff;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 20px;
            font-size: 16px;
            transition: all 0.3s ease;
        }

        .back-link:hover {
            transform: translateX(-5px);
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .profile-body {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Include Navbar -->
    <?php include __DIR__ . '/includes/navbar.php'; ?>
    
    <div class="container">
        <a href="index.php" class="back-link">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="19" y1="12" x2="5" y2="12"></line>
                <polyline points="12 19 5 12 12 5"></polyline>
            </svg>
            Zurück zur Startseite
        </a>

        <div class="profile-card">
            <div class="profile-header">
                <h1><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></h1>
                <p><?php echo htmlspecialchars($user['email']); ?></p>
            </div>

            <div class="profile-body">
                <div id="alert" class="alert"></div>

                <form id="profileForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="firstname">Vorname *</label>
                            <input type="text" id="firstname" name="firstname" value="<?php echo htmlspecialchars($user['firstname']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="lastname">Nachname *</label>
                            <input type="text" id="lastname" name="lastname" value="<?php echo htmlspecialchars($user['lastname']); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">E-Mail-Adresse</label>
                        <input type="email" id="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                    </div>

                    <div class="form-group">
                        <label for="phone">Telefonnummer</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="+49 123 456789">
                    </div>

                    <div class="form-group">
                        <label for="address">Adresse</label>
                        <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" placeholder="Straße und Hausnummer">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="postal_code">Postleitzahl</label>
                            <input type="text" id="postal_code" name="postal_code" value="<?php echo htmlspecialchars($user['postal_code'] ?? ''); ?>" placeholder="12345">
                        </div>

                        <div class="form-group">
                            <label for="city">Stadt</label>
                            <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>" placeholder="Berlin">
                        </div>
                    </div>

                    <div class="form-group" style="margin-top: 30px;">
                        <button type="submit" class="btn btn-primary">Profil aktualisieren</button>
                        <a href="index.php" class="btn btn-secondary">Abbrechen</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        const CSRF_TOKEN = '<?php echo generateCSRFToken(); ?>';
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = {
                csrf_token: CSRF_TOKEN,
                firstname: document.getElementById('firstname').value.trim(),
                lastname: document.getElementById('lastname').value.trim(),
                phone: document.getElementById('phone').value.trim(),
                address: document.getElementById('address').value.trim(),
                postal_code: document.getElementById('postal_code').value.trim(),
                city: document.getElementById('city').value.trim()
            };

            fetch('/api/update-profile.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                const alert = document.getElementById('alert');
                alert.textContent = data.message;

                if (data.success) {
                    alert.className = 'alert alert-success';
                    alert.style.display = 'block';

                    // Update session name if changed
                    if (data.user) {
                        document.querySelector('.profile-header h1').textContent =
                            data.user.firstname + ' ' + data.user.lastname;
                    }
                } else {
                    alert.className = 'alert alert-error';
                    alert.style.display = 'block';
                }

                // Scroll to alert
                alert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

                // Hide alert after 5 seconds
                setTimeout(() => {
                    alert.style.display = 'none';
                }, 5000);
            })
            .catch(error => {
                const alert = document.getElementById('alert');
                alert.textContent = 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.';
                alert.className = 'alert alert-error';
                alert.style.display = 'block';
            });
        });
    </script>
    <script src="<?php echo ASSETS_URL; ?>/js/cart.js?v=<?php echo time(); ?>"></script>
</body>
</html>
