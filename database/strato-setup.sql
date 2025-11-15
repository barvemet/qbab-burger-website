-- Q-Bab Burger Restaurant Website Database Schema
-- Modified for STRATO hosting (dbs14816626)

USE dbs14816626;

-- Admin users table
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_username (username)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Menu categories table
CREATE TABLE IF NOT EXISTS menu_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name_en VARCHAR(100) NOT NULL,
    name_de VARCHAR(100) NOT NULL,
    name_tr VARCHAR(100) NOT NULL,
    description_en TEXT,
    description_de TEXT,
    description_tr TEXT,
    display_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active (is_active),
    INDEX idx_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Menu items table
CREATE TABLE IF NOT EXISTS menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name_en VARCHAR(150) NOT NULL,
    name_de VARCHAR(150) NOT NULL,
    name_tr VARCHAR(150) NOT NULL,
    description_en TEXT,
    description_de TEXT,
    description_tr TEXT,
    price DECIMAL(10, 2) NOT NULL,
    image VARCHAR(255),
    is_vegetarian TINYINT(1) DEFAULT 0,
    is_vegan TINYINT(1) DEFAULT 0,
    is_popular TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    display_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES menu_categories(id) ON DELETE CASCADE,
    INDEX idx_category (category_id),
    INDEX idx_active (is_active),
    INDEX idx_popular (is_popular)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Customers table
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20) NOT NULL,
    address TEXT,
    city VARCHAR(50),
    postal_code VARCHAR(10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT,
    order_number VARCHAR(20) NOT NULL UNIQUE,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(100),
    customer_phone VARCHAR(20) NOT NULL,
    delivery_address TEXT,
    city VARCHAR(50),
    postal_code VARCHAR(10),
    total_amount DECIMAL(10, 2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    payment_status VARCHAR(20) DEFAULT 'pending',
    order_status VARCHAR(20) DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    INDEX idx_order_number (order_number),
    INDEX idx_status (order_status),
    INDEX idx_payment (payment_status),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    menu_item_id INT,
    item_name VARCHAR(150) NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    notes TEXT,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE SET NULL,
    INDEX idx_order (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reviews table
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(100),
    rating INT NOT NULL,
    review_text TEXT,
    is_approved TINYINT(1) DEFAULT 0,
    is_featured TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_approved (is_approved),
    INDEX idx_featured (is_featured),
    INDEX idx_rating (rating)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Chat messages table
CREATE TABLE IF NOT EXISTS chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    response TEXT,
    is_bot TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_session (session_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- FAQ table
CREATE TABLE IF NOT EXISTS faqs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_en TEXT NOT NULL,
    question_de TEXT NOT NULL,
    question_tr TEXT NOT NULL,
    answer_en TEXT NOT NULL,
    answer_de TEXT NOT NULL,
    answer_tr TEXT NOT NULL,
    display_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_active (is_active),
    INDEX idx_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Site settings table
CREATE TABLE IF NOT EXISTS site_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Content pages table
CREATE TABLE IF NOT EXISTS content_pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    page_slug VARCHAR(50) NOT NULL UNIQUE,
    title_en VARCHAR(200) NOT NULL,
    title_de VARCHAR(200) NOT NULL,
    title_tr VARCHAR(200) NOT NULL,
    content_en TEXT,
    content_de TEXT,
    content_tr TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (page_slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user (username: admin, password: admin123)
INSERT INTO admin_users (username, password, email) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@q-bab.de');

-- Insert default menu categories
INSERT INTO menu_categories (name_en, name_de, name_tr, display_order) VALUES
('Burgers & Kebabs', 'Burger & Kebabs', 'Burgerler ve Kebaplar', 1),
('Combos', 'Kombi-Menüs', 'Menüler', 2),
('Drinks', 'Getränke', 'İçecekler', 3);

-- Insert default site settings
INSERT INTO site_settings (setting_key, setting_value) VALUES
('site_name', 'Q-Bab Burger'),
('site_email', 'info@q-bab.de'),
('site_phone', '+49 8205 123456'),
('site_address', 'Mühlweg 1, 86559 Adelzhausen, Germany'),
('logo_path', ''),
('opening_hours_en', 'Mon-Sun: 11:00 AM - 10:00 PM'),
('opening_hours_de', 'Mo-So: 11:00 - 22:00 Uhr'),
('opening_hours_tr', 'Pzt-Paz: 11:00 - 22:00'),
('facebook_url', ''),
('instagram_url', ''),
('notification_email', 'orders@q-bab.de'),
('notification_phone', '');

-- Insert default content pages
INSERT INTO content_pages (page_slug, title_en, title_de, title_tr, content_en, content_de, content_tr) VALUES
('about', 'About Us', 'Über uns', 'Hakkımızda', 
'Welcome to Q-Bab Burger! We serve delicious burgers and kebabs made with fresh ingredients.', 
'Willkommen bei Q-Bab Burger! Wir servieren köstliche Burger und Kebabs aus frischen Zutaten.', 
'Q-Bab Burger''a hoş geldiniz! Taze malzemelerle hazırlanmış lezzetli burgerler ve kebaplar sunuyoruz.'),
('contact', 'Contact Us', 'Kontakt', 'İletişim',
'Get in touch with us for any questions or concerns.',
'Kontaktieren Sie uns bei Fragen oder Anliegen.',
'Herhangi bir soru veya endişeniz için bizimle iletişime geçin.');

-- Insert sample FAQs
INSERT INTO faqs (question_en, question_de, question_tr, answer_en, answer_de, answer_tr, display_order) VALUES
('What are your opening hours?', 'Was sind Ihre Öffnungszeiten?', 'Açılış saatleriniz nedir?',
'We are open Monday to Sunday from 11:00 AM to 10:00 PM.', 
'Wir haben Montag bis Sonntag von 11:00 bis 22:00 Uhr geöffnet.',
'Pazartesiden Pazara 11:00 - 22:00 saatleri arasında açığız.', 1),
('Do you offer vegan options?', 'Bieten Sie vegane Optionen an?', 'Vegan seçenekleriniz var mı?',
'Yes, we have several vegan burger options available.',
'Ja, wir haben mehrere vegane Burger-Optionen.',
'Evet, çeşitli vegan burger seçeneklerimiz bulunmaktadır.', 2),
('How long does delivery take?', 'Wie lange dauert die Lieferung?', 'Teslimat ne kadar sürer?',
'Typical delivery time is 30-45 minutes.',
'Die typische Lieferzeit beträgt 30-45 Minuten.',
'Teslimat süresi genellikle 30-45 dakikadır.', 3);
