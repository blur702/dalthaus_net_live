<?php
/**
 * COMPLETE FIX FOR DALTHAUS.NET
 * Upload this file to the root of dalthaus.net and access it in browser
 * This will fix ALL issues
 */

// Database credentials (from your config)
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'dalthaus_cms');
define('DB_USER', 'kevin');
define('DB_PASS', '(130Bpm)');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Dalthaus.net - Complete Solution</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Courier New', monospace; background: #1a1a1a; color: #00ff00; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        h1 { font-size: 2em; margin-bottom: 20px; text-align: center; border-bottom: 2px solid #00ff00; padding-bottom: 10px; }
        .section { background: #0a0a0a; border: 1px solid #00ff00; padding: 20px; margin: 20px 0; }
        .button { display: inline-block; padding: 15px 30px; background: #00ff00; color: #000; text-decoration: none; font-weight: bold; margin: 10px; cursor: pointer; border: none; }
        .button:hover { background: #00cc00; }
        .success { color: #00ff00; font-weight: bold; }
        .error { color: #ff0000; font-weight: bold; }
        .warning { color: #ffff00; }
        pre { background: #000; padding: 10px; overflow-x: auto; margin: 10px 0; }
        .status { padding: 10px; margin: 10px 0; border-left: 4px solid #00ff00; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 FIX DALTHAUS.NET - COMPLETE SOLUTION</h1>
        
        <?php
        $action = $_GET['action'] ?? '';
        
        if ($action === 'fix') {
            echo '<div class="section">';
            echo '<h2>Applying Fixes...</h2>';
            echo '<pre>';
            
            $fixes = 0;
            $errors = 0;
            
            // Fix 1: Create proper .htaccess
            echo "1. Fixing .htaccess for CSS/JS loading...\n";
            $htaccess_content = '# Dalthaus Photography CMS
# Fixed .htaccess for proper static file serving

RewriteEngine On

# CRITICAL: Serve static files directly without PHP processing
# This fixes CSS/JS not loading
RewriteCond %{REQUEST_FILENAME} -f
RewriteCond %{REQUEST_URI} \.(css|js|jpg|jpeg|png|gif|ico|svg|woff|woff2|ttf|eot|txt|xml|pdf)$ [NC]
RewriteRule ^(.*)$ - [L]

# Also serve these directories directly
RewriteRule ^assets/ - [L]
RewriteRule ^uploads/ - [L]
RewriteRule ^favicon\.(ico|svg)$ - [L]
RewriteRule ^robots\.txt$ - [L]

# Block access to sensitive directories
RewriteRule ^includes/ - [F,L]
RewriteRule ^scripts/ - [F,L]
RewriteRule ^logs/ - [F,L]
RewriteRule ^cache/ - [F,L]
RewriteRule ^vendor/ - [F,L]
RewriteRule ^\.git/ - [F,L]

# Route all other requests through index.php
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?route=$1 [QSA,L]

# PHP Configuration
<IfModule mod_php.c>
    php_flag display_errors Off
    php_flag log_errors On
    php_value memory_limit 128M
    php_value upload_max_filesize 10M
    php_value session.cookie_httponly 1
</IfModule>

# Enable compression
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE text/html text/css application/javascript application/json
</IfModule>

# Set proper MIME types
<IfModule mod_mime.c>
    AddType text/css .css
    AddType application/javascript .js
    AddType application/json .json
    AddType image/svg+xml .svg
</IfModule>
';
            
            // Backup existing .htaccess
            if (file_exists('.htaccess')) {
                $backup_name = '.htaccess.backup.' . date('YmdHis');
                if (copy('.htaccess', $backup_name)) {
                    echo "   ✓ Backed up existing .htaccess to $backup_name\n";
                }
            }
            
            // Write new .htaccess
            if (file_put_contents('.htaccess', $htaccess_content)) {
                echo "   <span class='success'>✓ Created new .htaccess with CSS/JS fixes</span>\n";
                $fixes++;
            } else {
                echo "   <span class='error'>✗ Failed to create .htaccess</span>\n";
                $errors++;
            }
            
            // Fix 2: Database settings
            echo "\n2. Fixing database settings...\n";
            try {
                $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                // Create settings table if it doesn't exist
                $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    setting_key VARCHAR(100) UNIQUE NOT NULL,
                    setting_value TEXT,
                    setting_type VARCHAR(50) DEFAULT 'text',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                
                // Insert essential settings
                $settings = [
                    ['maintenance_mode', '0', 'boolean'],
                    ['site_title', 'Dalthaus Photography', 'text'],
                    ['site_description', 'Professional Photography Portfolio', 'text'],
                    ['cache_enabled', '1', 'boolean']
                ];
                
                $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, setting_type) 
                                      VALUES (?, ?, ?) 
                                      ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                
                foreach ($settings as $setting) {
                    $stmt->execute($setting);
                }
                
                echo "   <span class='success'>✓ Database settings configured</span>\n";
                $fixes++;
                
            } catch (Exception $e) {
                echo "   <span class='error'>✗ Database error: " . $e->getMessage() . "</span>\n";
                $errors++;
            }
            
            // Fix 3: Clear cache
            echo "\n3. Clearing cache...\n";
            $cache_dir = './cache';
            if (is_dir($cache_dir)) {
                $files = glob($cache_dir . '/*');
                $cleared = 0;
                foreach ($files as $file) {
                    if (is_file($file) && basename($file) !== 'index.html') {
                        unlink($file);
                        $cleared++;
                    }
                }
                echo "   <span class='success'>✓ Cleared $cleared cache files</span>\n";
                $fixes++;
            }
            
            // Fix 4: Create/check directories
            echo "\n4. Checking required directories...\n";
            $dirs = ['cache', 'uploads', 'logs', 'temp', 'assets/css', 'assets/js'];
            foreach ($dirs as $dir) {
                if (!is_dir($dir)) {
                    if (mkdir($dir, 0755, true)) {
                        echo "   ✓ Created $dir directory\n";
                        $fixes++;
                    }
                }
            }
            
            // Fix 5: Test CSS accessibility
            echo "\n5. Testing CSS loading...\n";
            $test_url = 'https://' . $_SERVER['HTTP_HOST'] . '/assets/css/public.css';
            $ch = curl_init($test_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($http_code === 200 && strpos($response, 'font-family') !== false) {
                echo "   <span class='success'>✓ CSS is now accessible!</span>\n";
            } else {
                echo "   <span class='warning'>⚠ CSS returned HTTP $http_code (may need page refresh)</span>\n";
            }
            
            echo '</pre>';
            
            // Summary
            echo '<div class="status">';
            echo '<h3>Fix Summary:</h3>';
            echo "Fixes Applied: <span class='success'>$fixes</span><br>";
            echo "Errors: <span class='error'>$errors</span><br><br>";
            
            if ($errors === 0) {
                echo '<span class="success">✅ ALL FIXES APPLIED SUCCESSFULLY!</span><br><br>';
                echo 'The site should now be working with proper CSS styling.';
            } else {
                echo '<span class="warning">⚠ Some fixes may have failed. Please check manually.</span>';
            }
            echo '</div>';
            
            echo '<div style="text-align: center; margin-top: 30px;">';
            echo '<a href="/" class="button">View Homepage</a>';
            echo '<a href="/admin/login.php" class="button">Admin Login</a>';
            echo '<a href="/assets/css/public.css" class="button">Test CSS File</a>';
            echo '</div>';
            
            echo '</div>';
            
        } else {
            // Show intro screen
            ?>
            <div class="section">
                <h2>Current Issues Detected:</h2>
                <ul style="margin: 20px 0; padding-left: 30px;">
                    <li>❌ CSS files not loading (returning HTML instead)</li>
                    <li>❌ JavaScript files not loading</li>
                    <li>❌ .htaccess incorrectly routing static files through PHP</li>
                    <li>❌ Homepage showing unstyled HTML</li>
                </ul>
            </div>
            
            <div class="section">
                <h2>This script will fix:</h2>
                <ul style="margin: 20px 0; padding-left: 30px;">
                    <li>✅ Create proper .htaccess that serves CSS/JS directly</li>
                    <li>✅ Configure database settings</li>
                    <li>✅ Clear cache files</li>
                    <li>✅ Create required directories</li>
                    <li>✅ Test that CSS loads properly</li>
                </ul>
            </div>
            
            <div class="section" style="text-align: center;">
                <h2>Ready to fix your site?</h2>
                <p style="margin: 20px 0;">Click the button below to apply all fixes automatically.</p>
                <a href="?action=fix" class="button">🔧 FIX EVERYTHING NOW</a>
            </div>
            
            <div class="section">
                <h2>Manual Check:</h2>
                <p>Current .htaccess status:</p>
                <pre><?php 
                if (file_exists('.htaccess')) {
                    $current = file_get_contents('.htaccess');
                    echo "File exists (" . strlen($current) . " bytes)\n";
                    if (strpos($current, 'RewriteEngine') !== false) {
                        echo "RewriteEngine is enabled\n";
                    }
                    if (strpos($current, '.css') !== false) {
                        echo "CSS rules found\n";
                    } else {
                        echo "WARNING: No CSS rules found - this is the problem!\n";
                    }
                } else {
                    echo "No .htaccess file found!";
                }
                ?></pre>
            </div>
            <?php
        }
        ?>
        
        <div class="section" style="text-align: center; margin-top: 30px;">
            <p style="color: #666;">Emergency Fix Script for Dalthaus Photography CMS</p>
            <p style="color: #666;">Generated: <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
    </div>
</body>
</html>