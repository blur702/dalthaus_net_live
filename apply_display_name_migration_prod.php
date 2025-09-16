<?php
/**
 * Apply display_name migration to the PRODUCTION database
 * Run this script on the production server to add the display_name field
 */

// Load the production config
$config = require __DIR__ . '/config/config.php';

try {
    echo "Applying display_name migration to PRODUCTION database...\n";
    
    // Production database connection
    $dsn = "mysql:host={$config['database']['host']};dbname={$config['database']['dbname']};charset={$config['database']['charset']}";
    $db = new PDO($dsn, $config['database']['username'], $config['database']['password']);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if display_name column already exists
    $checkSql = "SHOW COLUMNS FROM users LIKE 'display_name'";
    $result = $db->query($checkSql);
    
    if ($result->rowCount() > 0) {
        echo "Column 'display_name' already exists in users table.\n";
        exit(0);
    }
    
    // Add display_name column
    echo "Adding display_name column...\n";
    $sql1 = "ALTER TABLE `users` ADD COLUMN `display_name` varchar(100) NULL AFTER `username`";
    $db->exec($sql1);
    
    // Update existing users to have display_name = username
    echo "Setting display_name for existing users...\n";
    $sql2 = "UPDATE `users` SET `display_name` = `username` WHERE `display_name` IS NULL";
    $db->exec($sql2);
    
    // Make display_name required
    echo "Making display_name required...\n";
    $sql3 = "ALTER TABLE `users` MODIFY COLUMN `display_name` varchar(100) NOT NULL";
    $db->exec($sql3);
    
    // Add index for display_name
    echo "Adding index for display_name...\n";
    $sql4 = "ALTER TABLE `users` ADD INDEX `idx_display_name` (`display_name`)";
    $db->exec($sql4);
    
    echo "Migration applied successfully!\n";
    echo "All existing users now have display_name set to their username.\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}