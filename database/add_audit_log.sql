-- Admin Audit Log Table
-- Tracks all admin actions for security and accountability

CREATE TABLE IF NOT EXISTS admin_audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NULL,
    admin_username VARCHAR(100) NOT NULL,
    action VARCHAR(50) NOT NULL, -- CREATE, UPDATE, DELETE, LOGIN, LOGOUT, etc.
    details TEXT NULL,
    table_name VARCHAR(100) NULL,
    record_id INT NULL,
    ip_address VARCHAR(45) NULL, -- IPv6 support
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_admin_id (admin_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at),
    INDEX idx_table_record (table_name, record_id),
    FOREIGN KEY (admin_id) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample queries for audit log:

-- Get all actions by admin
-- SELECT * FROM admin_audit_log WHERE admin_username = 'admin' ORDER BY created_at DESC LIMIT 50;

-- Get all deletions
-- SELECT * FROM admin_audit_log WHERE action = 'DELETE' ORDER BY created_at DESC;

-- Get actions on specific table
-- SELECT * FROM admin_audit_log WHERE table_name = 'menu_extras' ORDER BY created_at DESC;

-- Get actions in last 24 hours
-- SELECT * FROM admin_audit_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) ORDER BY created_at DESC;

-- Get actions from specific IP
-- SELECT * FROM admin_audit_log WHERE ip_address = '123.456.789.0' ORDER BY created_at DESC;

