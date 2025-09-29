-- Add sort_order column to pages table
ALTER TABLE pages ADD COLUMN sort_order INT(11) NOT NULL DEFAULT 0 AFTER meta_keywords;

-- Add index for better performance
ALTER TABLE pages ADD INDEX idx_sort_order (sort_order);

-- Set initial sort order based on existing page_id
SET @row_number = 0;
UPDATE pages
SET sort_order = (@row_number:=@row_number + 1)
ORDER BY page_id;