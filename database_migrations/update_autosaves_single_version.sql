-- Migration: Update autosaves table for single autosave per content
-- This migration removes version_number and adds master_content_uuid

-- First, backup any existing autosaves with multiple versions
-- (This should be minimal since system is new)

-- Add the new master_content_uuid column
ALTER TABLE `autosaves` 
ADD COLUMN `master_content_uuid` varchar(36) DEFAULT NULL AFTER `autosave_uuid`;

-- Remove the version_number column since we only want one autosave per content
ALTER TABLE `autosaves` 
DROP COLUMN `version_number`;

-- Drop the old cleanup index
DROP INDEX `idx_autosave_cleanup` ON `autosaves`;

-- Add new indexes for the updated structure
CREATE INDEX `idx_master_content_uuid` ON `autosaves`(`master_content_uuid`);
CREATE INDEX `idx_autosave_lookup` ON `autosaves`(`master_content_uuid`, `autosave_uuid`);

-- Add unique constraint to ensure only one autosave per master content UUID
-- This prevents multiple autosaves for the same content piece
ALTER TABLE `autosaves` 
ADD CONSTRAINT `uk_one_autosave_per_content` UNIQUE (`master_content_uuid`);

-- Update any existing records to have a master_content_uuid
-- For existing autosaves without content_id, generate a master UUID
UPDATE `autosaves` 
SET `master_content_uuid` = UUID() 
WHERE `master_content_uuid` IS NULL AND `content_id` IS NULL;

-- For existing autosaves with content_id, use content_id as basis for master UUID
-- This ensures consistency for content that already exists
UPDATE `autosaves` 
SET `master_content_uuid` = CONCAT('content-', `content_id`) 
WHERE `master_content_uuid` IS NULL AND `content_id` IS NOT NULL;