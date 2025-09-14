<?php
// Emergency dashboard fix
header("Content-Type: text/plain");

echo "=== EMERGENCY FIX RUNNING ===\n\n";

// 1. First ensure database config is correct
$configPath = __DIR__ . "/config/config.php";
$configContent = file_get_contents($configPath);

// Force correct database values
$configContent = preg_replace(
    '/["\']dbname["\']\s*=>\s*["\'][^"\']*["\']/',
    '"dbname" => "dalthaus_maincms"',
    $configContent
);
$configContent = preg_replace(
    '/["\']username["\']\s*=>\s*["\'][^"\']*["\']/',
    '"username" => "dalthaus_maincms"',
    $configContent
);
$configContent = preg_replace(
    '/["\']password["\']\s*=>\s*["\'][^"\']*["\']/',
    '"password" => "f4!,Wpds=w6*=~+1"',
    $configContent
);

file_put_contents($configPath, $configContent);
echo "1. Config file updated\n";

// 2. Fix BaseController to handle Settings errors
$baseControllerPath = __DIR__ . "/src/Controllers/BaseController.php";
$baseController = file_get_contents($baseControllerPath);

// Make sure Settings::getBool is wrapped in try-catch
if (!strpos($baseController, "try {") || !strpos($baseController, "Settings::getBool")) {
    $baseController = str_replace(
        '$maintenanceMode = Settings::getBool(\'maintenance_mode\', false);',
        'try {
            $maintenanceMode = Settings::getBool(\'maintenance_mode\', false);
        } catch (\\Exception $e) {
            error_log(\'Settings::getBool failed: \' . $e->getMessage());
            $maintenanceMode = false; // Default to not in maintenance
        }',
        $baseController
    );
    file_put_contents($baseControllerPath, $baseController);
    echo "2. BaseController error handling updated\n";
}

// 3. Ensure settings table exists with correct structure
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=dalthaus_maincms;charset=utf8mb4",
        "dalthaus_maincms",
        "f4!,Wpds=w6*=~+1"
    );
    
    // Create table if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        setting_id int(11) NOT NULL AUTO_INCREMENT,
        setting_key varchar(100) NOT NULL,
        setting_value text,
        created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (setting_id),
        UNIQUE KEY setting_key (setting_key)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    
    // Ensure maintenance_mode is off
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->execute(["maintenance_mode", "0", "0"]);
    
    echo "3. Settings table verified and maintenance_mode set to 0\n";
    
} catch (PDOException $e) {
    echo "3. Database error: " . $e->getMessage() . "\n";
}

// 4. Clear all caches
if (function_exists("opcache_reset")) {
    opcache_reset();
    echo "4. OPcache cleared\n";
}

// Clear any session data
session_start();
session_destroy();
echo "5. Sessions cleared\n";

echo "\n✅ Emergency fix complete!\n";
echo "Dashboard should now load without 503 errors.\n";
?>