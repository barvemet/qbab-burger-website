-- ========================================
-- KASSA SYSTEM INTEGRATION - DATABASE MIGRATION
-- Adds Kasse-specific fields to existing orders table
-- Creates kasse_sessions table for shift management
-- ========================================

-- Step 1: Add Kasse fields to orders table
ALTER TABLE orders 
ADD COLUMN order_source ENUM('WEBSITE', 'KASSE', 'LIEFERANDO', 'PHONE') DEFAULT 'WEBSITE' COMMENT 'Sipariş kaynağı',
ADD COLUMN tse_transaction_id VARCHAR(255) DEFAULT NULL COMMENT 'TSE işlem ID (Deutsche Fiskal)',
ADD COLUMN tse_signature TEXT DEFAULT NULL COMMENT 'TSE dijital imza',
ADD COLUMN tse_qr_code TEXT DEFAULT NULL COMMENT 'TSE QR kodu (fiş için)',
ADD COLUMN cashier_name VARCHAR(100) DEFAULT NULL COMMENT 'Kasiyer adı (KASSE siparişleri için)',
ADD COLUMN cash_given DECIMAL(10, 2) DEFAULT NULL COMMENT 'Müşterinin verdiği nakit tutar',
ADD COLUMN cash_change DECIMAL(10, 2) DEFAULT NULL COMMENT 'Para üstü',
ADD COLUMN is_synced TINYINT(1) DEFAULT 1 COMMENT 'Offline\'dan sync edildi mi (0=offline pending, 1=synced)',
ADD INDEX idx_order_source (order_source),
ADD INDEX idx_tse_transaction_id (tse_transaction_id),
ADD INDEX idx_is_synced (is_synced);

-- Step 2: Add extras_json field to order_items if not exists
ALTER TABLE order_items 
ADD COLUMN extras_json TEXT DEFAULT NULL COMMENT 'Seçilen ekstralar JSON formatında';

-- Step 3: Add tax_rate field to menu_items if not exists (for DATEV export)
ALTER TABLE menu_items 
ADD COLUMN tax_rate DECIMAL(4, 2) DEFAULT 19.00 COMMENT 'MwSt oranı (7.00 veya 19.00)';

-- Step 4: Create kasse_sessions table for shift management
CREATE TABLE IF NOT EXISTS kasse_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_number VARCHAR(50) UNIQUE NOT NULL COMMENT 'Vardiya numarası (örn: KS-20250114-001)',
    cashier_name VARCHAR(100) NOT NULL COMMENT 'Kasiyer adı',
    
    -- Session timing
    start_time DATETIME NOT NULL COMMENT 'Vardiya başlangıç zamanı',
    end_time DATETIME DEFAULT NULL COMMENT 'Vardiya bitiş zamanı',
    
    -- Cash tracking
    starting_cash DECIMAL(10, 2) DEFAULT 0.00 COMMENT 'Kasadaki başlangıç parası',
    ending_cash DECIMAL(10, 2) DEFAULT NULL COMMENT 'Vardiya sonu kasadaki para',
    expected_cash DECIMAL(10, 2) DEFAULT NULL COMMENT 'Olması gereken tutar (sistem hesabı)',
    cash_difference DECIMAL(10, 2) DEFAULT NULL COMMENT 'Fark (ending_cash - expected_cash)',
    
    -- Session statistics
    total_sales DECIMAL(10, 2) DEFAULT 0.00 COMMENT 'Toplam satış tutarı',
    total_orders INT DEFAULT 0 COMMENT 'Toplam sipariş sayısı',
    cash_orders INT DEFAULT 0 COMMENT 'Nakit sipariş sayısı',
    card_orders INT DEFAULT 0 COMMENT 'Kart sipariş sayısı',
    
    -- Session notes
    opening_notes TEXT DEFAULT NULL COMMENT 'Açılış notları',
    closing_notes TEXT DEFAULT NULL COMMENT 'Kapanış notları',
    
    -- Status
    status ENUM('ACTIVE', 'CLOSED') DEFAULT 'ACTIVE' COMMENT 'Vardiya durumu',
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Indexes
    INDEX idx_session_number (session_number),
    INDEX idx_cashier_name (cashier_name),
    INDEX idx_start_time (start_time),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Kasa vardiya yönetimi';

-- Step 5: Create table for offline sync queue (optional but useful)
CREATE TABLE IF NOT EXISTS kasse_sync_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_data JSON NOT NULL COMMENT 'Offline sipariş verisi (JSON)',
    sync_status ENUM('PENDING', 'PROCESSING', 'COMPLETED', 'FAILED') DEFAULT 'PENDING',
    sync_attempts INT DEFAULT 0 COMMENT 'Sync deneme sayısı',
    error_message TEXT DEFAULT NULL COMMENT 'Hata mesajı (varsa)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Offline sipariş oluşturulma zamanı',
    synced_at TIMESTAMP NULL DEFAULT NULL COMMENT 'Sync edilme zamanı',
    
    INDEX idx_sync_status (sync_status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Offline siparişlerin sync kuyruğu';

-- Step 6: Update existing orders to have default source
UPDATE orders SET order_source = 'WEBSITE' WHERE order_source IS NULL;

-- Step 7: Set default tax rates for existing menu items
-- Assuming most items are 19%, specific items can be updated manually later
UPDATE menu_items SET tax_rate = 19.00 WHERE tax_rate IS NULL;

-- ========================================
-- MIGRATION COMPLETE
-- ========================================
-- Next steps:
-- 1. Run this SQL on Strato MySQL database
-- 2. Verify all columns are added correctly
-- 3. Update tax_rate for items that should be 7% (e.g., basic food items)
-- 4. Create TSE service and API endpoints
-- ========================================

