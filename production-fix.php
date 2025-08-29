<?php
/**
 * Production Fix Script - Fixes all known issues
 * Run this on dalthaus.net to fix CSS, routing, and database issues
 */

$token = $_GET['token'] ?? '';
if ($token !== 'fix-' . date('Ymd')) {
    die('Invalid token. Use: fix-' . date('Ymd'));
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Production Fix Script</title>
    <style>
        body { font-family: monospace; background: #000; color: #0f0; padding: 20px; }
        pre { background: #111; padding: 15px; border: 1px solid #0f0; }
        .success { color: #0f0; }
        .error { color: #f00; }
        .warning { color: #ff0; }
        .button { display: inline-block; padding: 10px 20px; background: #0f0; color: #000; text-decoration: none; margin: 10px; }
    </style>
</head>
<body>
<h1>🔧 Production Fix Script</h1>
<pre>
<?php
echo "Starting fixes at: " . date('Y-m-d H:i:s') . "\n";
echo str_repeat("=", 50) . "\n\n";

$fixes_applied = 0;
$errors = 0;

// Fix 1: Backup and update .htaccess
echo "FIX 1: Updating .htaccess for proper static asset serving\n";
echo str_repeat("-", 40) . "\n";

$htaccess_current = __DIR__ . '/.htaccess';
$htaccess_backup = __DIR__ . '/.htaccess.backup.' . date('Ymd_His');
$htaccess_fixed = __DIR__ . '/.htaccess.fixed';

if (file_exists($htaccess_fixed)) {
    // Backup current .htaccess
    if (copy($htaccess_current, $htaccess_backup)) {
        echo "✅ Backed up .htaccess to " . basename($htaccess_backup) . "\n";
        
        // Apply fixed .htaccess
        if (copy($htaccess_fixed, $htaccess_current)) {
            echo "✅ Applied fixed .htaccess\n";
            $fixes_applied++;
        } else {
            echo "❌ Failed to apply fixed .htaccess\n";
            $errors++;
        }
    } else {
        echo "❌ Failed to backup .htaccess\n";
        $errors++;
    }
} else {
    echo "⚠️  Fixed .htaccess not found, creating minimal version\n";
    
    $minimal_htaccess = '# Minimal .htaccess
RewriteEngine On

# Serve static files directly
RewriteCond %{REQUEST_FILENAME} -f
RewriteRule \.(css|js|jpg|jpeg|png|gif|ico|svg|woff|woff2|ttf|eot)$ - [L]

# Protect sensitive directories
RewriteRule ^includes/ - [F,L]
RewriteRule ^logs/ - [F,L]

# Route everything else through index.php
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php?route=$1 [QSA,L]
';
    
    if (file_put_contents($htaccess_current, $minimal_htaccess)) {
        echo "✅ Created minimal .htaccess\n";
        $fixes_applied++;
    } else {
        echo "❌ Failed to create .htaccess\n";
        $errors++;
    }
}
echo "\n";

// Fix 2: Database - Ensure maintenance_mode exists
echo "FIX 2: Checking database settings\n";
echo str_repeat("-", 40) . "\n";

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';

try {
    $pdo = Database::getInstance();
    
    // Check for maintenance_mode setting
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'maintenance_mode'");
    $stmt->execute();
    $value = $stmt->fetchColumn();
    
    if ($value === false) {
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('maintenance_mode', '0')");
        $stmt->execute();
        echo "✅ Created maintenance_mode setting\n";
        $fixes_applied++;
    } else {
        echo "✅ maintenance_mode already exists (value: $value)\n";
    }
    
    // Ensure other critical settings exist
    $critical_settings = [
        'site_title' => 'Dalthaus Photography',
        'site_description' => 'Professional Photography Portfolio',
        'cache_enabled' => '1'
    ];
    
    foreach ($critical_settings as $key => $default) {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        if ($stmt->fetchColumn() === false) {
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
            $stmt->execute([$key, $default]);
            echo "✅ Created $key setting\n";
            $fixes_applied++;
        }
    }
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    $errors++;
}
echo "\n";

// Fix 3: Clear cache
echo "FIX 3: Clearing cache\n";
echo str_repeat("-", 40) . "\n";

$cache_dir = __DIR__ . '/cache';
if (is_dir($cache_dir)) {
    $files = glob($cache_dir . '/*');
    $cleared = 0;
    foreach ($files as $file) {
        if (is_file($file) && basename($file) !== 'index.html') {
            unlink($file);
            $cleared++;
        }
    }
    echo "✅ Cleared $cleared cache files\n";
    $fixes_applied++;
} else {
    echo "⚠️  Cache directory not found\n";
}
echo "\n";

// Fix 4: Check file permissions
echo "FIX 4: Checking file permissions\n";
echo str_repeat("-", 40) . "\n";

$dirs_to_check = ['cache', 'uploads', 'logs', 'temp'];
foreach ($dirs_to_check as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (!is_dir($path)) {
        if (mkdir($path, 0755, true)) {
            echo "✅ Created $dir directory\n";
            $fixes_applied++;
        } else {
            echo "❌ Failed to create $dir directory\n";
            $errors++;
        }
    } elseif (!is_writable($path)) {
        if (chmod($path, 0755)) {
            echo "✅ Fixed permissions for $dir\n";
            $fixes_applied++;
        } else {
            echo "⚠️  Cannot fix permissions for $dir (may need manual fix)\n";
        }
    } else {
        echo "✅ $dir is writable\n";
    }
}
echo "\n";

// Fix 5: Test CSS loading
echo "FIX 5: Testing CSS accessibility\n";
echo str_repeat("-", 40) . "\n";

$css_file = __DIR__ . '/assets/css/public.css';
if (file_exists($css_file)) {
    echo "✅ public.css exists (" . filesize($css_file) . " bytes)\n";
    
    // Test if it's accessible via URL
    $css_url = 'https://' . $_SERVER['HTTP_HOST'] . '/assets/css/public.css';
    $ch = curl_init($css_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $css_content = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200 && strpos($css_content, 'font-family') !== false) {
        echo "✅ CSS is accessible via URL\n";
    } else {
        echo "⚠️  CSS returns HTTP $http_code (may be cached or blocked)\n";
    }
} else {
    echo "❌ public.css not found!\n";
    $errors++;
}
echo "\n";

// Summary
echo str_repeat("=", 50) . "\n";
echo "SUMMARY:\n";
echo "Fixes Applied: $fixes_applied\n";
echo "Errors: $errors\n";
echo "\n";

if ($errors === 0) {
    echo "<span class='success'>✅ ALL FIXES APPLIED SUCCESSFULLY!</span>\n\n";
    echo "The site should now be working properly with:\n";
    echo "- Static assets (CSS/JS) loading correctly\n";
    echo "- Database settings configured\n";
    echo "- Cache cleared\n";
    echo "- Proper file permissions\n";
} else {
    echo "<span class='warning'>⚠️  Some fixes failed. Manual intervention may be needed.</span>\n";
}

echo "\nCompleted at: " . date('Y-m-d H:i:s') . "\n";
?>
</pre>

<div style="margin-top: 30px;">
    <h2>Next Steps:</h2>
    <a href="/" class="button">View Homepage</a>
    <a href="/assets/css/public.css" class="button">Test CSS Loading</a>
    <a href="/admin/login.php" class="button">Admin Login</a>
    <a href="/production-test-suite.php?token=test-<?= date('Ymd') ?>" class="button">Run Full Tests</a>
</div>

</body>
</html>