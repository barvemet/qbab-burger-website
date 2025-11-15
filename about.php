<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle language change via URL parameter
if (isset($_GET['lang']) && in_array($_GET['lang'], ['de', 'en', 'tr'])) {
    $_SESSION['language'] = $_GET['lang'];
    // Force save session
    session_write_close();
    session_start();
    // Redirect to clean URL without parameters
    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
    exit;
}

// Define constant for includes
define('ALLOW_INCLUDE', true);

// Include config
require_once __DIR__ . '/includes/config.php';

// Get current language
$lang = getCurrentLanguage();

// Page metadata for SEO
$page_title = $lang == 'de' ? 'Über uns - Q-Bab Burger Adelzhausen | Beste Burger & Kebabs' : 
              ($lang == 'en' ? 'About Us - Q-Bab Burger Adelzhausen | Best Burgers & Kebabs' : 
              'Hakkımızda - Q-Bab Burger Adelzhausen | En İyi Burgerler & Kebaplar');

$page_description = $lang == 'de' ? 
    'Erfahren Sie mehr über Q-Bab Burger in Adelzhausen. Seit Jahren servieren wir die besten Burger, Kebabs und Döner mit frischen Zutaten und Leidenschaft. Besuchen Sie uns!' :
    ($lang == 'en' ? 
    'Learn more about Q-Bab Burger in Adelzhausen. For years, we have been serving the best burgers, kebabs and döner with fresh ingredients and passion. Visit us!' :
    'Adelzhausen\'da Q-Bab Burger hakkında daha fazla bilgi edinin. Yıllardır taze malzemeler ve tutkuyla en iyi burgerleri, kebapları sunuyoruz. Bizi ziyaret edin!');

