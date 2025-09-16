-- Migration: Add display_name field to users table
-- Date: 2025-09-16
-- Description: Add display_name field for frontend display while keeping username for login

-- Add display_name column to users table
ALTER TABLE `users` 
ADD COLUMN `display_name` varchar(100) NULL AFTER `username`;

-- Update existing users to have display_name = username initially
UPDATE `users` SET `display_name` = `username` WHERE `display_name` IS NULL;

-- Make display_name required after setting initial values
ALTER TABLE `users` 
MODIFY COLUMN `display_name` varchar(100) NOT NULL;

-- Add index for display_name for better query performance
ALTER TABLE `users` 
ADD INDEX `idx_display_name` (`display_name`);