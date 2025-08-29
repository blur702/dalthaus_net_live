#!/bin/bash

# =====================================
# GIT DEPLOYMENT SCRIPT
# For Dalthaus Photography CMS
# =====================================

set -e  # Exit on any error

echo "======================================="
echo "DALTHAUS CMS - PRODUCTION DEPLOYMENT"
echo "======================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Functions for colored output
print_success() { echo -e "${GREEN}✓${NC} $1"; }
print_error() { echo -e "${RED}✗${NC} $1"; }
print_warning() { echo -e "${YELLOW}⚠${NC} $1"; }
print_info() { echo -e "${BLUE}→${NC} $1"; }
print_header() {
    echo ""
    echo -e "${BLUE}═══════════════════════════════${NC}"
    echo -e "${BLUE} $1${NC}"
    echo -e "${BLUE}═══════════════════════════════${NC}"
    echo ""
}

# Get script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

# Check if git is installed
if ! command -v git &> /dev/null; then
    print_error "Git is not installed"
    exit 1
fi

# Check if this is a git repository
if [ ! -d .git ]; then
    print_error "This is not a git repository"
    exit 1
fi

print_header "PRE-DEPLOYMENT CHECKS"

# Check for uncommitted changes
if [[ -n $(git status -s) ]]; then
    print_warning "You have uncommitted changes:"
    git status -s
    read -p "Continue anyway? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        print_error "Deployment cancelled"
        exit 1
    fi
fi

# Get current branch
CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
print_info "Current branch: $CURRENT_BRANCH"

# Ask which branch to deploy
read -p "Deploy from branch (default: main): " DEPLOY_BRANCH
DEPLOY_BRANCH=${DEPLOY_BRANCH:-main}

if [ "$CURRENT_BRANCH" != "$DEPLOY_BRANCH" ]; then
    print_info "Switching to branch: $DEPLOY_BRANCH"
    git checkout "$DEPLOY_BRANCH"
fi

print_header "PULLING LATEST CHANGES"

# Fetch and pull latest changes
print_info "Fetching from remote..."
git fetch origin

print_info "Pulling latest changes..."
git pull origin "$DEPLOY_BRANCH"

LATEST_COMMIT=$(git rev-parse --short HEAD)
print_success "Updated to commit: $LATEST_COMMIT"

print_header "BACKUP CURRENT STATE"

# Create backup directory
BACKUP_DIR="backups/$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

# Backup database
if [ -f includes/config.php ]; then
    print_info "Backing up database..."
    
    # Extract database credentials from config
    DB_HOST=$(grep "define('DB_HOST'" includes/config.php | cut -d"'" -f4)
    DB_NAME=$(grep "define('DB_NAME'" includes/config.php | cut -d"'" -f4)
    DB_USER=$(grep "define('DB_USER'" includes/config.php | cut -d"'" -f4)
    DB_PASS=$(grep "define('DB_PASS'" includes/config.php | cut -d"'" -f4)
    
    if command -v mysqldump &> /dev/null; then
        mysqldump -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_DIR/database.sql" 2>/dev/null
        if [ $? -eq 0 ]; then
            print_success "Database backed up to $BACKUP_DIR/database.sql"
        else
            print_warning "Database backup failed (may need manual backup)"
        fi
    else
        print_warning "mysqldump not found - skipping database backup"
    fi
fi

# Backup uploads directory
if [ -d uploads ]; then
    print_info "Backing up uploads..."
    cp -r uploads "$BACKUP_DIR/"
    print_success "Uploads backed up"
fi

# Backup config file
if [ -f includes/config.php ]; then
    cp includes/config.php "$BACKUP_DIR/config.php.bak"
    print_success "Config backed up"
fi

print_header "MAINTENANCE MODE"

# Enable maintenance mode
print_info "Enabling maintenance mode..."
if [ -f includes/config.php ]; then
    # Try to enable via database
    mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" \
        -e "UPDATE site_settings SET setting_value = '1' WHERE setting_key = 'maintenance_mode';" 2>/dev/null
    
    if [ $? -eq 0 ]; then
        print_success "Maintenance mode enabled"
    else
        print_warning "Could not enable maintenance mode via database"
    fi
fi

print_header "INSTALLING DEPENDENCIES"

