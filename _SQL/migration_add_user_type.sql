-- Migration: Add user_type field to users table
-- Created: 2026-02-06

USE jansoftw_havu;

-- Add user_type column if it doesn't exist
ALTER TABLE `users`
ADD COLUMN IF NOT EXISTS `user_type` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '0 = Admin, 1 = Regular User' AFTER `is_active`;

-- Add index for user_type for faster queries
ALTER TABLE `users`
ADD INDEX IF NOT EXISTS `idx_user_type` (`user_type`);
