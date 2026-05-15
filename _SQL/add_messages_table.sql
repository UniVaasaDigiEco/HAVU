-- Messages table for internal messaging center
-- Allows players to send messages to route creators
-- Instead of one-way feedback email, messages are stored in the database for creators to review

CREATE TABLE IF NOT EXISTS `messages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `recipient_user_id` INT NOT NULL COMMENT 'The route creator receiving the message (FK to users.id)',
  `sender_user_id` INT NULL COMMENT 'The player sending the message (FK to users.id). NULL if anonymous',
  `route_id` INT UNSIGNED NULL COMMENT 'If route-specific message, FK to routes.id. NULL for general messages to creator',
  `title` VARCHAR(255) NOT NULL COMMENT 'Message subject/title',
  `content` TEXT NOT NULL COMMENT 'Message body content',
  `is_read` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0 = unread, 1 = read',
  `read_at` DATETIME NULL COMMENT 'When the message was marked as read',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When the message was created',
  
  PRIMARY KEY (`id`),
  KEY `idx_recipient_user_id` (`recipient_user_id`),
  KEY `idx_sender_user_id` (`sender_user_id`),
  KEY `idx_route_id` (`route_id`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_recipient_unread` (`recipient_user_id`, `is_read`),
  
  CONSTRAINT `fk_messages_recipient` FOREIGN KEY (`recipient_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_messages_sender` FOREIGN KEY (`sender_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_messages_route` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add column to track when creator was last emailed about unread messages
-- This prevents spamming creators with multiple reminder emails per week
ALTER TABLE `users` ADD COLUMN `messages_last_reminder_sent` DATETIME NULL 
  COMMENT 'Last time this creator received a reminder email about unread messages' 
  AFTER `updated_at`;