# Check for composer.json
if [ -f composer.json ]; then
    if command -v composer &> /dev/null; then
        print_info "Installing PHP dependencies..."
        composer install --no-dev --optimize-autoloader
        print_success "PHP dependencies installed"
    else
        print_warning "Composer not found - skipping PHP dependencies"
    fi
fi

# Check for package.json
if [ -f package.json ]; then
    if command -v npm &> /dev/null; then
        print_info "Installing Node dependencies..."
        npm ci --production
        print_success "Node dependencies installed"
    else
        print_warning "NPM not found - skipping Node dependencies"
    fi
fi

print_header "DATABASE MIGRATIONS"

# Check for pending migrations
if [ -f database_fixes.sql ]; then
    print_warning "Found database_fixes.sql"
    read -p "Run database migrations? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < database_fixes.sql 2>/dev/null
        if [ $? -eq 0 ]; then
            print_success "Database migrations completed"
            # Remove the file after successful migration
            mv database_fixes.sql "$BACKUP_DIR/"
        else
            print_error "Database migration failed"
        fi
    fi
fi

print_header "CACHE CLEARING"

# Clear application cache
if [ -d cache ]; then
    print_info "Clearing cache..."
    rm -rf cache/*
    print_success "Cache cleared"
fi

# Clear OPcache if available
if command -v php &> /dev/null; then
    php -r "if(function_exists('opcache_reset')) { opcache_reset(); echo 'OPcache cleared'; }"
    echo ""
fi

print_header "FILE PERMISSIONS"

# Fix file permissions
if [ -f fix-permissions.sh ]; then
    print_info "Fixing file permissions..."
    bash fix-permissions.sh
else
    print_warning "Permission fixer script not found"
fi

print_header "SECURITY CHECKS"

# Remove setup script if it exists
if [ -f setup.php ]; then
    print_warning "setup.php found - removing for security"
    rm setup.php
    print_success "setup.php removed"
fi

# Check environment setting
ENV_SETTING=$(grep "define('ENV'" includes/config.php | cut -d"'" -f4)
if [ "$ENV_SETTING" != "production" ]; then
    print_warning "Environment is set to: $ENV_SETTING"
    print_warning "Consider setting ENV to 'production' in config.php"
else
    print_success "Environment set to production"
fi

print_header "POST-DEPLOYMENT TESTS"

# Test homepage
print_info "Testing homepage..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/)
if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "503" ]; then
    print_success "Homepage responds with: $HTTP_CODE"
else
    print_warning "Homepage returned: $HTTP_CODE"
fi

# Test admin page
print_info "Testing admin page..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/admin/login.php)
if [ "$HTTP_CODE" = "200" ]; then
    print_success "Admin page accessible"
else
    print_warning "Admin page returned: $HTTP_CODE"
fi

print_header "DISABLE MAINTENANCE MODE"

# Disable maintenance mode
print_info "Disabling maintenance mode..."
if [ -f includes/config.php ]; then
    mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" \
        -e "UPDATE site_settings SET setting_value = '0' WHERE setting_key = 'maintenance_mode';" 2>/dev/null
    
    if [ $? -eq 0 ]; then
        print_success "Maintenance mode disabled"
    else
        print_warning "Could not disable maintenance mode - do it manually"
    fi
fi

print_header "DEPLOYMENT SUMMARY"

print_success "Deployment completed successfully!"
echo ""
print_info "Deployed branch: $DEPLOY_BRANCH"
print_info "Current commit: $LATEST_COMMIT"
print_info "Backup location: $BACKUP_DIR"
echo ""

print_header "POST-DEPLOYMENT CHECKLIST"

echo "Please verify:"
echo "  [ ] Website is accessible"
echo "  [ ] Admin panel works"
echo "  [ ] Images load correctly"
echo "  [ ] Forms submit properly"
echo "  [ ] No error messages displayed"
echo ""

print_warning "Important reminders:"
echo "  1. Monitor error logs: tail -f logs/error.log"
echo "  2. Check application performance"
echo "  3. Verify all features are working"
echo "  4. Keep the backup for at least 7 days"
echo ""

# Create deployment log
DEPLOY_LOG="logs/deployments.log"
mkdir -p logs
echo "$(date '+%Y-%m-%d %H:%M:%S') - Deployed $DEPLOY_BRANCH ($LATEST_COMMIT)" >> "$DEPLOY_LOG"
print_success "Deployment logged to $DEPLOY_LOG"

print_success "Deployment complete!"