<?php
/**
 * Database Schema Migration and Fix Script
 * Fixes schema inconsistencies and ensures maintenance_mode setting exists
 * 
 * USAGE: Access via browser or run via command line
 * Security: Deletes itself after successful execution
 */
declare(strict_types=1);

// Security check - prevent running in production unless forced
if (!isset($_GET['force']) && isset($_SERVER['HTTP_HOST'])) {
    $host = $_SERVER['HTTP_HOST'];
    if (strpos($host, 'dalthaus.net') !== false && !isset($_GET['override'])) {
        die("Production safety lock. Add ?force=1&override=1 to run on live site.");
    }
}

// Load configuration
require_once 'includes/config.php';
require_once 'includes/database.php';

echo "<h2>Database Schema Migration - Dalthaus CMS</h2>\n";
echo "<pre>\n";

$errors = [];
$fixes = [];

try {
    echo "📊 Connecting to database...\n";
    $pdo = Database::getInstance();
    echo "✅ Database connection successful\n\n";
    
    // Step 1: Check current settings table structure
    echo "🔍 Checking settings table structure...\n";
    
    $tableExists = false;
    $hasSettingKey = false;
    $hasKey = false;
    
    try {
        $result = $pdo->query("SHOW TABLES LIKE 'settings'");
        $tableExists = $result->rowCount() > 0;
        
        if ($tableExists) {
            $result = $pdo->query("DESCRIBE settings");
            $columns = [];
            while ($row = $result->fetch()) {
                $columns[] = $row['Field'];
            }
            
            $hasSettingKey = in_array('setting_key', $columns);
            $hasKey = in_array('key', $columns);
            
            echo "   Table exists: YES\n";
            echo "   Columns found: " . implode(', ', $columns) . "\n";
            echo "   Has 'setting_key': " . ($hasSettingKey ? 'YES' : 'NO') . "\n";
            echo "   Has 'key': " . ($hasKey ? 'YES' : 'NO') . "\n";
        } else {
            echo "   Table exists: NO\n";
        }
    } catch (Exception $e) {
        echo "   Error checking table: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    
    // Step 2: Fix schema based on current state
    if (!$tableExists) {
        echo "🛠️ Creating settings table with correct schema...\n";
        $pdo->exec("CREATE TABLE settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            setting_type VARCHAR(50) DEFAULT 'text',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_key (setting_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $fixes[] = "Created settings table with correct schema";
        
    } elseif ($hasKey && !$hasSettingKey) {
        echo "🔄 Migrating settings table from 'key' to 'setting_key' column...\n";
        
        // Backup existing data
        $stmt = $pdo->query("SELECT `key`, `value` FROM settings");
        $existingData = $stmt->fetchAll();
        echo "   Backed up " . count($existingData) . " existing settings\n";
        
        // Drop the old table
        $pdo->exec("DROP TABLE settings");
        echo "   Dropped old table\n";
        
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
        echo "   Created new table with correct schema\n";
        
        // Restore data with new column names
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
        foreach ($existingData as $row) {
            $stmt->execute([$row['key'], $row['value']]);
        }
        echo "   Restored " . count($existingData) . " settings with new schema\n";
        
        $fixes[] = "Migrated settings table schema from 'key' to 'setting_key'";
        
    } elseif ($hasSettingKey) {
        echo "✅ Settings table already has correct schema\n";
    } else {
        echo "⚠️ Settings table exists but has unknown schema\n";
        $errors[] = "Settings table schema is unrecognized";
    }
    
    // Step 3: Ensure maintenance_mode setting exists
    echo "\n🔧 Checking maintenance_mode setting...\n";
    
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute(['maintenance_mode']);
    $maintenanceMode = $stmt->fetchColumn();
    
    if ($maintenanceMode === false) {
        echo "   maintenance_mode setting: NOT FOUND - creating...\n";
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, setting_type) VALUES (?, ?, ?)");
        $stmt->execute(['maintenance_mode', '0', 'boolean']);
        $fixes[] = "Created maintenance_mode setting (default: 0)";
    } else {
        echo "   maintenance_mode setting: EXISTS (value: $maintenanceMode)\n";
    }
    
    // Step 4: Add other essential settings if missing
    echo "\n🎨 Checking other essential settings...\n";
    
    $defaultSettings = [
        'site_title' => 'Dalthaus Photography',
        'site_description' => 'Professional Photography Portfolio',
        'site_author' => 'Don Althaus',
        'header_image' => '',
        'cache_enabled' => '1'
    ];
    
    foreach ($defaultSettings as $key => $defaultValue) {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $exists = $stmt->fetchColumn() !== false;
        
        if (!$exists) {
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, setting_type) VALUES (?, ?, 'text')");
            $stmt->execute([$key, $defaultValue]);
            echo "   Added missing setting: $key = '$defaultValue'\n";
            $fixes[] = "Added default setting: $key";
        } else {
            echo "   Setting exists: $key\n";
        }
    }
    
    // Step 5: Test the fix
    echo "\n🧪 Testing maintenance_mode query...\n";
    
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute(['maintenance_mode']);
    $testResult = $stmt->fetchColumn();
    
    if ($testResult !== false) {
        echo "   ✅ Query successful! maintenance_mode = '$testResult'\n";
        $fixes[] = "Verified maintenance_mode query works";
    } else {
        echo "   ❌ Query still failing!\n";
        $errors[] = "maintenance_mode query test failed";
    }
    
    // Step 6: Clear cache to ensure changes take effect
    echo "\n🧹 Clearing cache...\n";
    $cacheCleared = 0;
    if (is_dir('cache')) {
        $files = glob('cache/*');
        foreach ($files as $file) {
            if (is_file($file) && pathinfo($file, PATHINFO_EXTENSION) === 'cache') {
                unlink($file);
                $cacheCleared++;
            }
        }
    }
    echo "   Cleared $cacheCleared cache files\n";
    
    // Summary
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "MIGRATION SUMMARY\n";
    echo str_repeat("=", 60) . "\n";
    
    if (empty($errors)) {
        echo "✅ MIGRATION SUCCESSFUL\n\n";
        echo "Fixes applied:\n";
        foreach ($fixes as $fix) {
            echo "  • $fix\n";
        }
        
        echo "\n📋 Next steps:\n";
        echo "  1. Test main index.php - should work without errors\n";
        echo "  2. Verify admin panel functionality\n";
        echo "  3. Delete this migration script: " . __FILE__ . "\n";
        
        // Auto-delete this script for security
        echo "\n🗑️ Auto-deleting migration script for security...\n";
        if (unlink(__FILE__)) {
            echo "   ✅ Migration script deleted\n";
        } else {
            echo "   ⚠️ Could not delete migration script - please remove manually\n";
        }
        
    } else {
        echo "❌ MIGRATION HAD ERRORS\n\n";
        echo "Errors encountered:\n";
        foreach ($errors as $error) {
            echo "  • $error\n";
        }
        
        if (!empty($fixes)) {
            echo "\nFixes applied:\n";
            foreach ($fixes as $fix) {
                echo "  • $fix\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "\n💥 FATAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>\n";
?>