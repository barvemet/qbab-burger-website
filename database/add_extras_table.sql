-- Add Menu Extras/Toppings Table
-- For Salatbar and Additional Toppings

CREATE TABLE IF NOT EXISTS menu_extras (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name_en VARCHAR(100) NOT NULL,
    name_de VARCHAR(100) NOT NULL,
    name_tr VARCHAR(100) NOT NULL,
    price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    category VARCHAR(50) NOT NULL COMMENT 'salatbar or toppings',
    is_active TINYINT(1) DEFAULT 1,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_active (is_active),
    INDEX idx_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample extras
INSERT INTO menu_extras (name_en, name_de, name_tr, price, category, display_order) VALUES
-- Aus der Salatbar
('Lettuce', 'Salat', 'Marul', 0.50, 'salatbar', 1),
('Tomatoes', 'Tomaten', 'Domates', 0.50, 'salatbar', 2),
('Cucumbers', 'Gurken', 'Salatalık', 0.50, 'salatbar', 3),
('Onions', 'Zwiebeln', 'Soğan', 0.50, 'salatbar', 4),
('Pickles', 'Gewürzgurken', 'Turşu', 0.50, 'salatbar', 5),
('Jalapeños', 'Jalapeños', 'Jalapeño', 0.50, 'salatbar', 6),
('Coleslaw', 'Krautsalat', 'Lahana Salatası', 0.70, 'salatbar', 7),
('Corn', 'Mais', 'Mısır', 0.50, 'salatbar', 8),

-- Zusätzliche Toppings
('Extra Cheese', 'Extra Käse', 'Ekstra Peynir', 1.50, 'toppings', 1),
('Extra Bacon', 'Extra Speck', 'Ekstra Bacon', 2.00, 'toppings', 2),
('Extra Patty', 'Extra Patty', 'Ekstra Köfte', 3.50, 'toppings', 3),
('Fried Egg', 'Spiegelei', 'Yumurta', 1.50, 'toppings', 4),
('Avocado', 'Avocado', 'Avokado', 2.50, 'toppings', 5),
('Mushrooms', 'Champignons', 'Mantar', 1.50, 'toppings', 6),
('Grilled Onions', 'Gegrillte Zwiebeln', 'Izgara Soğan', 1.00, 'toppings', 7),
('Chili Sauce', 'Chili-Sauce', 'Acı Sos', 0.50, 'toppings', 8);

-- Update order_items table to support extras
ALTER TABLE order_items 
ADD COLUMN extras JSON DEFAULT NULL COMMENT 'Selected extras for this item';

