-- Sample Menu Items for Q-Bab Burger
-- Run this in PhpMyAdmin to add sample products

USE dbs14816626;

-- First, make sure we have categories
INSERT IGNORE INTO menu_categories (id, name_en, name_de, name_tr, display_order, is_active) VALUES
(1, 'Burgers', 'Burger', 'Burgerler', 1, 1),
(2, 'Kebabs', 'Kebabs', 'Kebaplar', 2, 1),
(3, 'Sides', 'Beilagen', 'Yan Ürünler', 3, 1),
(4, 'Drinks', 'Getränke', 'İçecekler', 4, 1);

-- Insert sample menu items
INSERT INTO menu_items (category_id, name_en, name_de, name_tr, description_en, description_de, description_tr, price, is_vegetarian, is_vegan, is_popular, is_active, display_order) VALUES

-- Burgers
(1, 'Classic Burger', 'Klassischer Burger', 'Klasik Burger',
 'Juicy beef patty with lettuce, tomato, and special sauce',
 'Saftiges Rindfleisch-Patty mit Salat, Tomate und Spezialsauce',
 'Marul, domates ve özel soslu sulu dana burger',
 8.99, 0, 0, 0, 1, 1),

(1, 'Cheese Burger', 'Cheeseburger', 'Peynirli Burger',
 'Classic burger with melted cheese',
 'Klassischer Burger mit geschmolzenem Käse',
 'Eritilmiş peynirli klasik burger',
 9.99, 0, 0, 0, 1, 2),

(1, 'Double Burger', 'Doppel Burger', 'Çift Burger',
 'Two beef patties with double cheese',
 'Zwei Rindfleisch-Pattys mit doppeltem Käse',
 'Çift peynirli iki dana burger',
 12.99, 0, 0, 0, 1, 3),

(1, 'Veggie Burger', 'Veggie Burger', 'Vejetaryen Burger',
 'Plant-based patty with fresh vegetables',
 'Pflanzliches Patty mit frischem Gemüse',
 'Taze sebzelerle bitkisel burger',
 8.99, 1, 1, 0, 1, 4),

-- Kebabs
(2, 'Chicken Döner', 'Hähnchen Döner', 'Tavuk Döner',
 'Grilled chicken with fresh salad in bread',
 'Gegrilltes Hähnchen mit frischem Salat im Brot',
 'Taze salatalı ızgara tavuk dürüm',
 7.99, 0, 0, 0, 1, 1),

(2, 'Beef Döner', 'Rindfleisch Döner', 'Dana Döner',
 'Grilled beef with vegetables and sauce',
 'Gegrilltes Rindfleisch mit Gemüse und Sauce',
 'Sebze ve soslu ızgara dana dürüm',
 8.99, 0, 0, 0, 1, 2),

(2, 'Mixed Döner', 'Gemischter Döner', 'Karışık Döner',
 'Mix of chicken and beef döner',
 'Mischung aus Hähnchen und Rindfleisch Döner',
 'Tavuk ve dana karışık döner',
 9.49, 0, 0, 0, 1, 3),

(2, 'Falafel Wrap', 'Falafel Wrap', 'Falafel Dürüm',
 'Crispy falafel with tahini sauce',
 'Knusprige Falafel mit Tahini-Sauce',
 'Tahin soslu çıtır falafel',
 7.49, 1, 1, 0, 1, 4),

-- Sides
(3, 'French Fries', 'Pommes Frites', 'Patates Kızartması',
 'Crispy golden fries',
 'Knusprige goldene Pommes',
 'Çıtır altın rengi patates',
 3.99, 1, 1, 0, 1, 1),

(3, 'Onion Rings', 'Zwiebelringe', 'Soğan Halkaları',
 'Crispy breaded onion rings',
 'Knusprige panierte Zwiebelringe',
 'Çıtır soğan halkaları',
 4.49, 1, 0, 0, 1, 2),

(3, 'Chicken Wings', 'Chicken Wings', 'Tavuk Kanatları',
 'Spicy chicken wings (6 pieces)',
 'Scharfe Chicken Wings (6 Stück)',
 'Baharatlı tavuk kanatları (6 adet)',
 6.99, 0, 0, 0, 1, 3),

-- Drinks
(4, 'Coca Cola', 'Coca Cola', 'Coca Cola',
 'Classic Coca Cola 0.5L',
 'Klassische Coca Cola 0,5L',
 'Klasik Coca Cola 0,5L',
 2.99, 1, 1, 0, 1, 1),

(4, 'Ayran', 'Ayran', 'Ayran',
 'Traditional Turkish yogurt drink',
 'Traditionelles türkisches Joghurtgetränk',
 'Geleneksel Türk yoğurt içeceği',
 2.49, 1, 1, 0, 1, 2),

(4, 'Water', 'Wasser', 'Su',
 'Still water 0.5L',
 'Stilles Wasser 0,5L',
 'Maden suyu 0,5L',
 1.99, 1, 1, 0, 1, 3);

-- Update: Mark first 4 items as popular for demo
UPDATE menu_items SET is_popular = 1 WHERE id IN (1, 2, 5, 6) LIMIT 4;
