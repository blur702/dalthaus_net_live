-- Add metadata columns to media_uploads table for Drupal-style media management
-- This enables storing alt text, titles, captions, and image dimensions

ALTER TABLE media_uploads
ADD COLUMN IF NOT EXISTS alt_text VARCHAR(255) NULL COMMENT 'Alt text for accessibility',
ADD COLUMN IF NOT EXISTS title VARCHAR(255) NULL COMMENT 'Image title',
ADD COLUMN IF NOT EXISTS caption TEXT NULL COMMENT 'Image caption',
ADD COLUMN IF NOT EXISTS width INT NULL COMMENT 'Image width in pixels',
ADD COLUMN IF NOT EXISTS height INT NULL COMMENT 'Image height in pixels',
ADD COLUMN IF NOT EXISTS tags JSON NULL COMMENT 'Tags for categorization (future use)';

-- Add index for searching by metadata
ALTER TABLE media_uploads
ADD INDEX idx_alt_text (alt_text),
ADD INDEX idx_title (title);

-- Show table structure
DESCRIBE media_uploads;
