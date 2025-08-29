<?php
/**
 * Set Maintenance Mode
 * Access: https://dalthaus.net/set-maintenance.php?mode=1&token=maint-20250829
 */

$token = $_GET['token'] ?? '';
$mode = $_GET['mode'] ?? '';

if ($token !== 'maint-' . date('Ymd')) {
    die('Invalid token. Use: maint-' . date('Ymd'));
}

require_once 'includes/config.php';
require_once 'includes/database.php';

try {
    $pdo = Database::getInstance();
    
    $newMode = ($mode === '1') ? '1' : '0';
    $modeText = ($newMode === '1') ? 'ENABLED' : 'DISABLED';
    
    // Update maintenance mode setting
    $stmt = $pdo->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'maintenance_mode'");
    $stmt->execute([$newMode]);
    
    // If no rows affected, insert it
    if ($stmt->rowCount() === 0) {
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('maintenance_mode', ?)");
        $stmt->execute([$newMode]);
    }
    
    // Clear cache
    $cacheDir = __DIR__ . '/cache';
    if (is_dir($cacheDir)) {
        $files = glob($cacheDir . '/*');
        foreach ($files as $file) {
            if (is_file($file) && basename($file) !== 'index.html') {
                unlink($file);
            }
        }
    }
    
    echo "<!DOCTYPE html>
<html>
<head>
    <title>Maintenance Mode Control</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .status { padding: 20px; border-radius: 5px; text-align: center; font-size: 24px; font-weight: bold; margin: 20px 0; }
        .enabled { background: #e74c3c; color: white; }
        .disabled { background: #27ae60; color: white; }
        .button { display: inline-block; padding: 10px 20px; margin: 10px; background: #3498db; color: white; text-decoration: none; border-radius: 5px; }
        .button:hover { background: #2980b9; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>Maintenance Mode Control</h1>
        <div class='status " . ($newMode === '1' ? 'enabled' : 'disabled') . "'>
            Maintenance Mode: $modeText
        </div>
        
        <p>Cache has been cleared.</p>
        
        <div style='text-align: center; margin: 30px 0;'>";
    
    if ($newMode === '1') {
        echo "<p>The site is now in maintenance mode. Visitors will see a maintenance page.</p>
              <a href='?mode=0&token=$token' class='button'>Disable Maintenance Mode</a>";
    } else {
        echo "<p>The site is now live and accessible to all visitors.</p>
              <a href='?mode=1&token=$token' class='button'>Enable Maintenance Mode</a>";
    }
    
    echo "
            <a href='/' class='button'>View Site</a>
            <a href='/admin/login.php' class='button'>Admin Panel</a>
        </div>
    </div>
</body>
</html>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}