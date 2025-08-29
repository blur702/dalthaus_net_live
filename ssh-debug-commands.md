# SSH Debugging Commands for Shared Hosting

## Quick 500 Error Diagnosis via SSH

### 1. Connect to your server
```bash
ssh dalthaus@yourdomain.com
```

### 2. Navigate to your web root
```bash
cd ~/public_html  # Based on your cPanel structure
```

### 3. Check Recent PHP Errors
```bash
# View PHP error log (location varies by host)
tail -50 error_log
# OR
tail -50 ../logs/error_log
# OR  
tail -50 /home/username/logs/yourdomain.com/http/error.log

# Check Apache error log if accessible
tail -50 /usr/local/apache/logs/error_log
```

### 4. Test PHP Configuration
```bash
# Create a test file to check PHP
echo '<?php phpinfo(); ?>' > test-info.php

# Check PHP version
php -v

# List loaded modules
php -m

# Test your config file
php -l includes/config.php
```

### 5. Check File Permissions (Common 500 Error Cause)
```bash
# Files should be 644, directories 755
find . -type f -exec ls -la {} \; | grep -v "rw-r--r--"
find . -type d -exec ls -ld {} \; | grep -v "rwxr-xr-x"

# Fix permissions if needed
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;

# Make sure these directories are writable
chmod 755 uploads cache logs temp
```

### 6. Check .htaccess Issues
```bash
# Temporarily rename .htaccess to test
mv .htaccess .htaccess.backup

# Test site - if it works, .htaccess has issues
# Common fixes:
cat .htaccess | grep -v "Options" > .htaccess.new  # Remove Options directives
mv .htaccess.new .htaccess
```

### 7. Database Connection Test
```bash
# Test MySQL connection
mysql -h localhost -u your_db_user -p your_db_name -e "SHOW TABLES;"

# Create a quick PHP test
cat > test-db.php << 'EOF'
<?php
require_once 'includes/config.php';
try {
    $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS);
    echo "Database connected successfully\n";
    $tables = $pdo->query("SHOW TABLES")->fetchAll();
    echo "Tables: " . count($tables) . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
EOF

php test-db.php
```

### 8. Enable Error Display Temporarily
```bash
# Add to top of index.php temporarily
sed -i '2i\
error_reporting(E_ALL);\
ini_set("display_errors", "1");\
ini_set("display_startup_errors", "1");' index.php

# Remember to remove after debugging!
```

### 9. Check Memory Limits
```bash
# View PHP memory limit
php -i | grep memory_limit

# Add to .htaccess if needed
echo "php_value memory_limit 256M" >> .htaccess
```

### 10. Common Shared Hosting Fixes

#### A. Fix Session Path
```bash
# Create local session directory
mkdir -p tmp/sessions
chmod 777 tmp/sessions

# Add to config.php
echo "ini_set('session.save_path', dirname(__DIR__) . '/tmp/sessions');" >> includes/config.php
```

#### B. Disable Problem Functions
```bash
# Some hosts disable certain functions
php -i | grep disable_functions
```

#### C. Fix Include Paths
```bash
# Add to top of index.php
echo "set_include_path(get_include_path() . PATH_SEPARATOR . dirname(__FILE__));" >> index.php
```

### 11. Live Monitoring
```bash
# Watch error log in real-time while testing
tail -f error_log

# In another SSH session, trigger the error
curl -I https://yourdomain.com
```

### 12. Create Full Diagnostic Report
```bash
# Run this to create a full report
cat > diagnose.sh << 'EOF'
#!/bin/bash
echo "=== PHP Version ==="
php -v
echo -e "\n=== PHP Modules ==="
php -m
echo -e "\n=== Error Log (last 20) ==="
tail -20 error_log 2>/dev/null || echo "No error_log found"
echo -e "\n=== Directory Permissions ==="
ls -la
echo -e "\n=== Config File Check ==="
php -l includes/config.php
echo -e "\n=== Memory Usage ==="
php -r "echo 'Memory limit: ' . ini_get('memory_limit') . PHP_EOL;"
echo -e "\n=== Database Test ==="
php -r "
require_once 'includes/config.php';
try {
    \$pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS);
    echo 'Database: CONNECTED' . PHP_EOL;
} catch (Exception \$e) {
    echo 'Database: FAILED - ' . \$e->getMessage() . PHP_EOL;
}"
EOF

chmod +x diagnose.sh
./diagnose.sh > diagnostic_report.txt
cat diagnostic_report.txt
```

## Clean Up After Debugging
```bash
# Remove test files
rm -f test-info.php test-db.php debug-remote.php diagnose.sh

# Restore .htaccess if backed up
[ -f .htaccess.backup ] && mv .htaccess.backup .htaccess

# Remove debug lines from index.php
sed -i '/error_reporting(E_ALL)/d' index.php
sed -i '/ini_set("display_errors"/d' index.php
sed -i '/ini_set("display_startup_errors"/d' index.php
```

## If You Can't Access SSH

Upload `debug-remote.php` via FTP/cPanel:
1. Edit line 10: Replace 'YOUR_IP_HERE' with your actual IP
2. Upload to your web root
3. Visit: https://yourdomain.com/debug-remote.php
4. **DELETE immediately after debugging**

## Most Common 500 Error Causes on Shared Hosting

1. **Wrong file permissions** (should be 644 for files, 755 for directories)
2. **Invalid .htaccess directives** (Options, FollowSymLinks often disabled)
3. **PHP version mismatch** (need PHP 7.4+ for this CMS)
4. **Missing PHP extensions** (pdo_mysql, mbstring required)
5. **Memory limit too low** (increase to 256M)
6. **Database connection failed** (wrong credentials or host)
7. **Session save path not writable**
8. **Incorrect include paths**