-- Fix missing teaser images for October 2025 content
-- These images were referenced in the database but never uploaded to the server

-- Option 1: Clear the broken teaser image references (recommended)
UPDATE content 
SET teaser_image = NULL 
WHERE teaser_image IN (
    '530fe5ee220115e97dfb7a6386051541.jpg',
    '24e20e2aea802c08f96721eec4a66565.jpg',
    'ad495c68c2a90419cf64064ca3a6c777.jpg',
    'f6e47e30c04483d00557f461c4793869.jpg'
);

-- Alternative Option 2: Use a placeholder image from September (if you prefer to keep images)
-- UPDATE content 
-- SET teaser_image = '9a5c952b8b808e7f4207cf8e4afc436e.jpg'
-- WHERE teaser_image IN (
--     '530fe5ee220115e97dfb7a6386051541.jpg',
--     '24e20e2aea802c08f96721eec4a66565.jpg',
--     'ad495c68c2a90419cf64064ca3a6c777.jpg',
--     'f6e47e30c04483d00557f461c4793869.jpg'
-- );

-- Verify the fix
SELECT content_id, title, teaser_image 
FROM content 
WHERE content_id IN (31, 33, 34, 35);