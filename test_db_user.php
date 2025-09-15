<?php
/**
 * Database user verification tool
 */

// Load config and autoloader
require_once __DIR__ . '/vendor/autoload.php';
$config = require __DIR__ . '/config/config.php';

echo "<h1>Database User Test</h1>";

try {
    // Initialize database
    $db = CMS\Utils\Database::getInstance($config['database']);
    echo "<p style='color: green;'>✓ Database connection successful</p>";
    
    // Check if users table exists
    $tables = $db->fetchAll("SHOW TABLES LIKE 'users'");
    if (empty($tables)) {
        echo "<p style='color: red;'>✗ 'users' table does not exist</p>";
        exit;
    }
    echo "<p style='color: green;'>✓ 'users' table exists</p>";
    
    // Get all users
    echo "<h2>All Users in Database</h2>";
    $users = $db->fetchAll("SELECT user_id, username, email, created_at FROM users");
    if (empty($users)) {
        echo "<p style='color: red;'>✗ No users found in database</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Created</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($user['user_id']) . "</td>";
            echo "<td>" . htmlspecialchars($user['username']) . "</td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . htmlspecialchars($user['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test specific user lookup
    echo "<h2>Test User 'kevin' Lookup</h2>";
    $user = $db->fetchRow(
        'SELECT user_id, username, email, password_hash FROM users WHERE username = ? OR email = ?',
        ['kevin', 'kevin']
    );
    
    if ($user) {
        echo "<p style='color: green;'>✓ User 'kevin' found</p>";
        echo "<pre>";
        echo "User ID: " . $user['user_id'] . "\n";
        echo "Username: " . $user['username'] . "\n";
        echo "Email: " . $user['email'] . "\n";
        echo "Password Hash: " . substr($user['password_hash'], 0, 20) . "...\n";
        echo "Hash length: " . strlen($user['password_hash']) . " chars\n";
        echo "</pre>";
        
        // Test password verification
        echo "<h2>Password Verification Test</h2>";
        $testPassword = "(130Bpm)";
        if (password_verify($testPassword, $user['password_hash'])) {
            echo "<p style='color: green;'>✓ Password '$testPassword' is correct</p>";
        } else {
            echo "<p style='color: red;'>✗ Password '$testPassword' is incorrect</p>";
            
            // Test common variations
            $variations = [
                "130Bpm",
                "(130bpm)",
                "130bpm",
                "(130BPM)"
            ];
            
            echo "<p>Testing password variations:</p>";
            echo "<ul>";
            foreach ($variations as $variation) {
                if (password_verify($variation, $user['password_hash'])) {
                    echo "<li style='color: green;'>✓ '$variation' - CORRECT</li>";
                } else {
                    echo "<li style='color: red;'>✗ '$variation' - incorrect</li>";
                }
            }
            echo "</ul>";
        }
        
    } else {
        echo "<p style='color: red;'>✗ User 'kevin' not found</p>";
        
        // Show what usernames do exist
        $usernames = $db->fetchAll("SELECT username FROM users");
        if (!empty($usernames)) {
            echo "<p>Existing usernames:</p>";
            echo "<ul>";
            foreach ($usernames as $u) {
                echo "<li>" . htmlspecialchars($u['username']) . "</li>";
            }
            echo "</ul>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Database User Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
        h2 { color: #333; border-bottom: 2px solid #ccc; padding-bottom: 10px; }
        table { margin: 10px 0; }
        th { background: #f0f0f0; padding: 8px; }
        td { padding: 8px; }
    </style>
</head>
<body>
    <p><a href="/admin/login">Back to Login</a> | <a href="test_db_user.php">Refresh</a></p>
</body>
</html>