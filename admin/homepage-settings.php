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

// Handle image upload
if (isset($_FILES['image_upload']) && $_FILES['image_upload']['error'] === UPLOAD_ERR_OK) {
    $imageField = $_POST['image_field'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB

    if (!in_array($_FILES['image_upload']['type'], $allowedTypes)) {
        $error = 'Nur JPG, PNG und WEBP Bilder sind erlaubt.';
    } elseif ($_FILES['image_upload']['size'] > $maxSize) {
        $error = 'Bild ist zu groß. Maximal 5MB erlaubt.';
    } else {
        $uploadsDir = UPLOADS_PATH . '/homepage';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }

        $extension = pathinfo($_FILES['image_upload']['name'], PATHINFO_EXTENSION);
        $filename = 'about_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
        $destination = $uploadsDir . '/' . $filename;

        if (move_uploaded_file($_FILES['image_upload']['tmp_name'], $destination)) {
            $imageUrl = 'homepage/' . $filename;

            // Update database
            $stmt = $db->prepare("
                INSERT INTO site_settings (setting_key, setting_value)
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE setting_value = ?
            ");
            $stmt->execute([$imageField, $imageUrl, $imageUrl]);

            $message = 'Bild erfolgreich hochgeladen!';
        } else {
            $error = 'Fehler beim Hochladen des Bildes.';
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $fields = [
        'homepage_about_title_de',
        'homepage_about_title_en',
        'homepage_about_title_tr',
        'homepage_about_subtitle_de',
        'homepage_about_subtitle_en',
        'homepage_about_subtitle_tr',
        'homepage_about_description_de',
        'homepage_about_description_en',
        'homepage_about_description_tr'
    ];

    try {
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $value = trim($_POST[$field]);
                $stmt = $db->prepare("
                    INSERT INTO site_settings (setting_key, setting_value)
                    VALUES (?, ?)
                    ON DUPLICATE KEY UPDATE setting_value = ?
                ");
                $stmt->execute([$field, $value, $value]);
            }
        }
        $message = 'Einstellungen erfolgreich gespeichert!';
    } catch (Exception $e) {
        $error = 'Fehler beim Speichern: ' . $e->getMessage();
    }
}

// Get current settings
$stmt = $db->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key LIKE 'homepage_about_%'");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homepage Einstellungen - Admin</title>
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
            max-width: 1200px;
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
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .card h2 {
            margin-bottom: 20px;
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
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
            margin-bottom: 25px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        .form-group input, .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
            font-family: inherit;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }
        .form-group input:focus, .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
        }
        .lang-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }
        .lang-tab {
            padding: 10px 20px;
            border: none;
            background: none;
            cursor: pointer;
            font-weight: 600;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        .lang-tab.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }
        .lang-content {
            display: none;
        }
        .lang-content.active {
            display: block;
        }
        .image-upload-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        .image-upload-box {
            border: 2px dashed #667eea;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            background: #f8f9ff;
        }
        .image-preview {
            width: 100%;
            max-width: 300px;
            height: 200px;
            margin: 20px auto;
            border-radius: 10px;
            overflow: hidden;
            background: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .image-preview img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .image-preview.empty {
            border: 2px dashed #ccc;
            color: #999;
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
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-upload {
            background: #28a745;
            color: white;
            cursor: pointer;
        }
        .btn-upload:hover {
            background: #218838;
        }
        input[type="file"] {
            display: none;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>🏠 Homepage Einstellungen</h1>
            <div class="nav-links">
                <a href="index.php">← Dashboard</a>
                <a href="logout.php">Abmelden</a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if ($message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Images Section -->
        <div class="card">
            <h2>📸 Bilder (Max. 2 Bilder)</h2>
            <div class="image-upload-section">
                <!-- Image 1 -->
                <div class="image-upload-box">
                    <h3>Bild 1</h3>
                    <div class="image-preview <?php echo empty($settings['homepage_about_image_1']) ? 'empty' : ''; ?>">
                        <?php if (!empty($settings['homepage_about_image_1'])): ?>
                            <img src="<?php echo UPLOADS_URL . '/' . $settings['homepage_about_image_1']; ?>" alt="Bild 1">
                        <?php else: ?>
                            Kein Bild
                        <?php endif; ?>
                    </div>
                    <form method="POST" enctype="multipart/form-data" style="margin-top: 20px;">
                        <input type="hidden" name="image_field" value="homepage_about_image_1">
                        <input type="file" name="image_upload" id="image1" accept="image/*" onchange="this.form.submit()">
                        <label for="image1" class="btn btn-upload">Bild hochladen</label>
                    </form>
                </div>

                <!-- Image 2 -->
                <div class="image-upload-box">
                    <h3>Bild 2</h3>
                    <div class="image-preview <?php echo empty($settings['homepage_about_image_2']) ? 'empty' : ''; ?>">
                        <?php if (!empty($settings['homepage_about_image_2'])): ?>
                            <img src="<?php echo UPLOADS_URL . '/' . $settings['homepage_about_image_2']; ?>" alt="Bild 2">
                        <?php else: ?>
                            Kein Bild
                        <?php endif; ?>
                    </div>
                    <form method="POST" enctype="multipart/form-data" style="margin-top: 20px;">
                        <input type="hidden" name="image_field" value="homepage_about_image_2">
                        <input type="file" name="image_upload" id="image2" accept="image/*" onchange="this.form.submit()">
                        <label for="image2" class="btn btn-upload">Bild hochladen</label>
                    </form>
                </div>
            </div>
        </div>

        <!-- Text Content Section -->
        <div class="card">
            <h2>📝 Text Inhalte</h2>

            <div class="lang-tabs">
                <button class="lang-tab active" onclick="switchLang('de')">🇩🇪 Deutsch</button>
                <button class="lang-tab" onclick="switchLang('en')">🇬🇧 English</button>
                <button class="lang-tab" onclick="switchLang('tr')">🇹🇷 Türkçe</button>
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="update">

                <!-- German -->
                <div class="lang-content active" id="lang-de">
                    <div class="form-group">
                        <label for="homepage_about_title_de">Titel (Deutsch)</label>
                        <input type="text" id="homepage_about_title_de" name="homepage_about_title_de"
                               value="<?php echo htmlspecialchars($settings['homepage_about_title_de'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="homepage_about_subtitle_de">Untertitel (Deutsch)</label>
                        <input type="text" id="homepage_about_subtitle_de" name="homepage_about_subtitle_de"
                               value="<?php echo htmlspecialchars($settings['homepage_about_subtitle_de'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="homepage_about_description_de">Beschreibung (Deutsch)</label>
                        <textarea id="homepage_about_description_de" name="homepage_about_description_de"><?php echo htmlspecialchars($settings['homepage_about_description_de'] ?? ''); ?></textarea>
                    </div>
                </div>

                <!-- English -->
                <div class="lang-content" id="lang-en">
                    <div class="form-group">
                        <label for="homepage_about_title_en">Title (English)</label>
                        <input type="text" id="homepage_about_title_en" name="homepage_about_title_en"
                               value="<?php echo htmlspecialchars($settings['homepage_about_title_en'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="homepage_about_subtitle_en">Subtitle (English)</label>
                        <input type="text" id="homepage_about_subtitle_en" name="homepage_about_subtitle_en"
                               value="<?php echo htmlspecialchars($settings['homepage_about_subtitle_en'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="homepage_about_description_en">Description (English)</label>
                        <textarea id="homepage_about_description_en" name="homepage_about_description_en"><?php echo htmlspecialchars($settings['homepage_about_description_en'] ?? ''); ?></textarea>
                    </div>
                </div>

                <!-- Turkish -->
                <div class="lang-content" id="lang-tr">
                    <div class="form-group">
                        <label for="homepage_about_title_tr">Başlık (Türkçe)</label>
                        <input type="text" id="homepage_about_title_tr" name="homepage_about_title_tr"
                               value="<?php echo htmlspecialchars($settings['homepage_about_title_tr'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="homepage_about_subtitle_tr">Alt Başlık (Türkçe)</label>
                        <input type="text" id="homepage_about_subtitle_tr" name="homepage_about_subtitle_tr"
                               value="<?php echo htmlspecialchars($settings['homepage_about_subtitle_tr'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="homepage_about_description_tr">Açıklama (Türkçe)</label>
                        <textarea id="homepage_about_description_tr" name="homepage_about_description_tr"><?php echo htmlspecialchars($settings['homepage_about_description_tr'] ?? ''); ?></textarea>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">💾 Änderungen speichern</button>
            </form>
        </div>
    </div>

    <script>
        function switchLang(lang) {
            // Hide all content
            document.querySelectorAll('.lang-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.lang-tab').forEach(el => el.classList.remove('active'));

            // Show selected
            document.getElementById('lang-' + lang).classList.add('active');
            event.target.classList.add('active');
        }
    </script>
</body>
</html>
