-- Add homepage settings to database
-- Run this in PhpMyAdmin

USE dbs14816626;

-- Insert homepage about section settings
INSERT INTO site_settings (setting_key, setting_value) VALUES
('homepage_about_title_de', 'Gourmet Burger'),
('homepage_about_title_en', 'Gourmet Burger'),
('homepage_about_title_tr', 'Gurme Burger'),
('homepage_about_subtitle_de', 'Köstliche Burger aus den frischesten Zutaten'),
('homepage_about_subtitle_en', 'Delicious Burgers from the Freshest Ingredients'),
('homepage_about_subtitle_tr', 'En Taze Malzemelerden Lezzetli Burgerler'),
('homepage_about_description_de', 'Entdecken Sie die perfekte Kombination aus erstklassigem Rindfleisch, handwerklichen Brötchen und frischem Gemüse. Unsere Gourmet-Burger werden mit Leidenschaft und nur den besten Zutaten zubereitet. Perfekt gegrillt und mit hausgemachten Spezialsaucen verfeinert.'),
('homepage_about_description_en', 'Discover the perfect combination of premium beef, artisan buns and fresh vegetables. Our gourmet burgers are prepared with passion and only the finest ingredients. Perfectly grilled and refined with homemade special sauces.'),
('homepage_about_description_tr', 'Birinci sınıf sığır eti, el yapımı ekmek ve taze sebzelerin mükemmel kombinasyonunu keşfedin. Gurme burgerlerimiz tutkuyla ve sadece en iyi malzemelerle hazırlanır. Mükemmel ızgara ve ev yapımı özel soslarla zenginleştirilmiştir.'),
('homepage_about_image_1', ''),
('homepage_about_image_2', '')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);
