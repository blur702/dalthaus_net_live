#!/bin/bash
# Manual migration script for media_uploads table
# Run this on the production server via SSH

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo "======================================"
echo "Media Uploads Table Migration"
echo "======================================"
echo ""

# Read database credentials from config.php
CONFIG_FILE="/home/dalthaus/public_html/config/config.php"

if [ ! -f "$CONFIG_FILE" ]; then
    echo -e "${RED}Error: Config file not found at $CONFIG_FILE${NC}"
    exit 1
fi

# Extract database credentials
DB_NAME=$(grep "define('DB_NAME'" "$CONFIG_FILE" | sed "s/.*'\(.*\)'.*/\1/")
DB_USER=$(grep "define('DB_USER'" "$CONFIG_FILE" | sed "s/.*'\(.*\)'.*/\1/")
DB_PASS=$(grep "define('DB_PASS'" "$CONFIG_FILE" | sed "s/.*'\(.*\)'.*/\1/")
DB_HOST=$(grep "define('DB_HOST'" "$CONFIG_FILE" | sed "s/.*'\(.*\)'.*/\1/")

echo "Database: $DB_NAME"
echo "Host: $DB_HOST"
echo "User: $DB_USER"
echo ""

# Run the migration
echo "Creating media_uploads table..."

mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" << 'EOF'
-- Media Uploads Tracking Table
-- Tracks all images uploaded through TinyMCE editor and dual image uploader

CREATE TABLE IF NOT EXISTS media_uploads (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    filepath VARCHAR(500) NOT NULL,
    original_filename VARCHAR(255),
    file_size INT,
    file_type VARCHAR(50),
    upload_type ENUM('tinymce', 'dual_display', 'dual_modal', 'featured', 'teaser') DEFAULT 'tinymce',
    user_id INT,
    content_id INT NULL,
    used_in_content BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_filename (filename),
    INDEX idx_user_id (user_id),
    INDEX idx_content_id (content_id),
    INDEX idx_upload_type (upload_type),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (content_id) REFERENCES content(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
EOF

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Migration completed successfully!${NC}"

    # Verify table was created
    echo ""
    echo "Verifying table structure..."
    mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "DESCRIBE media_uploads;"

    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ Table created and verified!${NC}"
    else
        echo -e "${RED}✗ Could not verify table structure${NC}"
    fi
else
    echo -e "${RED}✗ Migration failed!${NC}"
    exit 1
fi

echo ""
echo "======================================"
echo "Migration Complete"
echo "======================================"
