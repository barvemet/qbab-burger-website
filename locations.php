<?php
// Locations Page - Modern design matching site template
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Allow includes and load config
if (!defined('ALLOW_INCLUDE')) {
    define('ALLOW_INCLUDE', true);
}
require_once __DIR__ . '/includes/config.php';

$lang = getCurrentLanguage();

// Page metadata per language
$page_title_map = [
    'de' => 'Standorte - Q-Bab Burger',
    'en' => 'Locations - Q-Bab Burger',
    'tr' => 'Lokasyonlar - Q-Bab Burger'
];
$page_desc_map = [
    'de' => 'Q-Bab Burger Standorte: adres, telefon, çalışma saatleri ve harita.',
    'en' => 'Q-Bab Burger locations: address, phone, opening hours and map.',
    'tr' => 'Q-Bab Burger lokasyonları: adres, telefon, çalışma saatleri ve harita.'
];

$page_title = $page_title_map[$lang] ?? $page_title_map['de'];
$page_desc  = $page_desc_map[$lang] ?? $page_desc_map['de'];

// Single location data (can be expanded later)
$location = [
    'name' => 'Q-Bab Burger Adelzhausen',
    'address_lines' => ['Mühlweg 1', '86559 Adelzhausen', 'Bayern, Deutschland'],
    'phone' => ADMIN_PHONE,
    'email' => ADMIN_EMAIL,
    'maps_url' => 'https://www.google.com/maps/dir/?api=1&destination=M%C3%BChlweg%201%2C%2086559%20Adelzhausen',
];

// Opening hours text per language
$hours_title = $lang === 'de' ? 'Öffnungszeiten' : ($lang === 'en' ? 'Opening Hours' : 'Çalışma Saatleri');
$weekday  = $lang === 'de' ? 'Montag - Freitag' : ($lang === 'en' ? 'Monday - Friday' : 'Pazartesi - Cuma');
$weekend  = $lang === 'de' ? 'Samstag - Sonntag' : ($lang === 'en' ? 'Saturday - Sunday' : 'Cumartesi - Pazar');
$hours_1  = '11:00 - 22:00';
$hours_2  = '12:00 - 23:00';

