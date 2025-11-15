<?php
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
$lang = getCurrentLanguage();

// Page metadata for SEO
$page_title = $lang == 'de' ? 'Kontakt - Q-Bab Burger Adelzhausen | Kontaktieren Sie uns' : 
              ($lang == 'en' ? 'Contact - Q-Bab Burger Adelzhausen | Get in Touch' : 
              'İletişim - Q-Bab Burger Adelzhausen | Bize Ulaşın');

$page_description = $lang == 'de' ? 
    'Kontaktieren Sie Q-Bab Burger in Adelzhausen. Wir freuen uns auf Ihre Nachricht! Telefon, E-Mail und Anfahrt.' :
    ($lang == 'en' ? 
    'Contact Q-Bab Burger in Adelzhausen. We look forward to hearing from you! Phone, email and directions.' :
    'Adelzhausen\'da Q-Bab Burger ile iletişime geçin. Mesajınızı bekliyoruz! Telefon, e-posta ve adres.');
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
    <meta name="robots" content="index, follow">
    
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <!-- Styles -->
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/navbar.css?v=<?php echo ASSET_VERSION; ?>">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/auth-modal.css?v=<?php echo ASSET_VERSION; ?>">
    
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

        /* Navbar styles moved to navbar.css */

        /* Hero Section */
        .contact-hero {
            margin-top: 80px;
            padding: 100px 40px 60px;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .contact-hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('<?php echo ASSETS_URL; ?>/images/gourmet-burger-horizontal.jpg') center/cover;
            opacity: 0.1;
            z-index: 0;
        }

        .contact-hero-content {
            position: relative;
            z-index: 1;
            max-width: 800px;
            margin: 0 auto;
        }

        .contact-hero h1 {
            font-family: 'Bebas Neue', cursive;
            font-size: 4rem;
            color: #f9a825;
            margin-bottom: 1rem;
            letter-spacing: 3px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .contact-hero p {
            font-size: 1.2rem;
            color: #ddd;
            line-height: 1.6;
        }

        /* Main Content */
        .contact-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 80px 40px;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: start;
        }

        /* Contact Info Cards */
        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }

        .info-box {
            background: rgba(249, 168, 37, 0.1);
            border: 2px solid #f9a825;
            border-radius: 15px;
            padding: 30px;
            transition: all 0.3s;
        }

        .info-box:hover {
            transform: translateY(-5px);
            background: rgba(249, 168, 37, 0.15);
            box-shadow: 0 10px 30px rgba(249, 168, 37, 0.3);
        }

        .info-box-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 15px;
        }

        .info-icon {
            width: 60px;
            height: 60px;
            background: #f9a825;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .info-icon svg {
            width: 30px;
            height: 30px;
            stroke: #000;
        }

        .info-box h3 {
            font-family: 'Bebas Neue', cursive;
            font-size: 1.8rem;
            color: #f9a825;
            letter-spacing: 1px;
        }

        .info-box p {
            color: #ccc;
            line-height: 1.8;
            font-size: 1.1rem;
        }

        .info-box a {
            color: #f9a825;
            text-decoration: none;
            transition: color 0.3s;
        }

        .info-box a:hover {
            color: #fff;
        }

        /* Contact Form */
        .contact-form {
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(249, 168, 37, 0.3);
            border-radius: 20px;
            padding: 40px;
            backdrop-filter: blur(10px);
        }

        .contact-form h2 {
            font-family: 'Bebas Neue', cursive;
            font-size: 2.5rem;
            color: #f9a825;
            margin-bottom: 2rem;
            letter-spacing: 2px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #f9a825;
            font-weight: 500;
            font-size: 1rem;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 15px;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(249, 168, 37, 0.3);
            border-radius: 10px;
            color: #fff;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #f9a825;
            background: rgba(255, 255, 255, 0.15);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 150px;
            font-family: 'Roboto', sans-serif;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .submit-btn {
            width: 100%;
            padding: 15px;
            background: #f9a825;
            color: #000;
            border: none;
            border-radius: 50px;
            font-family: 'Bebas Neue', cursive;
            font-size: 1.5rem;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .submit-btn:hover {
            background: #fff;
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(249, 168, 37, 0.4);
        }

        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .form-message {
            margin-top: 20px;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            display: none;
        }

        .form-message.success {
            background: rgba(76, 175, 80, 0.2);
            border: 2px solid #4CAF50;
            color: #4CAF50;
        }

        .form-message.error {
            background: rgba(244, 67, 54, 0.2);
            border: 2px solid #f44336;
            color: #f44336;
        }

        /* Map Section */
        .map-section {
            margin-top: 80px;
            padding: 60px 40px;
            background: #1a1a1a;
        }

        .map-container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .map-container h2 {
            font-family: 'Bebas Neue', cursive;
            font-size: 3rem;
            color: #f9a825;
            text-align: center;
            margin-bottom: 3rem;
            letter-spacing: 2px;
        }

        .map-frame {
            width: 100%;
            height: 450px;
            border-radius: 20px;
            overflow: hidden;
            border: 3px solid #f9a825;
        }

        .map-frame iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        /* Opening Hours */
        .hours-section {
            background: linear-gradient(135deg, #f9a825 0%, #f89628 100%);
            padding: 60px 40px;
            margin-top: 80px;
        }

        .hours-container {
            max-width: 1000px;
            margin: 0 auto;
            text-align: center;
        }

        .hours-container h2 {
            font-family: 'Bebas Neue', cursive;
            font-size: 3rem;
            color: #000;
            margin-bottom: 3rem;
            letter-spacing: 2px;
        }

        .hours-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            max-width: 600px;
            margin: 0 auto;
        }

        .hour-item {
            background: rgba(0, 0, 0, 0.1);
            padding: 20px;
            border-radius: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .hour-day {
            font-family: 'Bebas Neue', cursive;
            font-size: 1.5rem;
            color: #000;
        }

        .hour-time {
            font-weight: 500;
            color: #1a1a1a;
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
        @media (max-width: 968px) {
            .nav-menu {
                display: none;
            }

            .mobile-menu-btn {
                display: block;
            }

            .contact-hero {
                padding: 60px 20px 40px;
            }

            .contact-hero h1 {
                font-size: 2.5rem;
            }

            .contact-grid {
                grid-template-columns: 1fr;
                gap: 40px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .hours-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Footer Styles */
        .footer {
            background: #0a0a0a;
            padding: 60px 40px 30px;
            color: #fff;
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

        .footer-left h2,
        .footer-middle h3,
        .footer-right h3 {
            font-family: 'Bebas Neue', cursive;
            color: #f9a825;
            margin-bottom: 1rem;
            font-size: 1.8rem;
            letter-spacing: 1px;
        }

        .footer-middle p,
        .footer-right p {
            color: #ccc;
            line-height: 1.8;
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
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border: 2px solid #f9a825;
            border-radius: 50%;
            color: #f9a825;
            transition: all 0.3s;
        }

        .social-icon:hover {
            background: #f9a825;
            color: #000;
            transform: translateY(-3px);
        }

        .social-icon svg {
            stroke: currentColor;
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

        .footer-copyright {
            text-align: center;
        }

        .footer-copyright p {
            color: #888;
            font-size: 0.9rem;
        }

        .footer-scroll-top {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: #f9a825;
            border-radius: 50%;
            color: #000;
            transition: all 0.3s;
        }

        .footer-scroll-top:hover {
            background: #fff;
            transform: translateY(-3px);
        }

        .footer-scroll-top svg {
            stroke: currentColor;
        }

        /* Cart Popup Styles */
        .cart-popup {
            display: none;
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
    <section class="contact-hero">
        <div class="contact-hero-content">
            <h1><?php echo t('nav_contact'); ?></h1>
            <p>
                <?php 
                if ($lang == 'de') {
                    echo 'Haben Sie Fragen oder möchten Sie eine Reservierung vornehmen? Wir freuen uns auf Ihre Nachricht!';
                } elseif ($lang == 'en') {
                    echo 'Do you have questions or would you like to make a reservation? We look forward to hearing from you!';
                } else {
                    echo 'Sorularınız mı var veya rezervasyon yapmak mı istiyorsunuz? Mesajınızı bekliyoruz!';
                }
                ?>
            </p>
        </div>
    </section>

    <!-- Main Content -->
    <section class="contact-content">
        <div class="contact-grid">
            <!-- Contact Info -->
            <div class="contact-info">
                <!-- Address -->
                <div class="info-box">
                    <div class="info-box-header">
                        <div class="info-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                        </div>
                        <h3><?php echo $lang == 'de' ? 'Adresse' : ($lang == 'en' ? 'Address' : 'Adres'); ?></h3>
                    </div>
                    <p>
                        Mühlweg 1<br>
                        86559 Adelzhausen<br>
                        Bayern, Deutschland
                    </p>
                </div>

                <!-- Phone -->
                <div class="info-box">
                    <div class="info-box-header">
                        <div class="info-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke-width="2">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                            </svg>
                        </div>
                        <h3><?php echo $lang == 'de' ? 'Telefon' : ($lang == 'en' ? 'Phone' : 'Telefon'); ?></h3>
                    </div>
                    <p>
                        <a href="tel:<?php echo ADMIN_PHONE; ?>"><?php echo ADMIN_PHONE; ?></a>
                    </p>
                </div>

                <!-- Email -->
                <div class="info-box">
                    <div class="info-box-header">
                        <div class="info-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path>
                                <polyline points="22,6 12,13 2,6"></polyline>
                            </svg>
                        </div>
                        <h3><?php echo $lang == 'de' ? 'E-Mail' : ($lang == 'en' ? 'Email' : 'E-Posta'); ?></h3>
                    </div>
                    <p>
                        <a href="mailto:<?php echo ADMIN_EMAIL; ?>"><?php echo ADMIN_EMAIL; ?></a>
                    </p>
                </div>
            </div>

            <!-- Contact Form -->
            <div class="contact-form">
                <h2><?php echo $lang == 'de' ? 'Nachricht Senden' : ($lang == 'en' ? 'Send Message' : 'Mesaj Gönder'); ?></h2>
                <form id="contactForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <div class="form-row">
                        <div class="form-group">
                            <label><?php echo $lang == 'de' ? 'Vorname' : ($lang == 'en' ? 'First Name' : 'Ad'); ?> *</label>
                            <input type="text" name="first_name" required>
                        </div>
                        <div class="form-group">
                            <label><?php echo $lang == 'de' ? 'Nachname' : ($lang == 'en' ? 'Last Name' : 'Soyad'); ?> *</label>
                            <input type="text" name="last_name" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label><?php echo $lang == 'de' ? 'E-Mail' : ($lang == 'en' ? 'Email' : 'E-Posta'); ?> *</label>
                        <input type="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label><?php echo $lang == 'de' ? 'Telefon' : ($lang == 'en' ? 'Phone' : 'Telefon'); ?></label>
                        <input type="tel" name="phone">
                    </div>
                    <div class="form-group">
                        <label><?php echo $lang == 'de' ? 'Betreff' : ($lang == 'en' ? 'Subject' : 'Konu'); ?> *</label>
                        <input type="text" name="subject" required>
                    </div>
                    <div class="form-group">
                        <label><?php echo $lang == 'de' ? 'Nachricht' : ($lang == 'en' ? 'Message' : 'Mesaj'); ?> *</label>
                        <textarea name="message" required></textarea>
                    </div>
                    <button type="submit" class="submit-btn">
                        <?php echo $lang == 'de' ? 'Senden' : ($lang == 'en' ? 'Send' : 'Gönder'); ?>
                    </button>
                    <div class="form-message" id="formMessage"></div>
                </form>
            </div>
        </div>
    </section>

    <!-- Opening Hours -->
    <section class="hours-section">
        <div class="hours-container">
            <h2><?php echo $lang == 'de' ? 'Öffnungszeiten' : ($lang == 'en' ? 'Opening Hours' : 'Açılış Saatleri'); ?></h2>
            <div class="hours-grid">
                <div class="hour-item">
                    <span class="hour-day"><?php echo $lang == 'de' ? 'Montag - Freitag' : ($lang == 'en' ? 'Monday - Friday' : 'Pazartesi - Cuma'); ?></span>
                    <span class="hour-time">11:00 - 22:00</span>
                </div>
                <div class="hour-item">
                    <span class="hour-day"><?php echo $lang == 'de' ? 'Samstag - Sonntag' : ($lang == 'en' ? 'Saturday - Sunday' : 'Cumartesi - Pazar'); ?></span>
                    <span class="hour-time">12:00 - 23:00</span>
                </div>
            </div>
        </div>
    </section>

    <!-- Map Section -->
    <section class="map-section">
        <div class="map-container">
            <h2><?php echo $lang == 'de' ? 'Finden Sie Uns' : ($lang == 'en' ? 'Find Us' : 'Bizi Bulun'); ?></h2>
            <div class="map-frame">
                <iframe 
                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2658.8!2d11.1!3d48.5!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zNDjCsDMwJzAwLjAiTiAxMcKwMDYnMDAuMCJF!5e0!3m2!1sde!2sde!4v1234567890"
                    allowfullscreen="" 
                    loading="lazy" 
                    referrerpolicy="no-referrer-when-downgrade">
                </iframe>
            </div>
        </div>
    </section>

    <?php include_once 'includes/login-popup.php'; ?>
    <script src="<?php echo ASSETS_URL; ?>/js/auth-modal.js?v=<?php echo ASSET_VERSION; ?>"></script>
    <script src="<?php echo ASSETS_URL; ?>/js/cart.js?v=<?php echo ASSET_VERSION; ?>"></script>

    <!-- Contact Form Handler -->
    <script>
        document.getElementById('contactForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('.submit-btn');
            const formMessage = document.getElementById('formMessage');
            const formData = new FormData(this);
            
            submitBtn.disabled = true;
            submitBtn.textContent = '<?php echo $lang == "de" ? "Wird gesendet..." : ($lang == "en" ? "Sending..." : "Gönderiliyor..."); ?>';
            
            try {
                const response = await fetch('<?php echo SITE_URL; ?>/api/contact.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    formMessage.className = 'form-message success';
                    formMessage.textContent = '<?php echo $lang == "de" ? "Nachricht erfolgreich gesendet!" : ($lang == "en" ? "Message sent successfully!" : "Mesaj başarıyla gönderildi!"); ?>';
                    this.reset();
                } else {
                    formMessage.className = 'form-message error';
                    formMessage.textContent = data.message || '<?php echo $lang == "de" ? "Fehler beim Senden" : ($lang == "en" ? "Error sending message" : "Mesaj gönderme hatası"); ?>';
                }
            } catch (error) {
                formMessage.className = 'form-message error';
                formMessage.textContent = '<?php echo $lang == "de" ? "Netzwerkfehler" : ($lang == "en" ? "Network error" : "Ağ hatası"); ?>';
            }
            
            formMessage.style.display = 'block';
            submitBtn.disabled = false;
            submitBtn.textContent = '<?php echo $lang == "de" ? "Senden" : ($lang == "en" ? "Send" : "Gönder"); ?>';
            
            setTimeout(() => {
                formMessage.style.display = 'none';
            }, 5000);
        });
    </script>

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
