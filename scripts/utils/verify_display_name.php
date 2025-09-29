<?php
// Verify display_name migration
try {
    $db = new PDO('mysql:host=localhost;dbname=cms_db;charset=utf8mb4', 'cms_user', 'cms_password');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Checking users table structure:\n";
    echo str_repeat("-", 50) . "\n";
    
    // Check columns
    $stmt = $db->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasDisplayName = false;
    foreach ($columns as $column) {
        if ($column['Field'] == 'display_name') {
            $hasDisplayName = true;
            echo "✓ display_name column exists\n";
            echo "  Type: " . $column['Type'] . "\n";
            echo "  Null: " . $column['Null'] . "\n";
            echo "  Key: " . $column['Key'] . "\n";
        }
    }
    
    if (!$hasDisplayName) {
        echo "✗ display_name column NOT found\n";
    }
    
    echo "\nUsers in database:\n";
    echo str_repeat("-", 50) . "\n";
    
    // Show users
    $stmt = $db->query("SELECT user_id, username, display_name, email FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) > 0) {
        foreach ($users as $user) {
            echo "ID: {$user['user_id']}\n";
            echo "  Username: {$user['username']}\n";
            echo "  Display Name: {$user['display_name']}\n";
            echo "  Email: {$user['email']}\n\n";
        }
    } else {
        echo "No users found in database.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}