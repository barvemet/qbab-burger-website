-- Special Offers / Angebot Table
-- Modern offers/deals management system

CREATE TABLE IF NOT EXISTS special_offers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title_de VARCHAR(255) NOT NULL,
    title_en VARCHAR(255) NOT NULL,
    title_tr VARCHAR(255) NOT NULL,
    description_de TEXT NOT NULL,
    description_en TEXT NOT NULL,
    description_tr TEXT NOT NULL,
    
    -- Pricing
    original_price DECIMAL(10, 2) NOT NULL,
    offer_price DECIMAL(10, 2) NOT NULL,
    discount_percentage INT GENERATED ALWAYS AS (ROUND(((original_price - offer_price) / original_price) * 100)) STORED,
    
    -- Image
    image_url VARCHAR(500) DEFAULT NULL,
    
    -- Badge/Label (HOT DEAL, LIMITED TIME, etc.)
    badge_text_de VARCHAR(50) DEFAULT NULL,
    badge_text_en VARCHAR(50) DEFAULT NULL,
    badge_text_tr VARCHAR(50) DEFAULT NULL,
    badge_color VARCHAR(20) DEFAULT '#e74c3c' COMMENT 'Hex color code',
    
    -- Validity
    valid_from DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    valid_until DATETIME DEFAULT NULL COMMENT 'NULL = No expiry',
    
    -- Status & Display
    is_active TINYINT(1) DEFAULT 1,
    is_featured TINYINT(1) DEFAULT 0 COMMENT 'Show in hero/top section',
    display_order INT DEFAULT 0,
    
    -- Terms & Conditions
    terms_de TEXT DEFAULT NULL,
    terms_en TEXT DEFAULT NULL,
    terms_tr TEXT DEFAULT NULL,
    
    -- CTA Button
    button_text_de VARCHAR(100) DEFAULT 'Jetzt bestellen',
    button_text_en VARCHAR(100) DEFAULT 'Order now',
    button_text_tr VARCHAR(100) DEFAULT 'Şimdi sipariş ver',
    button_link VARCHAR(500) DEFAULT '/menu.php',
    
    -- Analytics
    view_count INT DEFAULT 0,
    click_count INT DEFAULT 0,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_active (is_active),
    INDEX idx_featured (is_featured),
    INDEX idx_validity (valid_from, valid_until),
    INDEX idx_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample Special Offers
INSERT INTO special_offers (
    title_de, title_en, title_tr,
    description_de, description_en, description_tr,
    original_price, offer_price,
    image_url,
    badge_text_de, badge_text_en, badge_text_tr,
    badge_color,
    valid_from, valid_until,
    is_featured, display_order
) VALUES
-- Featured Offer 1
(
    'Mega Burger Menü',
    'Mega Burger Menu',
    'Mega Burger Menü',
    'Unser beliebtester Burger mit Pommes, Getränk und Dessert. Spare 25% mit diesem exklusiven Angebot!',
    'Our most popular burger with fries, drink and dessert. Save 25% with this exclusive offer!',
    'En popüler burgerimiz patates, içecek ve tatlı ile. Bu özel teklifle %25 tasarruf edin!',
    19.99, 14.99,
    '/assets/images/gourmet-burger.jpg',
    'MEGADEAL', 'MEGADEAL', 'MEGADEAL',
    '#e74c3c',
    NOW(), DATE_ADD(NOW(), INTERVAL 7 DAY),
    1, 1
),

-- Featured Offer 2
(
    'Family Feast',
    'Family Feast',
    'Aile Menüsü',
    '4 Burger + 4 Pommes + 4 Getränke + 2 Saucen. Perfekt für die ganze Familie!',
    '4 Burgers + 4 Fries + 4 Drinks + 2 Sauces. Perfect for the whole family!',
    '4 Burger + 4 Patates + 4 İçecek + 2 Sos. Tüm aile için mükemmel!',
    45.00, 34.99,
    '/assets/images/gallery/1.jpg',
    'FAMILIENPAKET', 'FAMILY PACK', 'AİLE PAKETİ',
    '#27ae60',
    NOW(), DATE_ADD(NOW(), INTERVAL 14 DAY),
    1, 2
),

-- Regular Offer 3
(
    'Lunch Special',
    'Lunch Special',
    'Öğle Menüsü',
    'Montag bis Freitag 11:00-15:00 Uhr. Burger deiner Wahl mit Pommes und Getränk.',
    'Monday to Friday 11:00-15:00. Burger of your choice with fries and drink.',
    'Pazartesi-Cuma 11:00-15:00. İstediğiniz burger patates ve içecekle.',
    12.50, 9.99,
    '/assets/images/gallery/2.jpg',
    'MITTAGSANGEBOT', 'LUNCH DEAL', 'ÖĞLE TEKLİFİ',
    '#f39c12',
    NOW(), NULL,
    0, 3
),

-- Regular Offer 4
(
    'Student Deal',
    'Student Deal',
    'Öğrenci İndirimi',
    'Zeig deinen Studentenausweis und spare 15% auf alle Burger!',
    'Show your student ID and save 15% on all burgers!',
    'Öğrenci kimliğinizi gösterin ve tüm burgerlerde %15 tasarruf edin!',
    10.00, 8.50,
    '/assets/images/gallery/3.jpg',
    'STUDENT', 'STUDENT', 'ÖĞRENCİ',
    '#3498db',
    NOW(), NULL,
    0, 4
),

-- Limited Time Offer 5
(
    'Happy Hour',
    'Happy Hour',
    'Happy Hour',
    'Jeden Tag 17:00-19:00 Uhr. 2 Burger zum Preis von 1!',
    'Every day 17:00-19:00. 2 Burgers for the price of 1!',
    'Her gün 17:00-19:00. 2 burger 1 fiyatına!',
    20.00, 10.00,
    '/assets/images/gallery/4.jpg',
    '50% RABATT', '50% OFF', '%50 İNDİRİM',
    '#e67e22',
    NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY),
    0, 5
);

