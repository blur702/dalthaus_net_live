#!/bin/bash

# =====================================
# CLEANUP SCRIPT FOR PRODUCTION
# Removes all development files before deployment
# =====================================

echo "========================================="
echo "Production Cleanup Script"
echo "This will remove all development files"
echo "========================================="
echo ""

# Confirmation
read -p "Are you sure you want to remove all development files? (y/N): " confirm
if [[ ! "$confirm" =~ ^[Yy]$ ]]; then
    echo "Cleanup cancelled."
    exit 0
fi

echo ""
echo "Removing development files..."

# Remove development documentation
echo "- Removing development documentation..."
rm -f CLAUDE.md README.md TODO.md NOTES.md IMPLEMENTATION_NOTES.md
rm -f SHARED_HOSTING_DEPLOYMENT.md
rm -f FINAL_E2E_TEST_REPORT.md SECURITY_FIXES_REPORT.md FINAL_SECURITY_REPORT.md
rm -f FINAL_E2E_VALIDATION_REPORT.md
rm -f test-report-*.md

# Remove development scripts
echo "- Removing development scripts..."
rm -f router.php debug-remote.php
rm -f remote-debug-workflow.md ssh-debug-commands.md
rm -f install-mysql.ps1 install-mysql-admin.bat
rm -f set_permissions.sh
rm -f config.local.php includes/config.local.php

# Remove testing files
echo "- Removing testing directory..."
rm -rf tests/

# Remove Node.js files
echo "- Removing Node.js files..."
rm -rf node_modules/
rm -f package.json package-lock.json yarn.lock

# Remove Python cache
echo "- Removing Python cache..."
find . -type d -name "__pycache__" -exec rm -rf {} + 2>/dev/null
find . -type f -name "*.pyc" -delete 2>/dev/null

# Remove IDE files
echo "- Removing IDE files..."
rm -rf .vscode/ .idea/
rm -f .DS_Store Thumbs.db

# Remove SQL files (except structure)
echo "- Removing SQL files..."
rm -f database_fixes.sql *.sql.bak *.sql.backup

# Remove screenshots
echo "- Removing screenshots..."
rm -rf screenshots/

# Remove git files (optional - uncomment if needed)
# rm -rf .git/
# rm -f .gitattributes

# Clean cache and logs (optional)
read -p "Do you want to clear cache and logs? (y/N): " clear_cache
if [[ "$clear_cache" =~ ^[Yy]$ ]]; then
    echo "- Clearing cache..."
    rm -f cache/*.cache cache/*.html
    echo "- Clearing logs..."
    rm -f logs/*.log logs/*.txt
fi

# Remove this cleanup script
echo "- Removing cleanup script..."
rm -f cleanup-for-production.sh

echo ""
echo "========================================="
echo "✅ Cleanup complete!"
echo "========================================="
echo ""
echo "Production files remaining:"
echo "- /admin/ (admin panel)"
echo "- /assets/ (CSS, JS, images)"
echo "- /includes/ (PHP includes)"
echo "- /public/ (public pages)"
echo "- /scripts/ (converter script)"
echo "- /uploads/ (user uploads)"
echo "- /cache/ (cache directory)"
echo "- /logs/ (log directory)"
echo "- index.php (main entry)"
echo "- .htaccess (Apache config)"
echo "- .user.ini (PHP config)"
echo "- setup.php (run once, then delete)"
echo "- favicon files"
echo ""
echo "Next steps:"
echo "1. Review remaining files"
echo "2. Commit changes: git add . && git commit -m 'Production ready'"
echo "3. Push to repository: git push origin main"
echo "4. Deploy to shared hosting"
echo "5. Run setup.php on server"
echo "6. DELETE setup.php after setup"
echo ""