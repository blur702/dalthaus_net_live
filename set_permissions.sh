#!/bin/bash

# Set Permissions for Shared Hosting
# Run this script after uploading files to your shared server
# Usage: bash set_permissions.sh

echo "Setting permissions for Dalthaus CMS..."

# Set directory permissions to 755 (rwxr-xr-x)
# Directories need execute permission for traversal
find . -type d -exec chmod 755 {} \;

# Set file permissions to 644 (rw-r--r--)
# Standard for PHP files on shared hosting
find . -type f -exec chmod 644 {} \;

# Set writable directories to 775 (rwxrwxr-x)
# These directories need write access for the web server
chmod 775 cache/
chmod 775 temp/
chmod 775 uploads/
chmod 775 logs/

# Ensure log files are writable if they exist
if [ -f logs/app.log ]; then
    chmod 664 logs/app.log
fi
if [ -f logs/php_errors.log ]; then
    chmod 664 logs/php_errors.log
fi

# Make this script executable (for future runs)
chmod 755 set_permissions.sh

# Protect sensitive files
chmod 600 includes/config.php 2>/dev/null || chmod 644 includes/config.php

echo "Permissions set successfully!"
echo ""
echo "Directory permissions:"
echo "  - Standard directories: 755"
echo "  - Writable directories (cache, temp, uploads, logs): 775"
echo "  - Standard files: 644"
echo "  - Config file: 600 (or 644 if 600 not allowed)"
echo ""
echo "Remember to:"
echo "  1. Update database credentials in includes/config.php"
echo "  2. Run setup.php in browser to create database tables"
echo "  3. Delete setup.php after setup is complete"
echo "  4. Uncomment HTTPS redirect in .htaccess if using SSL"