<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';
require_once 'includes/database.php';

echo "<pre>";
echo "Debug Index Page\n";
echo "================\n\n";

try {
    // Clear any cache
    $cacheDir = __DIR__ . '/cache';
    if (is_dir($cacheDir)) {
        $files = glob($cacheDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        echo "Cache cleared.\n\n";
    }
    
    // Test database connection
    $pdo = Database::getInstance();
    echo "Database connected successfully.\n\n";
    
    // Check settings table structure
    echo "Settings table structure:\n";
    $stmt = $pdo->query("DESCRIBE settings");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
    echo "\n";
    
    // Test the maintenance mode query
    echo "Testing maintenance mode query:\n";
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute(['maintenance_mode']);
    $value = $stmt->fetchColumn();
    
    if ($value === false) {
        echo "  No maintenance_mode setting found. Creating it...\n";
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        $stmt->execute(['maintenance_mode', '0', '0']);
        echo "  Created maintenance_mode setting.\n";
    } else {
        echo "  Maintenance mode: " . var_export($value, true) . "\n";
    }
    
    echo "\nNow the main page should work. <a href='/'>Go to homepage</a>\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString();
}

echo "</pre>";