$page_keywords = $lang == 'de' ?
    'Q-Bab Burger Adelzhausen, Burger Restaurant, Kebab Adelzhausen, Döner Adelzhausen, Beste Burger Bayern, Gourmet Burger, Frische Zutaten, Restaurant Adelzhausen' :
    ($lang == 'en' ?
    'Q-Bab Burger Adelzhausen, Burger Restaurant, Kebab Adelzhausen, Döner Adelzhausen, Best Burgers Bavaria, Gourmet Burger, Fresh Ingredients, Restaurant Adelzhausen' :
    'Q-Bab Burger Adelzhausen, Burger Restoranı, Kebap Adelzhausen, Döner Adelzhausen, En İyi Burgerler, Gurme Burger, Taze Malzemeler');
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($page_keywords); ?>">
    <meta name="author" content="Q-Bab Burger Adelzhausen">
    <meta name="robots" content="index, follow">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo SITE_URL; ?>/about.php">
    <meta property="og:title" content="<?php echo htmlspecialchars($page_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($page_description); ?>">
    <meta property="og:image" content="<?php echo ASSETS_URL; ?>/images/gourmet-burger.jpg">
    
    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?php echo SITE_URL; ?>/about.php">
    <meta property="twitter:title" content="<?php echo htmlspecialchars($page_title); ?>">
    <meta property="twitter:description" content="<?php echo htmlspecialchars($page_description); ?>">
    <meta property="twitter:image" content="<?php echo ASSETS_URL; ?>/images/gourmet-burger.jpg">
    
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/navbar.css?v=<?php echo ASSET_VERSION; ?>">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/auth-modal.css?v=<?php echo ASSET_VERSION; ?>">
    
    <!-- Structured Data for SEO -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Restaurant",
        "name": "Q-Bab Burger",
        "image": "<?php echo ASSETS_URL; ?>/images/gourmet-burger.jpg",
        "description": "<?php echo htmlspecialchars($page_description); ?>",
        "address": {
            "@type": "PostalAddress",
            "streetAddress": "Hauptstraße 123",
            "addressLocality": "Adelzhausen",
            "postalCode": "86477",
            "addressCountry": "DE"
        },
        "telephone": "<?php echo ADMIN_PHONE; ?>",
        "email": "<?php echo ADMIN_EMAIL; ?>",
        "servesCuisine": ["Burger", "Kebab", "Döner", "Fast Food"],
        "priceRange": "€€",
        "openingHoursSpecification": [
            {
                "@type": "OpeningHoursSpecification",
                "dayOfWeek": ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday"],
                "opens": "11:00",
                "closes": "22:00"
            },
            {
                "@type": "OpeningHoursSpecification",
                "dayOfWeek": ["Saturday", "Sunday"],
                "opens": "12:00",
                "closes": "23:00"
            }
        ]
    }
    </script>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background: #0a0a0a;
            color: #fff;
            overflow-x: hidden;
        }

        /* Navbar handled by includes/navbar.php and navbar.css */

        /* Hero Section */
        .about-hero {
            margin-top: 80px;
            padding: 100px 40px;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .about-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('<?php echo ASSETS_URL; ?>/images/gourmet-burger.jpg') center/cover;
            opacity: 0.15;
            z-index: 0;
        }

        .about-hero-content {
            position: relative;
            z-index: 1;
            max-width: 1000px;
            margin: 0 auto;
        }

        .about-hero h1 {
            font-family: 'Bebas Neue', cursive;
            font-size: 4rem;
            color: #f9a825;
            margin-bottom: 1rem;
            letter-spacing: 3px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .about-hero p {
            font-size: 1.3rem;
            color: #ddd;
            line-height: 1.8;
            max-width: 800px;
            margin: 0 auto;
        }

        /* Main Content Section */
        .about-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 80px 40px;
        }

        .about-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
            margin-bottom: 80px;
        }

        .about-image {
            width: 100%;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(249, 168, 37, 0.3);
            transform: rotate(-2deg);
            transition: transform 0.3s;
        }

        .about-image:hover {
            transform: rotate(0deg) scale(1.02);
        }

        .about-image img {
            width: 100%;
            height: auto;
            display: block;
        }

        .about-text h2 {
            font-family: 'Bebas Neue', cursive;
            font-size: 3rem;
            color: #f9a825;
            margin-bottom: 1.5rem;
            letter-spacing: 2px;
        }

        .about-text p {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #ccc;
            margin-bottom: 1.5rem;
        }

        .about-text strong {
            color: #f9a825;
        }

        /* Info Cards */
        .info-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
            margin: 80px 0;
        }

        .info-card {
            background: rgba(249, 168, 37, 0.1);
            border: 2px solid #f9a825;
            border-radius: 15px;
            padding: 40px 30px;
            text-align: center;
            transition: all 0.3s;
        }

        .info-card:hover {
            transform: translateY(-10px);
            background: rgba(249, 168, 37, 0.2);
            box-shadow: 0 15px 40px rgba(249, 168, 37, 0.3);
        }

        .info-card-icon {
            margin-bottom: 1.5rem;
        }

        .info-card-icon svg {
            transition: all 0.3s;
        }

        .info-card:hover .info-card-icon svg {
            transform: scale(1.1);
            filter: drop-shadow(0 5px 15px rgba(249, 168, 37, 0.5));
        }

        .info-card h3 {
            font-family: 'Bebas Neue', cursive;
            font-size: 1.8rem;
            color: #f9a825;
            margin-bottom: 1rem;
            letter-spacing: 1px;
        }

        .info-card p {
            color: #ddd;
            line-height: 1.6;
        }

        .info-card a {
            color: #f9a825;
            text-decoration: none;
            transition: color 0.3s;
        }

        .info-card a:hover {
            color: #fff;
        }

        /* Values Section */
        .values-section {
            background: linear-gradient(135deg, #f9a825 0%, #f89628 100%);
            padding: 80px 40px;
            margin: 80px 0;
        }

        .values-section h2 {
            font-family: 'Bebas Neue', cursive;
            font-size: 3.5rem;
            color: #000;
            text-align: center;
            margin-bottom: 3rem;
            letter-spacing: 2px;
        }

        .values-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 30px;
            max-width: 1400px;
            margin: 0 auto;
        }

        .value-item {
            text-align: center;
            padding: 30px 20px;
            background: rgba(0, 0, 0, 0.1);
            border-radius: 15px;
            transition: all 0.3s;
        }

        .value-item:hover {
            background: rgba(0, 0, 0, 0.2);
            transform: translateY(-5px);
        }

        .value-icon {
            margin-bottom: 1rem;
        }

        .value-icon svg {
            transition: all 0.3s;
        }

        .value-item:hover .value-icon svg {
            transform: scale(1.1) rotate(5deg);
        }

        .value-item h3 {
            font-family: 'Bebas Neue', cursive;
            font-size: 1.8rem;
            color: #000;
            margin-bottom: 0.5rem;
            letter-spacing: 1px;
        }

        .value-item p {
            color: #1a1a1a;
            font-size: 1rem;
        }

        /* CTA Section */
        .cta-section {
            text-align: center;
            padding: 80px 40px;
            background: #1a1a1a;
        }

        .cta-section h2 {
            font-family: 'Bebas Neue', cursive;
            font-size: 3rem;
            color: #f9a825;
            margin-bottom: 2rem;
            letter-spacing: 2px;
        }

        .cta-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .cta-btn {
            display: inline-block;
            padding: 15px 40px;
            font-family: 'Bebas Neue', cursive;
            font-size: 1.5rem;
            text-decoration: none;
            border-radius: 50px;
            transition: all 0.3s;
            letter-spacing: 1px;
        }

        .cta-btn-primary {
            background: #f9a825;
            color: #000;
        }

        .cta-btn-primary:hover {
            background: #fff;
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(249, 168, 37, 0.4);
        }

        .cta-btn-secondary {
            background: transparent;
            color: #f9a825;
            border: 2px solid #f9a825;
        }

        .cta-btn-secondary:hover {
            background: #f9a825;
            color: #000;
            transform: translateY(-3px);
        }

        /* Mobile Menu Button */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: #f9a825;
            font-size: 2rem;
            cursor: pointer;
        }

        /* Responsive */
        @media (max-width: 968px) {
            .nav-menu {
                display: none;
            }

            .mobile-menu-btn {
                display: block;
            }

            .about-hero {
                padding: 60px 20px;
            }

            .about-hero h1 {
                font-size: 2.5rem;
            }

            .about-hero p {
                font-size: 1.1rem;
            }

            .about-grid {
                grid-template-columns: 1fr;
                gap: 40px;
            }

            .about-text h2 {
                font-size: 2.2rem;
            }

            .info-cards {
                grid-template-columns: 1fr;
            }

            .values-grid {
                grid-template-columns: 1fr 1fr;
                gap: 20px;
            }

            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }

            .cta-btn {
                width: 100%;
                max-width: 300px;
            }
        }

        @media (max-width: 568px) {
            .values-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Footer Styles */
        .footer {
            background: #0a0a0a;
            color: #fff;
            padding: 60px 40px 30px;
        }

        .footer-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 40px;
            margin-bottom: 40px;
        }

        .footer-left h2 {
            font-family: 'Bebas Neue', cursive;
            font-size: 2rem;
            color: #f9a825;
            margin-bottom: 1rem;
        }

        .footer-middle h3,
        .footer-right h3 {
            font-family: 'Bebas Neue', cursive;
            font-size: 1.5rem;
            color: #f9a825;
            margin-bottom: 1rem;
        }

        .footer-middle p,
        .footer-right p {
            line-height: 1.8;
            color: #ccc;
        }

        .footer-right a {
            color: #f9a825;
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-right a:hover {
            color: #fff;
        }

        .footer-social {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .social-icon {
            width: 40px;
            height: 40px;
            border: 2px solid #f9a825;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .social-icon:hover {
            background: #f9a825;
            transform: translateY(-3px);
        }

        .social-icon svg {
            color: #f9a825;
        }

        .social-icon:hover svg {
            color: #000;
        }

        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 30px;
        }

        .footer-bottom-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .footer-links {
            display: flex;
            gap: 30px;
        }

        .footer-links a {
            color: #ccc;
            text-decoration: none;
            transition: color 0.3s;
        }

        .footer-links a:hover {
            color: #f9a825;
        }

        .footer-copyright p {
            color: #888;
            font-size: 0.9rem;
        }

        .footer-scroll-top {
            width: 40px;
            height: 40px;
            background: #f9a825;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
        }

        .footer-scroll-top:hover {
            background: #fff;
            transform: translateY(-3px);
        }

        .footer-scroll-top svg {
            color: #000;
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

        @media (max-width: 968px) {
            .footer-content {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .footer-social {
                justify-content: center;
            }

            .footer-bottom-content {
                flex-direction: column;
                gap: 20px;
            }

            .footer-links {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Include Navbar -->
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <!-- Hero Section -->
    <section class="about-hero">
        <div class="about-hero-content">
            <h1><?php echo t('nav_about'); ?></h1>
            <p>
                <?php 
                if ($lang == 'de') {
                    echo 'Willkommen bei Q-Bab Burger – Ihrem Premium-Burger-Restaurant in Adelzhausen. Seit Jahren vereinen wir Tradition mit Innovation und servieren die besten Burger, Kebabs und Döner der Region.';
                } elseif ($lang == 'en') {
                    echo 'Welcome to Q-Bab Burger – your premium burger restaurant in Adelzhausen. For years, we have been combining tradition with innovation, serving the best burgers, kebabs and döner in the region.';
                } else {
                    echo 'Q-Bab Burger\'a hoş geldiniz – Adelzhausen\'daki premium burger restoranınız. Yıllardır geleneği yenilikle birleştiriyor, bölgenin en iyi burgerlerini, kebaplarını ve dönerlerini sunuyoruz.';
                }
                ?>
            </p>
        </div>
    </section>

    <!-- Main Content -->
    <section class="about-content">
        <div class="about-grid">
            <div class="about-image">
                <img src="<?php echo ASSETS_URL; ?>/images/gourmet-burger.jpg" alt="Q-Bab Gourmet Burger">
            </div>
            <div class="about-text">
                <h2><?php echo t('gourmet_title'); ?></h2>
                <p>
                    <?php 
                    if ($lang == 'de') {
                        echo 'Bei <strong>Q-Bab Burger</strong> in Adelzhausen steht <strong>Qualität</strong> an erster Stelle. Wir verwenden nur <strong>frische, regionale Zutaten</strong> und bereiten jeden Burger mit <strong>Leidenschaft und Hingabe</strong> zu. Unser erfahrenes Team sorgt dafür, dass jeder Biss ein unvergessliches Erlebnis wird.';
                    } elseif ($lang == 'en') {
                        echo 'At <strong>Q-Bab Burger</strong> in Adelzhausen, <strong>quality</strong> comes first. We only use <strong>fresh, regional ingredients</strong> and prepare every burger with <strong>passion and dedication</strong>. Our experienced team ensures that every bite becomes an unforgettable experience.';
                    } else {
                        echo '<strong>Q-Bab Burger</strong> Adelzhausen\'da <strong>kalite</strong> her şeyden önce gelir. Sadece <strong>taze, bölgesel malzemeler</strong> kullanıyor ve her burgeri <strong>tutku ve özenle</strong> hazırlıyoruz. Deneyimli ekibimiz her lokmanın unutulmaz bir deneyim olmasını sağlıyor.';
                    }
                    ?>
                </p>
                <p>
                    <?php 
                    if ($lang == 'de') {
                        echo 'Von klassischen <strong>Beef Burgern</strong> über saftige <strong>Chicken Burger</strong> bis hin zu authentischen <strong>Kebabs und Döner</strong> – unsere vielfältige Speisekarte bietet für jeden Geschmack etwas. Besuchen Sie uns und überzeugen Sie sich selbst!';
                    } elseif ($lang == 'en') {
                        echo 'From classic <strong>beef burgers</strong> to juicy <strong>chicken burgers</strong> and authentic <strong>kebabs and döner</strong> – our diverse menu offers something for every taste. Visit us and see for yourself!';
                    } else {
                        echo 'Klasik <strong>dana burgerlerden</strong> sulu <strong>tavuk burgerlere</strong> ve otantik <strong>kebap ve dönerlere</strong> kadar – çeşitli menümüz her damak zevkine hitap ediyor. Bizi ziyaret edin ve kendiniz görün!';
                    }
                    ?>
                </p>
            </div>
        </div>

        <!-- Info Cards -->
        <div class="info-cards">
            <div class="info-card">
                <div class="info-card-icon">
                    <svg width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="#f9a825" stroke-width="2">
                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                        <circle cx="12" cy="10" r="3"></circle>
                    </svg>
                </div>
                <h3><?php echo $lang == 'de' ? 'ADRESSE' : ($lang == 'en' ? 'ADDRESS' : 'ADRES'); ?></h3>
                <p>
                    Mühlweg 1<br>
                    86559 Adelzhausen<br>
                    Bayern, Deutschland
                </p>
            </div>
            <div class="info-card">
                <div class="info-card-icon">
                    <svg width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="#f9a825" stroke-width="2">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                    </svg>
                </div>
                <h3><?php echo $lang == 'de' ? 'TELEFON' : ($lang == 'en' ? 'PHONE' : 'TELEFON'); ?></h3>
                <p>
                    <a href="tel:<?php echo ADMIN_PHONE; ?>"><?php echo ADMIN_PHONE; ?></a>
                </p>
            </div>
            <div class="info-card">
                <div class="info-card-icon">
                    <svg width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="#f9a825" stroke-width="2">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                        <polyline points="22,6 12,13 2,6"></polyline>
                    </svg>
                </div>
                <h3><?php echo $lang == 'de' ? 'E-MAIL' : ($lang == 'en' ? 'EMAIL' : 'E-POSTA'); ?></h3>
                <p>
                    <a href="mailto:<?php echo ADMIN_EMAIL; ?>"><?php echo ADMIN_EMAIL; ?></a>
                </p>
            </div>
        </div>
    </section>

    <!-- Values Section -->
    <section class="values-section">
        <h2><?php echo $lang == 'de' ? 'UNSERE WERTE' : ($lang == 'en' ? 'OUR VALUES' : 'DEĞERLERİMİZ'); ?></h2>
        <div class="values-grid">
            <div class="value-item">
                <div class="value-icon">
                    <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="2">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"></path>
                        <path d="M2 17l10 5 10-5M2 12l10 5 10-5"></path>
                    </svg>
                </div>
                <h3><?php echo $lang == 'de' ? 'FRISCHE' : ($lang == 'en' ? 'FRESHNESS' : 'TAZELİK'); ?></h3>
                <p><?php echo $lang == 'de' ? 'Täglich frische Zutaten' : ($lang == 'en' ? 'Fresh ingredients daily' : 'Günlük taze malzemeler'); ?></p>
            </div>
            <div class="value-item">
                <div class="value-icon">
                    <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="2">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>
                    </svg>
                </div>
                <h3><?php echo $lang == 'de' ? 'QUALITÄT' : ($lang == 'en' ? 'QUALITY' : 'KALİTE'); ?></h3>
                <p><?php echo $lang == 'de' ? 'Premium-Zutaten' : ($lang == 'en' ? 'Premium ingredients' : 'Premium malzemeler'); ?></p>
            </div>
            <div class="value-item">
                <div class="value-icon">
                    <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="2">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                    </svg>
                </div>
                <h3><?php echo $lang == 'de' ? 'LEIDENSCHAFT' : ($lang == 'en' ? 'PASSION' : 'TUTKU'); ?></h3>
                <p><?php echo $lang == 'de' ? 'Mit Liebe zubereitet' : ($lang == 'en' ? 'Prepared with love' : 'Sevgiyle hazırlanır'); ?></p>
            </div>
            <div class="value-item">
                <div class="value-icon">
                    <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#000" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
                <h3><?php echo $lang == 'de' ? 'ERFAHRUNG' : ($lang == 'en' ? 'EXPERIENCE' : 'DENEYİM'); ?></h3>
                <p><?php echo $lang == 'de' ? 'Jahrelange Expertise' : ($lang == 'en' ? 'Years of expertise' : 'Yıllarca deneyim'); ?></p>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <h2><?php echo $lang == 'de' ? 'BESUCHEN SIE UNS HEUTE!' : ($lang == 'en' ? 'VISIT US TODAY!' : 'BİZİ BUGÜN ZİYARET EDİN!'); ?></h2>
        <div class="cta-buttons">
            <a href="<?php echo SITE_URL; ?>/menu.php" class="cta-btn cta-btn-primary">
                <?php echo t('nav_order'); ?>
            </a>
            <a href="<?php echo SITE_URL; ?>/contact" class="cta-btn cta-btn-secondary">
                <?php echo t('nav_contact'); ?>
            </a>
        </div>
    </section>

    <?php include_once 'includes/login-popup.php'; ?>
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

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
</body>
</html>
