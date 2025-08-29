<?php
// Simple fix for maintenance_mode setting
require_once 'includes/config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Insert maintenance_mode setting
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->execute(['maintenance_mode', '0', '0']);
    
    echo "SUCCESS: maintenance_mode setting created/updated\n";
    
    // Verify it exists
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute(['maintenance_mode']);
    $value = $stmt->fetchColumn();
    
    echo "Current maintenance_mode value: " . var_export($value, true) . "\n";
    echo "\nThe site should now work. <a href='/'>Go to homepage</a>";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}