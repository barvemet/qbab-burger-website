<?php
/**
 * Get Products API - Kasse System
 * Returns active products with categories and extras
 * Ürün listesi (kategorilerle ve ekstralarla)
 */

session_start();

// Define constant for includes
define('ALLOW_INCLUDE', true);

// Set JSON header
header('Content-Type: application/json');

// Include config
require_once __DIR__ . '/../../includes/config.php';

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Methode nicht erlaubt'
    ]);
    exit;
}

try {
    $db = getDBConnection();

    // Get all active categories
    $stmt = $db->query("
        SELECT 
            id,
            name_de as name,
            icon,
            display_order
        FROM menu_categories
        WHERE is_active = 1
        ORDER BY display_order ASC
    ");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all active products
    $stmt = $db->query("
        SELECT 
            id,
            category_id,
            name_de,
            description_de,
            price,
            image,
            is_vegetarian,
            is_vegan,
            is_popular,
            discount_percent,
            display_order,
            tax_rate
        FROM menu_items
        WHERE is_active = 1
        ORDER BY display_order ASC
    ");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get all active extras
    $stmt = $db->query("
        SELECT 
            id,
            name_de,
            price,
            category,
            display_order
        FROM menu_extras
        WHERE is_active = 1
        ORDER BY category, display_order ASC
    ");
    $extras = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group products by category
    $productsByCategory = [];
    foreach ($products as $product) {
        $categoryId = $product['category_id'];
        if (!isset($productsByCategory[$categoryId])) {
            $productsByCategory[$categoryId] = [];
        }
        
        // Format product data
        $productsByCategory[$categoryId][] = [
            'id' => intval($product['id']),
            'name_de' => $product['name_de'],
            'description_de' => $product['description_de'],
            'price' => floatval($product['price']),
            'image' => $product['image'],
            'is_vegetarian' => (bool)$product['is_vegetarian'],
            'is_vegan' => (bool)$product['is_vegan'],
            'is_popular' => (bool)$product['is_popular'],
            'discount_percent' => floatval($product['discount_percent']),
            'tax_rate' => floatval($product['tax_rate'] ?? 19.00)
        ];
    }

    // Format categories with products
    $categoriesWithProducts = [];
    foreach ($categories as $category) {
        $categoryId = intval($category['id']);
        $categoriesWithProducts[] = [
            'id' => $categoryId,
            'name' => $category['name'],
            'icon' => $category['icon'] ?? '📦', // Use database icon or default
            'products' => $productsByCategory[$categoryId] ?? []
        ];
    }

    // Group extras by category
    $extrasByCategory = [];
    foreach ($extras as $extra) {
        $category = $extra['category'] ?? 'Sonstiges';
        if (!isset($extrasByCategory[$category])) {
            $extrasByCategory[$category] = [];
        }
        
        $extrasByCategory[$category][] = [
            'id' => intval($extra['id']),
            'name_de' => $extra['name_de'],
            'price' => floatval($extra['price'])
        ];
    }

    // Return response
    echo json_encode([
        'success' => true,
        'data' => [
            'categories' => $categoriesWithProducts,
            'extras' => $extrasByCategory,
            'total_products' => count($products),
            'total_categories' => count($categories),
            'total_extras' => count($extras)
        ]
    ]);

} catch (Exception $e) {
    error_log('Get products error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Fehler beim Abrufen der Produkte: ' . $e->getMessage()
    ]);
}
?>

