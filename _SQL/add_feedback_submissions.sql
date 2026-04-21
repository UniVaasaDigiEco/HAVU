CREATE TABLE IF NOT EXISTS `feedback_submissions` (
  `id`         INT            NOT NULL AUTO_INCREMENT,
  `type`       ENUM('contact','bug','feature') NOT NULL,
  `name`       VARCHAR(100)   NOT NULL,
  `email`      VARCHAR(255)   NOT NULL,
  `message`    TEXT           NOT NULL,
  `user_id`    INT            NULL,
  `page_url`   VARCHAR(500)   NOT NULL,
  `created_at` TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_type`       (`type`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_feedback_user`
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
