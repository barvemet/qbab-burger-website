<?php
/**
 * Q-Bab Burger - Menu Page
 * Restaurant-style list menu (No Images)
 */

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
$current_lang = getCurrentLanguage();

// Set page title
$page_title = t('nav_menu');

// Get database connection
$db = getDBConnection();

// Fetch categories that have active items (only non-empty categories)
try {
    $stmt = $db->prepare("
        SELECT DISTINCT c.id, c.name_{$current_lang} as category_name, c.display_order
        FROM menu_categories c
        INNER JOIN menu_items m ON c.id = m.category_id
        WHERE m.is_active = 1 AND c.is_active = 1
        ORDER BY c.display_order
    ");
    $stmt->execute();
    $categories = $stmt->fetchAll();
} catch (Exception $e) {
    $categories = [];
}

// Fetch active menu items
try {
    $stmt = $db->prepare("
        SELECT m.*, c.name_{$current_lang} as category_name, c.id as category_id_ref
        FROM menu_items m
        JOIN menu_categories c ON m.category_id = c.id
        WHERE m.is_active = 1 AND c.is_active = 1
        ORDER BY c.display_order, m.display_order, m.id
    ");
    $stmt->execute();
    $menu_items = $stmt->fetchAll();
} catch (Exception $e) {
    $menu_items = [];
}

// Group items by category
$items_by_category = [];
foreach ($menu_items as $item) {
    $category_id = $item['category_id'];
    if (!isset($items_by_category[$category_id])) {
        $items_by_category[$category_id] = [];
    }
    $items_by_category[$category_id][] = $item;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo t('nav_menu'); ?> - Q-Bab Burger</title>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/navbar.css?v=<?php echo ASSET_VERSION; ?>">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/auth-modal.css?v=<?php echo ASSET_VERSION; ?>">
    <link rel="stylesheet" href="<?php echo ASSETS_URL; ?>/css/product-modal.css?v=<?php echo ASSET_VERSION; ?>">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f9a825;
            font-family: Arial, sans-serif;
            color: #1a1a1a;
        }

        /* Navbar styles moved to navbar.css */

        /* Menu Header */
        .menu-header {
            text-align: center;
            padding: 150px 40px 80px;
        }

        .menu-header-subtitle {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.2rem;
            letter-spacing: 3px;
            margin-bottom: 20px;
            text-transform: uppercase;
        }

        .menu-header-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 3.5rem;
            letter-spacing: 2px;
            line-height: 1.2;
            text-transform: uppercase;
            margin-bottom: 40px;
        }

        .menu-header-divider {
            width: 60px;
            height: 3px;
            background: #1a1a1a;
            margin: 0 auto;
        }

        /* Category Filter */
        .category-filter {
            text-align: center;
            padding: 40px 20px;
            background: #f4b400;
        }

        .category-buttons {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 15px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .category-btn {
            padding: 12px 30px;
            background: rgba(26, 26, 26, 0.1);
            border: 2px solid transparent;
            color: #1a1a1a;
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.1rem;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
        }

        .category-btn:hover {
            background: rgba(26, 26, 26, 0.2);
            transform: translateY(-2px);
        }

        .category-btn.active {
            background: #1a1a1a;
            color: #f9a825;
            border-color: #1a1a1a;
        }

        .menu-category-section {
            margin-bottom: 60px;
        }

        .category-title {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 2.5rem;
            letter-spacing: 2px;
            text-align: center;
            margin-bottom: 40px;
            text-transform: uppercase;
            color: #1a1a1a;
        }

        /* Menu Container */
        .menu-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 60px 40px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px 80px;
        }

        /* Menu Item */
        .menu-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            padding-bottom: 30px;
            border-bottom: 2px dotted rgba(26, 26, 26, 0.3);
            opacity: 1;
            transform: translateY(0);
            transition: opacity 0.3s ease, transform 0.3s ease;
        }

        .menu-item:hover {
            transform: translateX(10px);
        }

        .menu-item-image {
            width: 120px;
            height: 120px;
            border-radius: 8px;
            overflow: hidden;
            flex-shrink: 0;
            background: rgba(26, 26, 26, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .menu-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .menu-item:hover .menu-item-image img {
            transform: scale(1.1);
        }

        .menu-item-left {
            flex: 1;
        }

        .menu-item-name {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.4rem;
            letter-spacing: 1px;
            margin-bottom: 10px;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .menu-item-badge {
            font-size: 0.7rem;
            padding: 3px 8px;
            background: #e74c3c;
            color: white;
            border-radius: 3px;
            letter-spacing: 0.5px;
        }

        .menu-item-description {
            font-size: 0.9rem;
            color: rgba(26, 26, 26, 0.8);
            line-height: 1.5;
            max-width: 400px;
        }

        .menu-item-price {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.5rem;
            color: #e74c3c;
            letter-spacing: 1px;
            margin-left: 20px;
            white-space: nowrap;
        }

        /* Add to Cart Button */
        .add-to-cart-btn {
            display: inline-block;
            margin-top: 10px;
            padding: 8px 16px;
            background: #1a1a1a;
            color: white;
            border: none;
            font-family: 'Bebas Neue', sans-serif;
            font-size: 0.9rem;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .add-to-cart-btn:hover {
            background: #e74c3c;
            transform: scale(1.05);
        }

        /* Footer */
        .menu-footer {
            text-align: center;
            padding: 60px 40px;
            background: rgba(0, 0, 0, 0.05);
        }

        .menu-footer-text {
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.5rem;
            letter-spacing: 2px;
            margin-bottom: 20px;
        }

        .menu-footer-btn {
            display: inline-block;
            padding: 15px 40px;
            background: #e74c3c;
            color: white;
            text-decoration: none;
            font-family: 'Bebas Neue', sans-serif;
            font-size: 1.2rem;
            letter-spacing: 2px;
            transition: all 0.3s;
        }

        .menu-footer-btn:hover {
            background: #c0392b;
            transform: translateY(-3px);
        }

        /* Responsive */
        @media (max-width: 968px) {
            .menu-container {
                grid-template-columns: 1fr;
                gap: 30px;
                padding: 40px 20px;
            }

            .menu-header {
                padding: 120px 20px 60px;
            }

            .menu-header-title {
                font-size: 2.5rem;
            }

            .menu-item-image {
                width: 80px;
                height: 80px;
            }

            .top-navbar {
                padding: 15px 20px 15px 100px;
                min-height: 60px;
            }

            .top-navbar.scrolled {
                padding: 10px 20px 10px 100px;
                min-height: 50px;
            }

            .nav-menu {
                display: none;
            }

            .site-logo {
                left: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Include Navbar -->
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <!-- Menu Header -->
    <div class="menu-header">
        <div class="menu-header-subtitle">
            <?php 
            $subtitle_texts = [
                'en' => 'OUR MENU',
                'de' => 'UNSERE SPEISEKARTE',
                'tr' => 'MENÜMÜZ'
            ];
            echo $subtitle_texts[$current_lang];
            ?>
        </div>
        <h1 class="menu-header-title">
            <?php 
            $title_texts = [
                'en' => 'YOUR ONE-STOP BURGER SHOP',
                'de' => 'IHR BURGER-SHOP AN EINEM ORT',
                'tr' => 'TEK DURAKINIZ BURGER DÜKKANINIZ'
            ];
            echo $title_texts[$current_lang];
            ?>
        </h1>
        <div class="menu-header-divider"></div>
    </div>

    <!-- Category Filter Buttons -->
    <?php if (!empty($categories)): ?>
    <div class="category-filter">
        <div class="category-buttons">
            <button class="category-btn active" data-category="all">
                <?php
                $all_text = [
                    'en' => 'All',
                    'de' => 'Alle',
                    'tr' => 'Hepsi'
                ];
                echo $all_text[$current_lang];
                ?>
            </button>
            <?php foreach ($categories as $category): ?>
                <button class="category-btn" data-category="<?php echo $category['id']; ?>">
                    <?php echo htmlspecialchars($category['category_name']); ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Menu Items Grid -->
    <div class="menu-container">
        <?php if (empty($menu_items)): ?>
            <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px;">
                <h3 style="font-family: 'Bebas Neue', sans-serif; font-size: 2rem; margin-bottom: 20px;">
                    <?php 
                    $no_items = [
                        'en' => 'No menu items available',
                        'de' => 'Keine Menüpunkte verfügbar',
                        'tr' => 'Menü öğesi yok'
                    ];
                    echo $no_items[$current_lang];
                    ?>
                </h3>
            </div>
        <?php else: ?>
            <?php foreach ($menu_items as $item): ?>
                <div class="menu-item" data-category="<?php echo $item['category_id']; ?>">
                    <?php if (!empty($item['image'])): ?>
                    <div class="menu-item-image">
                        <img src="<?php echo ASSETS_URL . '/uploads/menu/' . htmlspecialchars($item['image']); ?>" 
                             alt="<?php echo htmlspecialchars($item['name_' . $current_lang]); ?>"
                             loading="lazy">
                    </div>
                    <?php endif; ?>
                    <div class="menu-item-left">
                        <div class="menu-item-name">
                            <?php echo htmlspecialchars($item['name_' . $current_lang]); ?>
                            <?php if ($item['is_popular']): ?>
                                <span class="menu-item-badge">
                                    <?php 
                                    $popular = [
                                        'en' => 'POPULAR',
                                        'de' => 'BELIEBT',
                                        'tr' => 'POPÜLER'
                                    ];
                                    echo $popular[$current_lang];
                                    ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="menu-item-description">
                            <?php echo htmlspecialchars($item['description_' . $current_lang]); ?>
                        </div>
                        <button class="add-to-cart-btn open-product-modal"
                                data-id="<?php echo $item['id']; ?>"
                                data-name="<?php echo htmlspecialchars($item['name_' . $current_lang]); ?>"
                                data-description="<?php echo htmlspecialchars($item['description_' . $current_lang]); ?>"
                                data-price="<?php echo $item['price']; ?>"
                                data-image="<?php echo !empty($item['image']) ? UPLOADS_URL . '/menu/' . $item['image'] : ASSETS_URL . '/images/placeholder-food.jpg'; ?>">
                            <?php 
                            $add_cart = [
                                'en' => 'ADD TO CART',
                                'de' => 'IN DEN WARENKORB',
                                'tr' => 'SEPETE EKLE'
                            ];
                            echo $add_cart[$current_lang];
                            ?>
                        </button>
                    </div>
                    <div class="menu-item-price">
                        <?php echo formatPrice($item['price']); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Footer CTA -->
    <div class="menu-footer">
        <div class="menu-footer-text">
            <?php 
            $ready_text = [
                'en' => 'READY TO ORDER?',
                'de' => 'BEREIT ZU BESTELLEN?',
                'tr' => 'SİPARİŞ VERMEYE HAZIR MISINIZ?'
            ];
            echo $ready_text[$current_lang];
            ?>
        </div>
        <a href="cart.php" class="menu-footer-btn">
            <?php 
            $view_cart = [
                'en' => 'VIEW YOUR CART',
                'de' => 'WARENKORB ANSEHEN',
                'tr' => 'SEPETİNİZİ GÖRÜN'
            ];
            echo $view_cart[$current_lang];
            ?>
        </a>
    </div>

    <!-- Include Cart JS -->
    <script>
        // Category Filter Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const categoryButtons = document.querySelectorAll('.category-btn');
            const menuItems = document.querySelectorAll('.menu-item');

            categoryButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const selectedCategory = this.dataset.category;

                    // Update active button
                    categoryButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');

                    // Filter menu items
                    menuItems.forEach(item => {
                        if (selectedCategory === 'all' || item.dataset.category === selectedCategory) {
                            item.style.display = 'grid';
                            setTimeout(() => {
                                item.style.opacity = '1';
                                item.style.transform = 'translateY(0)';
                            }, 10);
                        } else {
                            item.style.opacity = '0';
                            item.style.transform = 'translateY(20px)';
                            setTimeout(() => {
                                item.style.display = 'none';
                            }, 300);
                        }
                    });
                });
            });
        });

        // Translations for cart notifications
        if (typeof translations === 'undefined') {
            var translations = {};
        }
        
        Object.assign(translations, {
            cart_item_added: <?php echo json_encode([
                'en' => 'Item added to cart!',
                'de' => 'Artikel in den Warenkorb gelegt!',
                'tr' => 'Ürün sepete eklendi!'
            ][$current_lang]); ?>,
            cart_item_removed: <?php echo json_encode([
                'en' => 'Item removed from cart',
                'de' => 'Artikel aus dem Warenkorb entfernt',
                'tr' => 'Ürün sepetten çıkarıldı'
            ][$current_lang]); ?>,
            cart_empty: <?php echo json_encode([
                'en' => 'Your cart is empty',
                'de' => 'Ihr Warenkorb ist leer',
                'tr' => 'Sepetiniz boş'
            ][$current_lang]); ?>,
            cart_total: <?php echo json_encode([
                'en' => 'Total',
                'de' => 'Gesamt',
                'tr' => 'Toplam'
            ][$current_lang]); ?>,
            cart_checkout: <?php echo json_encode([
                'en' => 'Proceed to Checkout',
                'de' => 'Zur Kasse',
                'tr' => 'Ödemeye Geç'
            ][$current_lang]); ?>,
            cart_continue_shopping: <?php echo json_encode([
                'en' => 'Continue Shopping',
                'de' => 'Weiter einkaufen',
                'tr' => 'Alışverişe Devam Et'
            ][$current_lang]); ?>,
            cart_item: <?php echo json_encode([
                'en' => 'Items',
                'de' => 'Artikel',
                'tr' => 'Ürünler'
            ][$current_lang]); ?>,
            cart_remove: <?php echo json_encode([
                'en' => 'Remove',
                'de' => 'Entfernen',
                'tr' => 'Kaldır'
            ][$current_lang]); ?>,
            checkout_order_summary: <?php echo json_encode([
                'en' => 'Order Summary',
                'de' => 'Bestellübersicht',
                'tr' => 'Sipariş Özeti'
            ][$current_lang]); ?>
        });
    </script>
    <script src="<?php echo ASSETS_URL; ?>/js/cart.js"></script>

    <!-- Product Modal JS -->
    <script src="<?php echo ASSETS_URL; ?>/js/product-modal.js?v=<?php echo ASSET_VERSION; ?>"></script>

    <!-- Auth Modal JS -->
    <script src="<?php echo ASSETS_URL; ?>/js/auth-modal.js?v=<?php echo ASSET_VERSION; ?>"></script>
    
    <!-- Initialize Product Modal -->
    <script>
        // Open product modal on button click
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.open-product-modal').forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault(); // Prevent default button action
                    e.stopPropagation(); // Stop event bubbling
                    
                    const product = {
                        id: this.dataset.id,
                        name: this.dataset.name,
                        description: this.dataset.description,
                        price: this.dataset.price,
                        image: this.dataset.image
                    };
                    
                    if (window.productModal) {
                        productModal.open(product);
                    } else {
                        console.error('Product modal not initialized yet');
                    }
                });
            });
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
</body>
</html>
