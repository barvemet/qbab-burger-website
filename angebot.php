<?php
/**
 * Q-Bab Burger - Special Offers / Angebot Page
 * Modern, attractive offers page with countdown timers
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle language change via URL parameter
if (isset($_GET['lang']) && in_array($_GET['lang'], ['de', 'en', 'tr'])) {
    $_SESSION['language'] = $_GET['lang'];
    session_write_close();
session_start();
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// Define constant for includes
define('ALLOW_INCLUDE', true);

// Include config
require_once __DIR__ . '/includes/config.php';

// Get current language
$current_lang = getCurrentLanguage();

// Page metadata
$page_title = $current_lang == 'de' ? 'Aktuelle Angebote' : 
              ($current_lang == 'en' ? 'Special Offers' : 'Özel Teklifler');

// Get database connection
$db = getDBConnection();

// Fetch active offers
try {
    $stmt = $db->prepare("
        SELECT 
            id,
            title_{$current_lang} as title,
            description_{$current_lang} as description,
            original_price,
            offer_price,
            discount_percentage,
            image_url,
            badge_text_{$current_lang} as badge_text,
            badge_color,
            valid_from,
            valid_until,
            is_featured,
            terms_{$current_lang} as terms,
            button_text_{$current_lang} as button_text,
            button_link
        FROM special_offers
        WHERE is_active = 1
        AND (valid_from <= NOW())
        AND (valid_until IS NULL OR valid_until >= NOW())
        ORDER BY is_featured DESC, display_order ASC, id DESC
    ");
    $stmt->execute();
    $offers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Update view count
    if (!empty($offers)) {
        $offerIds = array_column($offers, 'id');
        $placeholders = str_repeat('?,', count($offerIds) - 1) . '?';
        $updateStmt = $db->prepare("UPDATE special_offers SET view_count = view_count + 1 WHERE id IN ($placeholders)");
        $updateStmt->execute($offerIds);
    }
} catch (Exception $e) {
    $offers = [];
}

// Separate featured and regular offers
$featured_offers = array_filter($offers, function($offer) {
    return $offer['is_featured'] == 1;
});
$regular_offers = array_filter($offers, function($offer) {
    return $offer['is_featured'] == 0;
});
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - Q-Bab Burger</title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Roboto:wght@300;400;500;700;900&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/navbar.css?v=<?php echo ASSET_VERSION; ?>">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/auth-modal.css?v=<?php echo ASSET_VERSION; ?>">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/animations.css?v=<?php echo ASSET_VERSION; ?>">
    
    <style>
        :root {
            --primary-color: #f9a825;
            --secondary-color: #e74c3c;
            --dark-bg: #1a1a1a;
            --card-bg: #2d2d2d;
            --text-light: #ffffff;
            --text-gray: #cccccc;
            --success-color: #27ae60;
            --shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            --shadow-hover: 0 20px 60px rgba(0, 0, 0, 0.4);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, var(--dark-bg) 0%, #2d2d2d 100%);
            color: var(--text-light);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Hero Section */
        .hero-section {
            margin-top: 80px;
            padding: 60px 20px 40px;
            text-align: center;
            background: linear-gradient(135deg, rgba(249, 168, 37, 0.1) 0%, rgba(231, 76, 60, 0.1) 100%);
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(249, 168, 37, 0.1) 0%, transparent 70%);
            animation: pulse 15s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }

        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 800px;
            margin: 0 auto;
        }

        .hero-title {
            font-family: 'Bebas Neue', cursive;
            font-size: 4rem;
            color: var(--primary-color);
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
            margin-bottom: 20px;
            animation: fadeInDown 1s ease-out;
        }

        .hero-subtitle {
            font-size: 1.3rem;
            color: var(--text-gray);
            margin-bottom: 30px;
            animation: fadeInUp 1s ease-out 0.3s both;
        }

        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Featured Offers Section */
        .featured-section {
            padding: 60px 20px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .section-title {
            font-family: 'Bebas Neue', cursive;
            font-size: 3rem;
            text-align: center;
            color: var(--primary-color);
            margin-bottom: 50px;
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            border-radius: 2px;
        }

        .featured-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 40px;
            margin-bottom: 60px;
        }

        /* Offer Card - Featured */
        .offer-card-featured {
            background: var(--card-bg);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: all 0.4s ease;
            position: relative;
            animation: fadeInScale 0.6s ease-out;
        }

        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }

        .offer-card-featured:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: var(--shadow-hover);
        }

        .offer-image {
            width: 100%;
            height: 350px;
            object-fit: cover;
            transition: transform 0.4s ease;
        }

        .offer-card-featured:hover .offer-image {
            transform: scale(1.1);
        }

        .offer-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 10px 25px;
            background: var(--secondary-color);
            color: white;
            font-weight: 900;
            font-size: 0.9rem;
            border-radius: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            z-index: 2;
            animation: bounceIn 0.8s ease-out;
        }

        @keyframes bounceIn {
            0% { transform: scale(0); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        .offer-content {
            padding: 30px;
        }

        .offer-title {
            font-family: 'Bebas Neue', cursive;
            font-size: 2.2rem;
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .offer-description {
            color: var(--text-gray);
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 25px;
        }

        .offer-price-section {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }

        .original-price {
            font-size: 1.5rem;
            color: #888;
            text-decoration: line-through;
        }

        .offer-price {
            font-size: 2.5rem;
            color: var(--success-color);
            font-weight: 900;
        }

        .discount-badge {
            background: var(--secondary-color);
            color: white;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 1.1rem;
        }

        /* Countdown Timer */
        .countdown-timer {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin: 25px 0;
            flex-wrap: wrap;
        }

        .countdown-item {
            text-align: center;
            background: rgba(249, 168, 37, 0.1);
            padding: 15px 20px;
            border-radius: 12px;
            min-width: 80px;
        }

        .countdown-value {
            font-size: 2rem;
            font-weight: 900;
            color: var(--primary-color);
            display: block;
        }

        .countdown-label {
            font-size: 0.8rem;
            color: var(--text-gray);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .offer-cta {
            display: inline-block;
            background: linear-gradient(135deg, var(--primary-color), #e67e22);
            color: white;
            padding: 18px 45px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            box-shadow: 0 5px 20px rgba(249, 168, 37, 0.4);
            margin-top: 10px;
        }

        .offer-cta:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(249, 168, 37, 0.6);
        }

        /* Regular Offers Grid */
        .regular-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
        }

        .offer-card-regular {
            background: var(--card-bg);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .offer-card-regular:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .offer-card-regular .offer-image {
            height: 250px;
        }

        .offer-card-regular .offer-content {
            padding: 25px;
        }

        .offer-card-regular .offer-title {
            font-size: 1.8rem;
        }

        .offer-card-regular .offer-description {
            font-size: 1rem;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 100px 20px;
        }

        .empty-state-icon {
            font-size: 5rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .empty-state-title {
            font-family: 'Bebas Neue', cursive;
            font-size: 2.5rem;
            color: var(--text-gray);
            margin-bottom: 15px;
        }

        .empty-state-text {
            color: var(--text-gray);
            font-size: 1.2rem;
        }

        /* Terms */
        .offer-terms {
            font-size: 0.85rem;
            color: #888;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Footer Styles */
        .footer {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            color: var(--text-light);
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
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .footer-middle h3,
        .footer-right h3 {
            font-family: 'Bebas Neue', cursive;
            font-size: 1.3rem;
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .footer-middle p,
        .footer-right p {
            color: var(--text-gray);
            line-height: 1.8;
        }

        .footer-right a {
            color: var(--text-light);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-right a:hover {
            color: var(--primary-color);
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
            color: var(--primary-color);
            transition: all 0.3s ease;
        }

        .social-icon:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-3px);
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 30px;
            text-align: center;
        }

        .footer-copyright {
            color: var(--text-gray);
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
            color: var(--text-gray);
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: var(--primary-color);
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
                padding: 10px 20px 10px 100px !important;
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

        /* Responsive */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }

            .hero-subtitle {
                font-size: 1.1rem;
            }

            .section-title {
                font-size: 2.2rem;
            }

            .featured-grid {
                grid-template-columns: 1fr;
            }

            .regular-grid {
                grid-template-columns: 1fr;
            }

            .offer-price-section {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .countdown-timer {
                gap: 10px;
            }

            .countdown-item {
                min-width: 70px;
                padding: 10px 15px;
            }

            .countdown-value {
                font-size: 1.5rem;
            }

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
    <?php include 'includes/navbar.php'; ?>
    <?php include 'includes/login-popup.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-content">
            <h1 class="hero-title">
                <?php 
                echo $current_lang == 'de' ? '🔥 AKTUELLE ANGEBOTE' : 
                     ($current_lang == 'en' ? '🔥 SPECIAL OFFERS' : '🔥 ÖZEL TEKLİFLER');
                ?>
            </h1>
            <p class="hero-subtitle">
                <?php 
                echo $current_lang == 'de' ? 'Spare bis zu 50% auf unsere beliebtesten Gerichte!' : 
                     ($current_lang == 'en' ? 'Save up to 50% on our most popular dishes!' : 'En popüler yemeklerimizde %50\'ye varan tasarruf!');
                ?>
            </p>
        </div>
    </section>

    <!-- Featured Offers -->
    <?php if (!empty($featured_offers)): ?>
    <section class="featured-section">
        <h2 class="section-title">
            <?php 
            echo $current_lang == 'de' ? 'Top Angebote' : 
                 ($current_lang == 'en' ? 'Top Offers' : 'Öne Çıkan Teklifler');
            ?>
        </h2>
        
        <div class="featured-grid">
            <?php foreach ($featured_offers as $offer): ?>
            <div class="offer-card-featured" data-offer-id="<?php echo $offer['id']; ?>">
                <?php if ($offer['badge_text']): ?>
                <div class="offer-badge" style="background-color: <?php echo htmlspecialchars($offer['badge_color']); ?>">
                    <?php echo htmlspecialchars($offer['badge_text']); ?>
                </div>
                <?php endif; ?>
                
                <img src="<?php echo htmlspecialchars($offer['image_url'] ?: ASSETS_URL . '/images/placeholder-food.jpg'); ?>" 
                     alt="<?php echo htmlspecialchars($offer['title']); ?>" 
                     class="offer-image">
                
                <div class="offer-content">
                    <h3 class="offer-title"><?php echo htmlspecialchars($offer['title']); ?></h3>
                    <p class="offer-description"><?php echo htmlspecialchars($offer['description']); ?></p>
                    
                    <div class="offer-price-section">
                        <span class="original-price">€<?php echo number_format($offer['original_price'], 2); ?></span>
                        <span class="offer-price">€<?php echo number_format($offer['offer_price'], 2); ?></span>
                        <span class="discount-badge">-<?php echo $offer['discount_percentage']; ?>%</span>
                    </div>
                    
                    <?php if ($offer['valid_until']): ?>
                    <div class="countdown-timer" data-end-date="<?php echo $offer['valid_until']; ?>">
                        <div class="countdown-item">
                            <span class="countdown-value days">00</span>
                            <span class="countdown-label"><?php echo $current_lang == 'de' ? 'Tage' : ($current_lang == 'en' ? 'Days' : 'Gün'); ?></span>
                        </div>
                        <div class="countdown-item">
                            <span class="countdown-value hours">00</span>
                            <span class="countdown-label"><?php echo $current_lang == 'de' ? 'Stunden' : ($current_lang == 'en' ? 'Hours' : 'Saat'); ?></span>
                        </div>
                        <div class="countdown-item">
                            <span class="countdown-value minutes">00</span>
                            <span class="countdown-label"><?php echo $current_lang == 'de' ? 'Minuten' : ($current_lang == 'en' ? 'Minutes' : 'Dakika'); ?></span>
                        </div>
                        <div class="countdown-item">
                            <span class="countdown-value seconds">00</span>
                            <span class="countdown-label"><?php echo $current_lang == 'de' ? 'Sekunden' : ($current_lang == 'en' ? 'Seconds' : 'Saniye'); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <a href="<?php echo htmlspecialchars($offer['button_link']); ?>" 
                       class="offer-cta"
                       onclick="trackOfferClick(<?php echo $offer['id']; ?>)">
                        <?php echo htmlspecialchars($offer['button_text']); ?>
                    </a>
                    
                    <?php if ($offer['terms']): ?>
                    <div class="offer-terms">
                        <strong><?php echo $current_lang == 'de' ? 'Bedingungen:' : ($current_lang == 'en' ? 'Terms:' : 'Şartlar:'); ?></strong>
                        <?php echo htmlspecialchars($offer['terms']); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Regular Offers -->
    <?php if (!empty($regular_offers)): ?>
    <section class="featured-section">
        <h2 class="section-title">
            <?php 
            echo $current_lang == 'de' ? 'Weitere Angebote' : 
                 ($current_lang == 'en' ? 'More Offers' : 'Diğer Teklifler');
            ?>
        </h2>
        
        <div class="regular-grid">
            <?php foreach ($regular_offers as $offer): ?>
            <div class="offer-card-regular" data-offer-id="<?php echo $offer['id']; ?>">
                <?php if ($offer['badge_text']): ?>
                <div class="offer-badge" style="background-color: <?php echo htmlspecialchars($offer['badge_color']); ?>">
                    <?php echo htmlspecialchars($offer['badge_text']); ?>
                </div>
                <?php endif; ?>
                
                <img src="<?php echo htmlspecialchars($offer['image_url'] ?: ASSETS_URL . '/images/placeholder-food.jpg'); ?>" 
                     alt="<?php echo htmlspecialchars($offer['title']); ?>" 
                     class="offer-image">
                
                <div class="offer-content">
                    <h3 class="offer-title"><?php echo htmlspecialchars($offer['title']); ?></h3>
                    <p class="offer-description"><?php echo htmlspecialchars($offer['description']); ?></p>
                    
                    <div class="offer-price-section">
                        <span class="original-price">€<?php echo number_format($offer['original_price'], 2); ?></span>
                        <span class="offer-price">€<?php echo number_format($offer['offer_price'], 2); ?></span>
                        <span class="discount-badge">-<?php echo $offer['discount_percentage']; ?>%</span>
                    </div>
                    
                    <?php if ($offer['valid_until']): ?>
                    <div class="countdown-timer" data-end-date="<?php echo $offer['valid_until']; ?>">
                        <div class="countdown-item">
                            <span class="countdown-value days">00</span>
                            <span class="countdown-label"><?php echo $current_lang == 'de' ? 'T' : ($current_lang == 'en' ? 'D' : 'G'); ?></span>
                        </div>
                        <div class="countdown-item">
                            <span class="countdown-value hours">00</span>
                            <span class="countdown-label"><?php echo $current_lang == 'de' ? 'Std' : ($current_lang == 'en' ? 'Hrs' : 'Sa'); ?></span>
                        </div>
                        <div class="countdown-item">
                            <span class="countdown-value minutes">00</span>
                            <span class="countdown-label"><?php echo $current_lang == 'de' ? 'Min' : ($current_lang == 'en' ? 'Min' : 'Dk'); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <a href="<?php echo htmlspecialchars($offer['button_link']); ?>" 
                       class="offer-cta"
                       onclick="trackOfferClick(<?php echo $offer['id']; ?>)">
                        <?php echo htmlspecialchars($offer['button_text']); ?>
                    </a>
                    
                    <?php if ($offer['terms']): ?>
                    <div class="offer-terms">
                        <?php echo htmlspecialchars($offer['terms']); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Empty State -->
    <?php if (empty($offers)): ?>
    <section class="empty-state">
        <div class="empty-state-icon">🎁</div>
        <h2 class="empty-state-title">
            <?php 
            echo $current_lang == 'de' ? 'Keine aktiven Angebote' : 
                 ($current_lang == 'en' ? 'No Active Offers' : 'Aktif Teklif Yok');
            ?>
        </h2>
        <p class="empty-state-text">
            <?php 
            echo $current_lang == 'de' ? 'Schauen Sie bald wieder vorbei für neue Angebote!' : 
                 ($current_lang == 'en' ? 'Check back soon for new offers!' : 'Yeni teklifler için yakında tekrar bakın!');
            ?>
        </p>
    </section>
    <?php endif; ?>

    <?php include 'includes/footer.php'; ?>

    <!-- Countdown Timer Script -->
    <script>
    // Countdown Timer Function
    function initCountdownTimers() {
        const timers = document.querySelectorAll('.countdown-timer');
        
        timers.forEach(timer => {
            const endDate = new Date(timer.dataset.endDate).getTime();
            
            const updateTimer = () => {
                const now = new Date().getTime();
                const distance = endDate - now;
                
                if (distance < 0) {
                    timer.innerHTML = '<div style="color: #e74c3c; font-weight: bold;">Angebot abgelaufen / Offer expired</div>';
                    return;
                }
                
                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                
                timer.querySelector('.days').textContent = String(days).padStart(2, '0');
                timer.querySelector('.hours').textContent = String(hours).padStart(2, '0');
                timer.querySelector('.minutes').textContent = String(minutes).padStart(2, '0');
                
                const secondsEl = timer.querySelector('.seconds');
                if (secondsEl) {
                    secondsEl.textContent = String(seconds).padStart(2, '0');
                }
            };
            
            updateTimer();
            setInterval(updateTimer, 1000);
        });
    }

    // Track offer click (analytics)
    function trackOfferClick(offerId) {
        fetch('/api/track-offer-click.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ offer_id: offerId })
        }).catch(() => {});
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', initCountdownTimers);
    </script>

    <!-- Auth Modal Script -->
    <script src="<?php echo ASSETS_URL; ?>/js/auth-modal.js?v=<?php echo ASSET_VERSION; ?>"></script>

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
