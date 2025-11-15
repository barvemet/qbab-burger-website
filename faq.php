<?php
/**
 * FAQ Page - Häufige Fragen / Frequently Asked Questions
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle language change
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

// Page metadata
$page_title = $lang == 'de' ? 'Häufig gestellte Fragen - Q-Bab Burger' : 
              ($lang == 'en' ? 'Frequently Asked Questions - Q-Bab Burger' : 
              'Sıkça Sorulan Sorular - Q-Bab Burger');
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
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

        /* Hero Section */
        .faq-hero {
            margin-top: 80px;
            padding: 100px 40px 60px;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
            text-align: center;
        }

        .faq-hero h1 {
            font-family: 'Bebas Neue', cursive;
            font-size: 4rem;
            color: #f9a825;
            margin-bottom: 1rem;
            letter-spacing: 3px;
        }

        .faq-hero p {
            font-size: 1.2rem;
            color: #ddd;
        }

        /* FAQ Content */
        .faq-content {
            max-width: 1000px;
            margin: 80px auto;
            padding: 0 40px;
        }

        .faq-item {
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(249, 168, 37, 0.3);
            border-radius: 15px;
            margin-bottom: 20px;
            overflow: hidden;
            transition: all 0.3s;
        }

        .faq-item:hover {
            border-color: #f9a825;
            box-shadow: 0 5px 20px rgba(249, 168, 37, 0.2);
        }

        .faq-question {
            padding: 25px 30px;
            font-family: 'Bebas Neue', cursive;
            font-size: 1.5rem;
            color: #f9a825;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            letter-spacing: 1px;
        }

        .faq-question::after {
            content: '+';
            font-size: 2rem;
            transition: transform 0.3s;
        }

        .faq-question.active::after {
            content: '−';
        }

        .faq-answer {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            padding: 0 30px;
        }

        .faq-answer.active {
            max-height: 500px;
            padding: 0 30px 25px;
        }

        .faq-answer p {
            color: #ccc;
            line-height: 1.8;
            font-size: 1.1rem;
        }

        /* CTA Section */
        .faq-cta {
            text-align: center;
            padding: 80px 40px;
            background: #1a1a1a;
        }

        .faq-cta h2 {
            font-family: 'Bebas Neue', cursive;
            font-size: 3rem;
            color: #f9a825;
            margin-bottom: 2rem;
        }

        .faq-cta p {
            font-size: 1.2rem;
            color: #ddd;
            margin-bottom: 2rem;
        }

        .cta-btn {
            display: inline-block;
            padding: 15px 40px;
            background: #f9a825;
            color: #000;
            text-decoration: none;
            font-family: 'Bebas Neue', cursive;
            font-size: 1.5rem;
            border-radius: 50px;
            transition: all 0.3s;
            letter-spacing: 1px;
        }

        .cta-btn:hover {
            background: #fff;
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(249, 168, 37, 0.4);
        }

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

            .faq-hero {
                padding: 60px 20px 40px;
            }

            .faq-hero h1 {
                font-size: 2.5rem;
            }

            .faq-content {
                padding: 0 20px;
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
    <!-- Include Navbar -->
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <!-- Hero Section -->
    <section class="faq-hero">
        <h1><?php echo t('nav_faq'); ?></h1>
        <p>
            <?php 
            if ($lang == 'de') {
                echo 'Hier finden Sie Antworten auf die häufigsten Fragen zu Q-Bab Burger.';
            } elseif ($lang == 'en') {
                echo 'Find answers to the most frequently asked questions about Q-Bab Burger.';
            } else {
                echo 'Q-Bab Burger hakkında en sık sorulan soruların cevaplarını burada bulabilirsiniz.';
            }
            ?>
        </p>
    </section>

    <!-- FAQ Content -->
    <section class="faq-content">
        <!-- FAQ 1 -->
        <div class="faq-item">
            <div class="faq-question">
                <?php 
                if ($lang == 'de') {
                    echo 'Was sind Ihre Öffnungszeiten?';
                } elseif ($lang == 'en') {
                    echo 'What are your opening hours?';
                } else {
                    echo 'Açılış saatleriniz nedir?';
                }
                ?>
            </div>
            <div class="faq-answer">
                <p>
                    <?php 
                    if ($lang == 'de') {
                        echo 'Wir haben Montag bis Sonntag von 11:00 bis 22:00 Uhr geöffnet.';
                    } elseif ($lang == 'en') {
                        echo 'We are open Monday to Sunday from 11:00 AM to 10:00 PM.';
                    } else {
                        echo 'Pazartesi\'den Pazar\'a 11:00 - 22:00 saatleri arasında açığız.';
                    }
                    ?>
                </p>
            </div>
        </div>

        <!-- FAQ 2 -->
        <div class="faq-item">
            <div class="faq-question">
                <?php 
                if ($lang == 'de') {
                    echo 'Bieten Sie Lieferung an?';
                } elseif ($lang == 'en') {
                    echo 'Do you offer delivery?';
                } else {
                    echo 'Teslimat yapıyor musunuz?';
                }
                ?>
            </div>
            <div class="faq-answer">
                <p>
                    <?php 
                    if ($lang == 'de') {
                        echo 'Ja, wir bieten Lieferung innerhalb von Adelzhausen und Umgebung an. Bestellen Sie einfach online!';
                    } elseif ($lang == 'en') {
                        echo 'Yes, we offer delivery within Adelzhausen and surrounding areas. Just order online!';
                    } else {
                        echo 'Evet, Adelzhausen ve çevresine teslimat yapıyoruz. Online sipariş verebilirsiniz!';
                    }
                    ?>
                </p>
            </div>
        </div>

        <!-- FAQ 3 -->
        <div class="faq-item">
            <div class="faq-question">
                <?php 
                if ($lang == 'de') {
                    echo 'Haben Sie vegetarische/vegane Optionen?';
                } elseif ($lang == 'en') {
                    echo 'Do you have vegetarian/vegan options?';
                } else {
                    echo 'Vejetaryen/vegan seçenekleriniz var mı?';
                }
                ?>
            </div>
            <div class="faq-answer">
                <p>
                    <?php 
                    if ($lang == 'de') {
                        echo 'Ja, wir haben mehrere vegetarische und vegane Burger-Optionen in unserem Menü.';
                    } elseif ($lang == 'en') {
                        echo 'Yes, we have several vegetarian and vegan burger options in our menu.';
                    } else {
                        echo 'Evet, menümüzde çeşitli vejetaryen ve vegan burger seçeneklerimiz bulunmaktadır.';
                    }
                    ?>
                </p>
            </div>
        </div>

        <!-- FAQ 4 -->
        <div class="faq-item">
            <div class="faq-question">
                <?php 
                if ($lang == 'de') {
                    echo 'Welche Zahlungsmethoden akzeptieren Sie?';
                } elseif ($lang == 'en') {
                    echo 'What payment methods do you accept?';
                } else {
                    echo 'Hangi ödeme yöntemlerini kabul ediyorsunuz?';
                }
                ?>
            </div>
            <div class="faq-answer">
                <p>
                    <?php 
                    if ($lang == 'de') {
                        echo 'Wir akzeptieren Barzahlung, Kreditkarten, PayPal, Google Pay, Apple Pay und Klarna.';
                    } elseif ($lang == 'en') {
                        echo 'We accept cash, credit cards, PayPal, Google Pay, Apple Pay, and Klarna.';
                    } else {
                        echo 'Nakit, kredi kartı, PayPal, Google Pay, Apple Pay ve Klarna kabul ediyoruz.';
                    }
                    ?>
                </p>
            </div>
        </div>

        <!-- FAQ 5 -->
        <div class="faq-item">
            <div class="faq-question">
                <?php 
                if ($lang == 'de') {
                    echo 'Wie lange dauert die Lieferung?';
                } elseif ($lang == 'en') {
                    echo 'How long does delivery take?';
                } else {
                    echo 'Teslimat ne kadar sürer?';
                }
                ?>
            </div>
            <div class="faq-answer">
                <p>
                    <?php 
                    if ($lang == 'de') {
                        echo 'Die typische Lieferzeit beträgt 30-45 Minuten, abhängig von Ihrem Standort und unserer aktuellen Auslastung.';
                    } elseif ($lang == 'en') {
                        echo 'Typical delivery time is 30-45 minutes, depending on your location and our current workload.';
                    } else {
                        echo 'Teslimat süresi genellikle 30-45 dakikadır, lokasyonunuza ve iş yoğunluğumuza bağlıdır.';
                    }
                    ?>
                </p>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="faq-cta">
        <h2>
            <?php 
            if ($lang == 'de') {
                echo 'Weitere Fragen?';
            } elseif ($lang == 'en') {
                echo 'More Questions?';
            } else {
                echo 'Başka Sorularınız mı Var?';
            }
            ?>
        </h2>
        <p>
            <?php 
            if ($lang == 'de') {
                echo 'Kontaktieren Sie uns direkt – wir helfen Ihnen gerne weiter!';
            } elseif ($lang == 'en') {
                echo 'Contact us directly – we\'re happy to help!';
            } else {
                echo 'Doğrudan bize ulaşın – size yardımcı olmaktan memnuniyet duyarız!';
            }
            ?>
        </p>
        <a href="contact.php" class="cta-btn">
            <?php echo t('nav_contact'); ?>
        </a>
    </section>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- Scripts -->
    <script src="<?php echo ASSETS_URL; ?>/js/auth-modal.js?v=<?php echo ASSET_VERSION; ?>"></script>
    <script src="<?php echo ASSETS_URL; ?>/js/cart.js?v=<?php echo ASSET_VERSION; ?>"></script>

    <!-- FAQ Accordion Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const faqQuestions = document.querySelectorAll('.faq-question');
            
            faqQuestions.forEach(question => {
                question.addEventListener('click', function() {
                    // Close all other FAQs
                    faqQuestions.forEach(q => {
                        if (q !== this) {
                            q.classList.remove('active');
                            q.nextElementSibling.classList.remove('active');
                        }
                    });
                    
                    // Toggle current FAQ
                    this.classList.toggle('active');
                    this.nextElementSibling.classList.toggle('active');
                });
            });
        });
    </script>

    <!-- Navbar scroll effect -->
    <script>
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

