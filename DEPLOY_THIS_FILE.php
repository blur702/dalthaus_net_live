<?php
/**
 * SINGLE FILE DEPLOYMENT SOLUTION
 * Upload this ONE file to dalthaus.net and run it
 * It will pull all code from GitHub and fix everything
 */

// No token needed - this is urgent
error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Emergency Deployment</title>
    <style>
        body { font-family: monospace; background: #000; color: #0f0; padding: 20px; }
        pre { background: #111; padding: 20px; border: 1px solid #0f0; }
        .btn { display: inline-block; padding: 15px 30px; background: #0f0; color: #000; text-decoration: none; margin: 10px; font-weight: bold; }
        .btn:hover { background: #0a0; }
    </style>
</head>
<body>
<h1>🚀 EMERGENCY DEPLOYMENT SCRIPT</h1>
<pre>
<?php
$action = $_GET['action'] ?? 'show';

if ($action === 'deploy') {
    echo "STARTING DEPLOYMENT...\n";
    echo str_repeat("=", 50) . "\n\n";
    
    // Step 1: Git Pull
    echo "Step 1: Pulling latest code from GitHub\n";
    echo str_repeat("-", 40) . "\n";
    
    $commands = [
        'cd /home/dalthaus/public_html && git fetch origin main 2>&1',
        'cd /home/dalthaus/public_html && git reset --hard origin/main 2>&1',
        'cd /home/dalthaus/public_html && git clean -fd 2>&1'
    ];
    
    foreach ($commands as $cmd) {
        echo "Running: " . substr($cmd, 32, 50) . "...\n";
        exec($cmd, $output, $return);
        foreach ($output as $line) {
            echo "  $line\n";
        }
        $output = [];
    }
    echo "✅ Code updated from GitHub\n\n";
    
    // Step 2: Fix .htaccess
    echo "Step 2: Fixing .htaccess for static assets\n";
    echo str_repeat("-", 40) . "\n";
    
    $htaccess = '# Fixed .htaccess
RewriteEngine On

# Serve static files directly - CRITICAL FIX
RewriteCond %{REQUEST_FILENAME} -f
RewriteRule \.(css|js|jpg|jpeg|png|gif|ico|svg|woff|woff2|ttf|eot|txt|xml)$ - [L]

# Serve directories with static content
RewriteRule ^assets/ - [L]
RewriteRule ^uploads/ - [L]

# Protect sensitive directories
RewriteRule ^includes/ - [F,L]
RewriteRule ^logs/ - [F,L]
RewriteRule ^\.git/ - [F,L]

# Route everything else through index.php
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?route=$1 [QSA,L]

# PHP Settings
php_flag display_errors Off
php_value memory_limit 128M
php_value upload_max_filesize 10M
';
    
    file_put_contents('/home/dalthaus/public_html/.htaccess', $htaccess);
    echo "✅ .htaccess fixed\n\n";
    
    // Step 3: Fix Database
    echo "Step 3: Fixing database\n";
    echo str_repeat("-", 40) . "\n";
    
    try {
        $pdo = new PDO('mysql:host=127.0.0.1;dbname=dalthaus_cms', 'kevin', '(130Bpm)');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Ensure settings table has correct structure
        $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            setting_type VARCHAR(50) DEFAULT 'text',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )");
        
        // Insert critical settings
        $settings = [
            ['maintenance_mode', '0'],
            ['site_title', 'Dalthaus Photography'],
            ['site_description', 'Professional Photography Portfolio'],
            ['cache_enabled', '1']
        ];
        
        $stmt = $pdo->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
        foreach ($settings as $setting) {
            $stmt->execute($setting);
        }
        
        echo "✅ Database fixed\n\n";
    } catch (Exception $e) {
        echo "❌ Database error: " . $e->getMessage() . "\n\n";
    }
    
    // Step 4: Clear cache
    echo "Step 4: Clearing cache\n";
    echo str_repeat("-", 40) . "\n";
    exec('rm -f /home/dalthaus/public_html/cache/*.php 2>&1', $output);
    echo "✅ Cache cleared\n\n";
    
    // Step 5: Test
    echo "Step 5: Testing site\n";
    echo str_repeat("-", 40) . "\n";
    
    $test_urls = [
        '/' => 'Homepage',
        '/assets/css/public.css' => 'CSS File',
        '/admin/login.php' => 'Admin Login'
    ];
    
    foreach ($test_urls as $path => $name) {
        $url = 'https://dalthaus.net' . $path;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $status = ($code >= 200 && $code < 400) ? '✅' : '❌';
        echo "$status $name: HTTP $code\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "🎉 DEPLOYMENT COMPLETE!\n\n";
    echo "The site should now be working with proper CSS.\n";
    echo '<a href="/" class="btn">View Site</a>';
    
} else {
    echo "This script will:\n";
    echo "1. Pull latest code from GitHub\n";
    echo "2. Fix .htaccess for CSS/JS loading\n";
    echo "3. Fix database settings\n";
    echo "4. Clear cache\n";
    echo "5. Test the site\n\n";
    echo "Ready to deploy?\n";
    echo '</pre>';
    echo '<a href="?action=deploy" class="btn">START DEPLOYMENT</a>';
    echo '<pre>';
}
?>
</pre>
</body>
</html>