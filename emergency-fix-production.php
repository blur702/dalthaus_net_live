<?php
/**
 * Emergency Production Fix
 * Copy this file to https://dalthaus.net/emergency-fix-production.php
 * Then access it in browser to fix the maintenance_mode issue
 */

echo "<pre>";
echo "Emergency Production Fix\n";
echo "========================\n\n";

// First, show configuration
echo "1. Checking configuration...\n";
if (!file_exists('includes/config.php')) {
    die("ERROR: config.php not found!");
}

require_once 'includes/config.php';
echo "   - Database Host: " . DB_HOST . "\n";
echo "   - Database Name: " . DB_NAME . "\n\n";

try {
    // Connect to database
    echo "2. Connecting to database...\n";
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "   ✅ Connected successfully\n\n";
    
    // Check settings table
    echo "3. Checking settings table...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM settings");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "   Columns: " . implode(', ', $columns) . "\n\n";
    
    // Check for maintenance_mode
    echo "4. Checking for maintenance_mode setting...\n";
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute(['maintenance_mode']);
    $value = $stmt->fetchColumn();
    
    if ($value === false) {
        echo "   ⚠️ maintenance_mode not found, creating it...\n";
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->execute(['maintenance_mode', '0']);
        echo "   ✅ Created maintenance_mode setting\n\n";
    } else {
        echo "   ✅ maintenance_mode exists with value: $value\n\n";
    }
    
    // Verify fix
    echo "5. Verifying fix...\n";
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute(['maintenance_mode']);
    $value = $stmt->fetchColumn();
    echo "   Current maintenance_mode value: " . var_export($value, true) . "\n\n";
    
    // Clear cache
    echo "6. Clearing cache...\n";
    $cacheDir = __DIR__ . '/cache';
    if (is_dir($cacheDir)) {
        $files = glob($cacheDir . '/*');
        $count = 0;
        foreach ($files as $file) {
            if (is_file($file) && basename($file) !== 'index.html') {
                unlink($file);
                $count++;
            }
        }
        echo "   ✅ Cleared $count cache files\n\n";
    }
    
    echo "🎉 SUCCESS! The site should now work properly.\n\n";
    echo "<a href='/' style='background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px;'>Go to Homepage</a>\n";
    echo "<a href='/admin/login.php' style='background: #2196F3; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px; margin-left: 10px;'>Go to Admin</a>\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "</pre>";