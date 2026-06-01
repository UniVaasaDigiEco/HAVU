CREATE TABLE IF NOT EXISTS system_admin_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_user_id INT NULL,
    sender_name VARCHAR(100) NOT NULL,
    sender_email VARCHAR(255) NOT NULL,
    feedback_type ENUM('contact', 'bug', 'feature') NOT NULL,
    subject VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    page_url VARCHAR(500) NOT NULL DEFAULT '',
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    is_resolved TINYINT(1) NOT NULL DEFAULT 0,
    read_at DATETIME NULL,
    resolved_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_system_admin_messages_state (is_read, is_resolved, created_at),
    INDEX idx_system_admin_messages_sender (sender_user_id, created_at),
    INDEX idx_system_admin_messages_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
