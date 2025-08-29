#!/bin/bash

# =====================================
# PERMISSION FIXER SCRIPT
# For Dalthaus Photography CMS
# =====================================

echo "=================================="
echo "PERMISSION FIXER FOR DALTHAUS CMS"
echo "=================================="
echo ""

# Get the directory where script is located
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[0;33m'
NC='\033[0m' # No Color

# Function to print colored output
print_success() {
    echo -e "${GREEN}✓${NC} $1"
}

print_error() {
    echo -e "${RED}✗${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}⚠${NC} $1"
}

print_info() {
    echo -e "→ $1"
}

# Check if running as root (not recommended)
if [ "$EUID" -eq 0 ]; then 
   print_warning "Running as root is not recommended for web files"
   read -p "Continue anyway? (y/n) " -n 1 -r
   echo
   if [[ ! $REPLY =~ ^[Yy]$ ]]; then
       exit 1
   fi
fi

# Detect web server user
print_info "Detecting web server user..."
WEB_USER=""
WEB_GROUP=""

# Try to detect Apache user
if [ -f /etc/apache2/envvars ]; then
    WEB_USER=$(grep -E "^export APACHE_RUN_USER" /etc/apache2/envvars | cut -d= -f2)
    WEB_GROUP=$(grep -E "^export APACHE_RUN_GROUP" /etc/apache2/envvars | cut -d= -f2)
    print_success "Apache detected: $WEB_USER:$WEB_GROUP"
elif [ -f /etc/httpd/conf/httpd.conf ]; then
    WEB_USER=$(grep "^User" /etc/httpd/conf/httpd.conf | awk '{print $2}')
    WEB_GROUP=$(grep "^Group" /etc/httpd/conf/httpd.conf | awk '{print $2}')
    print_success "Apache detected: $WEB_USER:$WEB_GROUP"
# Try to detect Nginx user
elif [ -f /etc/nginx/nginx.conf ]; then
    WEB_USER=$(grep "^user" /etc/nginx/nginx.conf | awk '{print $2}' | tr -d ';')
    WEB_GROUP=$WEB_USER
    print_success "Nginx detected: $WEB_USER:$WEB_GROUP"
else
    # Common defaults
    if id "www-data" &>/dev/null; then
        WEB_USER="www-data"
        WEB_GROUP="www-data"
    elif id "apache" &>/dev/null; then
        WEB_USER="apache"
        WEB_GROUP="apache"
    elif id "nginx" &>/dev/null; then
        WEB_USER="nginx"
        WEB_GROUP="nginx"
    elif id "httpd" &>/dev/null; then
        WEB_USER="httpd"
        WEB_GROUP="httpd"
    fi
fi

# If still not found, ask user
if [ -z "$WEB_USER" ]; then
    print_warning "Could not auto-detect web server user"
    read -p "Enter web server user (e.g., www-data, apache): " WEB_USER
    read -p "Enter web server group (e.g., www-data, apache): " WEB_GROUP
fi

print_info "Using web server user: $WEB_USER:$WEB_GROUP"
echo ""

# Function to set permissions
set_permissions() {
    local path=$1
    local dir_perms=$2
    local file_perms=$3
    local owner=$4
    
    if [ -e "$path" ]; then
        if [ -d "$path" ]; then
            chmod $dir_perms "$path" 2>/dev/null
            if [ $? -eq 0 ]; then
                print_success "Set $dir_perms on directory: $path"
            else
                print_error "Failed to set permissions on: $path"
            fi
            
            if [ "$owner" = "web" ]; then
                chown $WEB_USER:$WEB_GROUP "$path" 2>/dev/null
                if [ $? -eq 0 ]; then
                    print_success "Set owner $WEB_USER:$WEB_GROUP on: $path"
                else
                    print_warning "Could not change owner on: $path (may need sudo)"
                fi
            fi
        fi
    else
        print_warning "Path does not exist: $path"
    fi
}

# Set general directory permissions
print_info "Setting general permissions..."
echo ""

# Root directory - 755
chmod 755 . 2>/dev/null && print_success "Set 755 on root directory" || print_error "Failed to set root permissions"

# All directories - 755
find . -type d -exec chmod 755 {} \; 2>/dev/null && print_success "Set 755 on all directories" || print_error "Failed to set directory permissions"

# All files - 644
find . -type f -exec chmod 644 {} \; 2>/dev/null && print_success "Set 644 on all files" || print_error "Failed to set file permissions"

echo ""
print_info "Setting specific directory permissions..."
echo ""

# Writable directories (need web server write access)
WRITABLE_DIRS=(
    "uploads"
    "cache" 
    "logs"
    "temp"
)

for dir in "${WRITABLE_DIRS[@]}"; do
    if [ ! -d "$dir" ]; then
        mkdir -p "$dir" 2>/dev/null
        if [ $? -eq 0 ]; then
            print_success "Created directory: $dir"
        else
            print_error "Failed to create: $dir"
        fi
    fi
    
    # Set permissions
    chmod 775 "$dir" 2>/dev/null
    if [ $? -eq 0 ]; then
        print_success "Set 775 on: $dir"
    else
        print_error "Failed to set permissions on: $dir"
    fi
    
    # Try to set owner (may need sudo)
    chown -R $WEB_USER:$WEB_GROUP "$dir" 2>/dev/null
    if [ $? -eq 0 ]; then
        print_success "Set owner $WEB_USER:$WEB_GROUP on: $dir"
    else
        print_warning "Could not change owner on: $dir (may need sudo)"
    fi
    
    # Create index.html for security
    if [ ! -f "$dir/index.html" ]; then
        echo '<!DOCTYPE html><html><head><title>403</title></head><body>Forbidden</body></html>' > "$dir/index.html"
        print_success "Created index.html in: $dir"
    fi
done

echo ""
print_info "Setting script permissions..."
echo ""

# Executable scripts
EXECUTABLE_SCRIPTS=(
    "setup.php"
    "fix-permissions.sh"
    "deploy.sh"
    "scripts/converter.py"
)

for script in "${EXECUTABLE_SCRIPTS[@]}"; do
    if [ -f "$script" ]; then
        chmod 755 "$script" 2>/dev/null
        if [ $? -eq 0 ]; then
            print_success "Set 755 (executable) on: $script"
        else
            print_error "Failed to set executable on: $script"
        fi
    fi
done

echo ""
print_info "Protecting sensitive files..."
echo ""

# Protected files (no web access)
PROTECTED_FILES=(
    ".env"
    "composer.json"
    "composer.lock"
    "package.json"
    "package-lock.json"
    ".git"
    ".gitignore"
    "*.sql"
    "*.log"
    "*.md"
)

for pattern in "${PROTECTED_FILES[@]}"; do
    for file in $pattern; do
        if [ -e "$file" ]; then
            chmod 600 "$file" 2>/dev/null
            if [ $? -eq 0 ]; then
                print_success "Protected: $file"
            fi
        fi
    done
done

echo ""
print_info "Checking .htaccess file..."
echo ""

# Check .htaccess
if [ ! -f ".htaccess" ]; then
    if [ -f ".htaccess.production" ]; then
        cp .htaccess.production .htaccess
        print_success "Created .htaccess from production template"
    else
        print_warning ".htaccess not found"
    fi
else
    print_success ".htaccess exists"
fi

# Set .htaccess permissions
if [ -f ".htaccess" ]; then
    chmod 644 .htaccess
    print_success "Set 644 on .htaccess"
fi

echo ""
print_info "Verifying permissions..."
echo ""

# Verify critical directories
CRITICAL_PATHS=(
    "uploads:775:Directory for file uploads"
    "cache:775:Cache directory"
    "logs:775:Log directory"
    "includes:755:PHP includes (protected)"
    "admin:755:Admin panel"
    "public:755:Public pages"
    "assets:755:Static assets"
)

for item in "${CRITICAL_PATHS[@]}"; do
    IFS=':' read -r path perms desc <<< "$item"
    if [ -e "$path" ]; then
        current_perms=$(stat -c "%a" "$path" 2>/dev/null)
        if [ "$current_perms" = "$perms" ]; then
            print_success "$path ($perms) - $desc"
        else
            print_warning "$path has $current_perms, expected $perms - $desc"
        fi
    else
        print_error "$path does not exist - $desc"
    fi
done

echo ""
echo "=================================="
print_success "PERMISSION CHECK COMPLETE"
echo "=================================="
echo ""

# Offer to run with sudo if needed
if [ "$EUID" -ne 0 ]; then
    print_info "Some operations may have failed due to lack of permissions."
    print_info "If you see warnings above, you may need to run:"
    echo ""
    echo "    sudo $0"
    echo ""
fi

# Final recommendations
print_info "Recommendations:"
echo "  1. Ensure web server can write to: uploads/, cache/, logs/, temp/"
echo "  2. Protect sensitive files from web access"
echo "  3. Set proper owner:group for web files"
echo "  4. Review and adjust permissions based on your server setup"
echo ""

# SELinux check (for CentOS/RHEL)
if command -v getenforce &> /dev/null; then
    SELINUX_STATUS=$(getenforce)
    if [ "$SELINUX_STATUS" != "Disabled" ]; then
        print_warning "SELinux is $SELINUX_STATUS"
        echo "You may need to set SELinux contexts:"
        echo "  sudo chcon -R -t httpd_sys_content_t ."
        echo "  sudo chcon -R -t httpd_sys_rw_content_t uploads cache logs temp"
    fi
fi

print_success "Script completed!"