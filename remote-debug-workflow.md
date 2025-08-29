# Remote Debugging Workflow Without Claude Code

## How I Can Help Debug Your Shared Hosting 500 Errors

### Option 1: SSH Session Relay
You run commands via SSH and paste the output back to me:

1. **Connect to your server:**
```bash
ssh dalthaus@yourdomain.com
cd ~/public_html
```

2. **Run diagnostic commands and share output:**
```bash
# I'll ask you to run specific commands like:
tail -50 error_log
php -l includes/config.php
ls -la
```

3. **I analyze the output and provide fixes**

### Option 2: Diagnostic Script Method
1. **Upload debug-remote.php** (I created this for you)
2. **Visit the URL** and copy the full output
3. **Share the output with me**
4. **I'll identify the exact issue**

### Option 3: Error Log Analysis
1. **Download your error logs:**
```bash
# Via SSH
scp username@yourdomain.com:~/public_html/error_log ./error_log_backup.txt

# Or via cPanel/FTP
# Download: error_log, logs/app.log
```

2. **Share the log contents with me**
3. **I'll pinpoint the issues**

## Quick Start Debugging Process

### Step 1: Get Initial Diagnostics
Run this single command via SSH and share the output:
```bash
echo "=== PHP Version ===" && php -v && \
echo -e "\n=== Last 10 Errors ===" && tail -10 error_log 2>/dev/null && \
echo -e "\n=== Directory List ===" && ls -la && \
echo -e "\n=== Config Check ===" && php -l includes/config.php && \
echo -e "\n=== Permissions ===" && ls -la includes/
```

### Step 2: Test Specific Issue
Based on the output, I'll have you run targeted commands:

**For Database Issues:**
```bash
php -r "
require_once 'includes/config.php';
try {
    \$pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS);
    echo 'DB: OK';
} catch (Exception \$e) {
    echo 'DB Error: ' . \$e->getMessage();
}"
```

**For Permission Issues:**
```bash
find . -type f ! -perm 644 -exec ls -la {} \; | head -10
find . -type d ! -perm 755 -exec ls -ld {} \; | head -10
```

**For .htaccess Issues:**
```bash
grep -n "Options\|FollowSymLinks\|Indexes" .htaccess
```

### Step 3: Apply Fixes
I'll provide exact commands to fix issues:

```bash
# Examples of fixes I might provide:

# Fix permissions
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;

# Fix .htaccess
sed -i '/Options/d' .htaccess

# Fix session path
mkdir -p tmp && chmod 777 tmp
echo "ini_set('session.save_path', __DIR__ . '/tmp');" >> includes/config.php

# Fix memory limit
echo "php_value memory_limit 256M" >> .htaccess
```

## Common Issues I Can Diagnose Remotely

1. **500 Internal Server Error**
   - Permission problems (files not 644, dirs not 755)
   - .htaccess syntax errors or forbidden directives
   - PHP parse errors in code

2. **White Screen of Death**
   - PHP fatal errors (missing functions, memory exhausted)
   - Database connection failures
   - Missing required extensions

3. **Database Errors**
   - Wrong credentials in config
   - Different host required (not localhost)
   - Missing tables or wrong prefix

4. **Session Errors**
   - Session path not writable
   - Session handler misconfigured
   - Cookie domain issues

5. **File Upload Issues**
   - Upload directory not writable
   - PHP upload limits too low
   - Missing temp directory

## Shared Hosting Specific Checks

```bash
# 1. Check PHP version and handler
php -v
php -i | grep "Server API"

# 2. Check disabled functions (common on shared hosting)
php -i | grep disable_functions

# 3. Check open_basedir restrictions
php -i | grep open_basedir

# 4. Check MySQL socket path (often different on shared hosting)
php -i | grep mysql.default_socket

# 5. Check mod_rewrite
php -r "echo function_exists('apache_get_modules') ? print_r(apache_get_modules(), true) : 'Cannot check';"
```

## Working Together Process

1. **You provide:**
   - SSH access to run commands
   - Error messages you're seeing
   - What triggers the 500 error (specific pages?)
   - Your hosting provider name

2. **I provide:**
   - Exact commands to run
   - Analysis of output
   - Step-by-step fixes
   - Modified files if needed

3. **We iterate:**
   - Run diagnostic command
   - Share output
   - Apply fix
   - Test result
   - Repeat until resolved

## Emergency Fix Kit

If you need to get the site working ASAP, run:

```bash
# Create emergency fix script
cat > emergency-fix.sh << 'EOFIX'
#!/bin/bash
echo "Applying emergency fixes..."

# 1. Fix permissions
echo "Fixing permissions..."
find . -type f -exec chmod 644 {} \; 2>/dev/null
find . -type d -exec chmod 755 {} \; 2>/dev/null
chmod 755 uploads cache logs temp 2>/dev/null

# 2. Backup and simplify .htaccess
echo "Fixing .htaccess..."
cp .htaccess .htaccess.backup
grep -v "Options\|FollowSymLinks" .htaccess > .htaccess.tmp
mv .htaccess.tmp .htaccess

# 3. Create temp directories
echo "Creating temp directories..."
mkdir -p tmp/sessions cache logs uploads temp
chmod 777 tmp/sessions

# 4. Add error display temporarily
echo "Enabling error display..."
echo "<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
require_once 'index-original.php';" > index.php.debug
cp index.php index-original.php
cp index.php.debug index.php

echo "Emergency fixes applied!"
echo "Visit your site and check for specific errors"
EOFIX

chmod +x emergency-fix.sh
./emergency-fix.sh
```

## Ready to Start?

1. Connect to your server via SSH
2. Run the initial diagnostic command from Step 1
3. Share the output with me
4. I'll guide you through fixing the 500 errors

No need to install anything on the server - we'll work with what's there!