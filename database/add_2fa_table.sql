-- Two-Factor Authentication (2FA) Table
-- Stores 2FA secrets and backup codes for admin users

-- Add 2FA columns to admin_users table
ALTER TABLE admin_users
ADD COLUMN two_factor_enabled TINYINT(1) DEFAULT 0 AFTER password,
ADD COLUMN two_factor_secret VARCHAR(32) NULL AFTER two_factor_enabled,
ADD COLUMN two_factor_backup_codes TEXT NULL AFTER two_factor_secret,
ADD COLUMN two_factor_confirmed_at TIMESTAMP NULL AFTER two_factor_backup_codes;

-- Create 2FA attempts log table for security monitoring
CREATE TABLE IF NOT EXISTS admin_2fa_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    attempt_type ENUM('setup', 'login', 'disable') NOT NULL,
    success TINYINT(1) NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_admin_id (admin_id),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample queries:

-- Check if 2FA is enabled for admin
-- SELECT two_factor_enabled FROM admin_users WHERE username = 'admin';

-- Get recent 2FA attempts
-- SELECT * FROM admin_2fa_attempts ORDER BY created_at DESC LIMIT 20;

-- Get failed 2FA attempts in last hour
-- SELECT * FROM admin_2fa_attempts 
-- WHERE success = 0 
-- AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
-- ORDER BY created_at DESC;

