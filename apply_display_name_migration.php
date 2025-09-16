<?php
/**
 * Apply display_name migration to the database
 * Run this script to add the display_name field to the users table
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/config.php';

use CMS\Core\Database;

try {
    echo "Applying display_name migration...\n";
    
    $db = Database::getInstance();
    
    // Read and execute the migration SQL
    $migrationSql = file_get_contents(__DIR__ . '/migrations/add_display_name_to_users.sql');
    
    // Split the SQL into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $migrationSql)),
        function($stmt) {
            return !empty($stmt) && !str_starts_with($stmt, '--');
        }
    );
    
    foreach ($statements as $sql) {
        if (!empty($sql)) {
            echo "Executing: " . substr($sql, 0, 50) . "...\n";
            $db->exec($sql);
        }
    }
    
    echo "Migration applied successfully!\n";
    echo "All existing users now have display_name set to their username.\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}