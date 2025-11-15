-- Add discount field to menu_items table
-- Run this in PhpMyAdmin

USE dbs14816626;

-- Add discount_percent column (0-100)
ALTER TABLE menu_items
ADD COLUMN discount_percent INT DEFAULT 0 COMMENT 'Discount percentage (0-100)';

-- Update some items with sample discounts (optional - for testing)
UPDATE menu_items SET discount_percent = 20 WHERE id = 1;
UPDATE menu_items SET discount_percent = 15 WHERE id = 2;
UPDATE menu_items SET discount_percent = 10 WHERE id = 5;
