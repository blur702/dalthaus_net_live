<?php
/**
 * Quick Fix Script for Dalthaus CMS
 * Applies all critical fixes in one go for immediate deployment
 * 
 * This script:
 * 1. Fixes database schema issues
 * 2. Creates missing settings
 * 3. Ensures maintenance_mode works
 * 4. Tests core functionality
 * 5. Self-destructs for security
 */
declare(strict_types=1);

// Security check
$allowedHosts = ['dalthaus.net', 'www.dalthaus.net', 'localhost', '127.0.0.1'];
$currentHost = $_SERVER['HTTP_HOST'] ?? 'unknown';

if (!in_array($currentHost, $allowedHosts) && !isset($_GET['force'])) {
    die("Security: Only run on authorized hosts. Add ?force=1 to override.");
}

echo "<h2>🚀 Dalthaus CMS - Quick Fix Deployment</h2>\n";
echo "<p>Applying all critical fixes for immediate deployment...</p>\n";
echo "<pre>\n";

$startTime = microtime(true);
$fixes = [];
$errors = [];

try {
    echo "🔧 QUICK FIX DEPLOYMENT STARTED\n";
    echo "Host: $currentHost\n";
    echo "Time: " . date('Y-m-d H:i:s') . "\n";
    echo str_repeat("-", 50) . "\n\n";
    
    // Step 1: Load configuration and database
    echo "📚 Loading configuration...\n";
    require_once 'includes/config.php';
    require_once 'includes/database.php';
    require_once 'includes/functions.php';
    
    $pdo = Database::getInstance();
    echo "   ✅ Database connection established\n";
    
    // Step 2: Fix settings table schema
    echo "\n🔧 Fixing settings table schema...\n";
    
    // Check current table structure
    try {
        $stmt = $pdo->query("DESCRIBE settings");
        $columns = [];
        while ($row = $stmt->fetch()) {
            $columns[] = $row['Field'];
        }
        
        $hasSettingKey = in_array('setting_key', $columns);
        $hasKey = in_array('key', $columns);
        
        if (!$hasSettingKey && $hasKey) {
            echo "   🔄 Migrating from 'key' to 'setting_key' column...\n";
            
            // Backup existing data
            $stmt = $pdo->query("SELECT `key`, `value` FROM settings");
            $existingData = $stmt->fetchAll();
            
            // Drop and recreate table
            $pdo->exec("DROP TABLE IF EXISTS settings_backup");
            $pdo->exec("CREATE TABLE settings_backup AS SELECT * FROM settings");
            $pdo->exec("DROP TABLE settings");
            
            // Create new table with correct schema
            $pdo->exec("CREATE TABLE settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(100) UNIQUE NOT NULL,
                setting_value TEXT,
                setting_type VARCHAR(50) DEFAULT 'text',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_key (setting_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            // Restore data with new column names
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
            foreach ($existingData as $row) {
                $stmt->execute([$row['key'], $row['value']]);
            }
            
            echo "   ✅ Schema migrated successfully\n";
            $fixes[] = "Migrated settings table schema";
            
        } elseif ($hasSettingKey) {
            echo "   ✅ Settings table already has correct schema\n";
        } else {
            // Create table if it doesn't exist
            echo "   🆕 Creating settings table...\n";
            $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
                id INT AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(100) UNIQUE NOT NULL,
                setting_value TEXT,
                setting_type VARCHAR(50) DEFAULT 'text',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_key (setting_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            echo "   ✅ Settings table created\n";
            $fixes[] = "Created settings table";
        }
        
    } catch (Exception $e) {
        echo "   ❌ Error fixing settings table: " . $e->getMessage() . "\n";
        $errors[] = "Settings table fix failed: " . $e->getMessage();
    }
    
    // Step 3: Ensure maintenance_mode setting exists
    echo "\n⚙️ Ensuring maintenance_mode setting exists...\n";
    
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute(['maintenance_mode']);
        $maintenanceMode = $stmt->fetchColumn();
        
        if ($maintenanceMode === false) {
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, setting_type) VALUES (?, ?, ?)");
            $stmt->execute(['maintenance_mode', '0', 'boolean']);
            echo "   ✅ maintenance_mode setting created (default: 0)\n";
            $fixes[] = "Created maintenance_mode setting";
        } else {
            echo "   ✅ maintenance_mode setting exists (value: $maintenanceMode)\n";
        }
        
    } catch (Exception $e) {
        echo "   ❌ Error with maintenance_mode setting: " . $e->getMessage() . "\n";
        $errors[] = "maintenance_mode setting error: " . $e->getMessage();
    }
    
    // Step 4: Add other essential settings
    echo "\n🎨 Adding essential settings...\n";
    
    $defaultSettings = [
        'site_title' => ['Dalthaus Photography', 'text'],
        'site_description' => ['Professional Photography Portfolio', 'text'],
        'site_author' => ['Don Althaus', 'text'],
        'cache_enabled' => ['1', 'boolean'],
        'header_image' => ['', 'text']
    ];
    
    foreach ($defaultSettings as $key => $data) {
        try {
            $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
            $stmt->execute([$key]);
            $exists = $stmt->fetchColumn() !== false;
            
            if (!$exists) {
                $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, setting_type) VALUES (?, ?, ?)");
                $stmt->execute([$key, $data[0], $data[1]]);
                echo "   ✅ Added setting: $key = '{$data[0]}'\n";
            }
        } catch (Exception $e) {
            echo "   ⚠️ Warning: Could not add setting $key: " . $e->getMessage() . "\n";
        }
    }
    
    // Step 5: Test the fix
    echo "\n🧪 Testing maintenance_mode query...\n";
    
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute(['maintenance_mode']);
        $testResult = $stmt->fetchColumn();
        
        if ($testResult !== false) {
            echo "   ✅ Query test PASSED! Result: '$testResult'\n";
            $fixes[] = "Maintenance mode query test successful";
        } else {
            echo "   ❌ Query test FAILED!\n";
            $errors[] = "Maintenance mode query test failed";
        }
        
    } catch (Exception $e) {
        echo "   ❌ Query test ERROR: " . $e->getMessage() . "\n";
        $errors[] = "Query test error: " . $e->getMessage();
    }
    
    // Step 6: Clear cache
    echo "\n🧹 Clearing cache...\n";
    
    $cacheCleared = 0;
    if (is_dir('cache')) {
        $files = glob('cache/*.cache');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
                $cacheCleared++;
            }
        }
    }
    echo "   ✅ Cleared $cacheCleared cache files\n";
    
    // Step 7: Verify core files exist
    echo "\n📁 Verifying core files...\n";
    
    $coreFiles = [
        'index.php' => 'Main entry point',
        'includes/config.php' => 'Configuration',
        'includes/database.php' => 'Database connection',
        'includes/functions.php' => 'Core functions',
        'admin/login.php' => 'Admin login'
    ];
    
    foreach ($coreFiles as $file => $description) {
        if (file_exists($file)) {
            echo "   ✅ $file ($description)\n";
        } else {
            echo "   ❌ Missing: $file ($description)\n";
            $errors[] = "Missing core file: $file";
        }
    }
    
    // Final summary
    $endTime = microtime(true);
    $duration = round($endTime - $startTime, 2);
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "QUICK FIX DEPLOYMENT COMPLETE\n";
    echo str_repeat("=", 50) . "\n";
    
    echo "\n⏱️ Duration: {$duration} seconds\n";
    echo "✅ Fixes Applied: " . count($fixes) . "\n";
    echo "❌ Errors: " . count($errors) . "\n";
    
    if (!empty($fixes)) {
        echo "\n🔧 Applied Fixes:\n";
        foreach ($fixes as $fix) {
            echo "   • $fix\n";
        }
    }
    
    if (!empty($errors)) {
        echo "\n⚠️ Issues Encountered:\n";
        foreach ($errors as $error) {
            echo "   • $error\n";
        }
    }
    
    if (empty($errors)) {
        echo "\n🎉 SUCCESS: The CMS should now work correctly!\n";
        echo "\n📋 Next Steps:\n";
        echo "   1. Visit the homepage: /\n";
        echo "   2. Test admin login: /admin/login.php (kevin / (130Bpm))\n";
        echo "   3. Check maintenance mode toggle in admin settings\n";
        echo "   4. Change admin password after first login\n";
        echo "\n🛡️ Security:\n";
        echo "   • This script will self-destruct for security\n";
        echo "   • Remember to delete debug files in production\n";
        echo "   • Set ENV='production' in config.php when ready\n";
        
    } else {
        echo "\n⚠️ PARTIAL SUCCESS: Some issues remain\n";
        echo "The main functionality should work, but please address the errors above.\n";
    }
    
    echo "\n🗑️ Self-destructing for security...\n";
    sleep(1); // Give user time to read
    
    if (unlink(__FILE__)) {
        echo "   ✅ Quick fix script deleted\n";
    } else {
        echo "   ⚠️ Could not delete script - please remove manually\n";
    }
    
} catch (Exception $e) {
    echo "\n💥 FATAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    $errors[] = "Fatal error: " . $e->getMessage();
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "END OF QUICK FIX DEPLOYMENT\n";
echo str_repeat("=", 50) . "\n";

echo "</pre>\n";
?>