?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($page_desc); ?>">
    <title><?php echo htmlspecialchars($page_title); ?></title>

    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/navbar.css?v=<?php echo ASSET_VERSION; ?>">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/auth-modal.css?v=<?php echo ASSET_VERSION; ?>">

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #0a0a0a; color: #fff; font-family: 'Roboto', sans-serif; }

        .loc-hero { margin-top: 80px; padding: 100px 40px; background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%); text-align: center; position: relative; overflow: hidden; }
        .loc-hero h1 { font-family: 'Bebas Neue', cursive; font-size: 3.5rem; color: #f9a825; letter-spacing: 2px; margin-bottom: 1rem; }
        .loc-hero p { color: #ddd; max-width: 900px; margin: 0 auto; font-size: 1.1rem; line-height: 1.7; }

        .loc-container { max-width: 1200px; margin: 60px auto 100px; padding: 0 20px; display: grid; grid-template-columns: 1fr 1fr; gap: 40px; align-items: start; }
        .loc-card { background: rgba(255,255,255,0.04); border: 1px solid rgba(249,168,37,0.25); border-radius: 16px; padding: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .loc-name { font-family: 'Bebas Neue', cursive; font-size: 2rem; color: #f9a825; letter-spacing: 1px; margin-bottom: 12px; }
        .loc-address { color: #ccc; line-height: 1.7; margin-bottom: 18px; }
        .loc-actions { display: flex; gap: 12px; margin-top: 12px; flex-wrap: wrap; }
        .btn { display: inline-block; padding: 12px 18px; border-radius: 8px; border: 2px solid #f9a825; color: #f9a825; text-decoration: none; font-weight: 600; letter-spacing: 0.5px; transition: all 0.25s; }
        .btn:hover { background: #f9a825; color: #000; transform: translateY(-2px); }
        .btn-primary { background: #e74c3c; border-color: #e74c3c; color: #fff; }
        .btn-primary:hover { background: #c0392b; color: #fff; border-color: #c0392b; }

        .map { border-radius: 16px; overflow: hidden; border: 3px solid #f9a825; box-shadow: 0 10px 30px rgba(0,0,0,0.25); }
        .map iframe { width: 100%; height: 450px; border: 0; display: block; }

        .hours { margin-top: 20px; border-top: 1px solid rgba(255,255,255,0.08); padding-top: 16px; }
        .hours h3 { font-family: 'Bebas Neue', cursive; color: #f9a825; font-size: 1.6rem; letter-spacing: 1px; margin-bottom: 10px; }
        .hour-row { display: flex; justify-content: space-between; padding: 8px 0; color: #ccc; }
        .hour-row + .hour-row { border-top: 1px dashed rgba(255,255,255,0.08); }

        /* Footer Styles */
        .footer {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: #ffffff;
            padding: 60px 20px 30px;
            margin-top: 80px;
        }

        .footer-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer-left h2 {
            font-family: 'Bebas Neue', cursive;
            font-size: 2rem;
            color: #f9a825;
            margin-bottom: 15px;
        }

        .footer-middle h3,
        .footer-right h3 {
            font-family: 'Bebas Neue', cursive;
            font-size: 1.3rem;
            color: #f9a825;
            margin-bottom: 15px;
        }

        .footer-middle p,
        .footer-right p {
            color: #cccccc;
            line-height: 1.8;
        }

        .footer-right a {
            color: #ffffff;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-right a:hover {
            color: #f9a825;
        }

        .footer-phone {
            font-size: 1.2rem;
            font-weight: 600;
        }

        .footer-social {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .social-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(249, 168, 37, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #f9a825;
            transition: all 0.3s ease;
        }

        .social-icon:hover {
            background: #f9a825;
            color: white;
            transform: translateY(-3px);
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 30px;
            text-align: center;
        }

        .footer-copyright {
            color: #cccccc;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 30px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .footer-links a {
            color: #cccccc;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: #f9a825;
        }

        .footer-payment {
            display: flex;
            justify-content: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .payment-icon {
            height: 30px;
            opacity: 0.7;
            transition: opacity 0.3s ease;
        }

        .payment-icon:hover {
            opacity: 1;
        }

        /* Mobile Navbar Fixes */
        @media (max-width: 768px) {
            .top-navbar {
                padding: 15px 20px 15px 100px !important;
                min-height: 60px !important;
            }

            .top-navbar.scrolled {
                padding: 10px 20px 15px 100px !important;
                min-height: 50px !important;
            }

            .nav-menu {
                display: none !important;
            }

            .site-logo {
                left: 15px !important;
            }

            .site-logo img {
                height: 50px !important;
            }
        }

        @media (max-width: 968px) {
            .loc-container { grid-template-columns: 1fr; }
            .loc-hero { padding: 70px 20px; }
            .loc-hero h1 { font-size: 2.5rem; }
            
            .footer-content {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .footer-social {
                justify-content: center;
            }

            .footer-links {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <section class="loc-hero">
        <h1><?php echo t('nav_locations'); ?></h1>
        <p><?php echo htmlspecialchars($page_desc); ?></p>
    </section>

    <section class="loc-container">
        <div class="loc-card">
            <div class="loc-name"><?php echo htmlspecialchars($location['name']); ?></div>
            <div class="loc-address">
                <?php foreach ($location['address_lines'] as $line): ?>
                    <div><?php echo htmlspecialchars($line); ?></div>
                <?php endforeach; ?>
            </div>
            <div>
                <div><strong>Tel:</strong> <a href="tel:<?php echo htmlspecialchars($location['phone']); ?>" style="color:#f9a825; text-decoration:none;"><?php echo htmlspecialchars($location['phone']); ?></a></div>
                <div><strong>Email:</strong> <a href="mailto:<?php echo htmlspecialchars($location['email']); ?>" style="color:#f9a825; text-decoration:none;"><?php echo htmlspecialchars($location['email']); ?></a></div>
            </div>
            <div class="hours">
                <h3><?php echo htmlspecialchars($hours_title); ?></h3>
                <div class="hour-row"><span><?php echo htmlspecialchars($weekday); ?></span><span><?php echo $hours_1; ?></span></div>
                <div class="hour-row"><span><?php echo htmlspecialchars($weekend); ?></span><span><?php echo $hours_2; ?></span></div>
            </div>
            <div class="loc-actions">
                <a class="btn" href="<?php echo htmlspecialchars($location['maps_url']); ?>" target="_blank" rel="noopener">Google Maps</a>
                <a class="btn btn-primary" href="menu.php"><?php echo t('nav_order'); ?></a>
            </div>
        </div>
        <div class="map">
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2658.8!2d11.1!3d48.5!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zNDjCsDMwJzAwLjAiTiAxMcKwMDYnMDAuMCJF!5e0!3m2!1sde!2sde!4v1234567890" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
        </div>
    </section>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <script src="<?php echo ASSETS_URL; ?>/js/auth-modal.js?v=<?php echo ASSET_VERSION; ?>"></script>
    <script src="<?php echo ASSETS_URL; ?>/js/cart.js?v=<?php echo ASSET_VERSION; ?>"></script>

    <!-- Navbar scroll effect -->
    <script>
        // Add black background to navbar on scroll
        const navbar = document.querySelector('.top-navbar');
        
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    </script>
</body>
</html>
