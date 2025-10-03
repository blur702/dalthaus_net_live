<?php
/**
 * User Debug Script - Check user admin status and session data
 */

require_once __DIR__ . '/config/config.php';

try {
    // Connect to database
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    echo "=== User Admin Status Check ===\n\n";
    
    // Get all users
    $stmt = $pdo->query("SELECT user_id, username, email, is_admin, created_at FROM users ORDER BY username");
    $users = $stmt->fetchAll();
    
    echo "Users in database:\n";
    echo str_repeat("-", 80) . "\n";
    printf("%-10s %-15s %-25s %-10s %s\n", "ID", "Username", "Email", "is_admin", "Created");
    echo str_repeat("-", 80) . "\n";
    
    foreach ($users as $user) {
        printf("%-10s %-15s %-25s %-10s %s\n", 
            $user['user_id'], 
            $user['username'], 
            $user['email'], 
            $user['is_admin'] ? 'YES' : 'NO',
            $user['created_at']
        );
    }
    
    echo "\n=== Session Information ===\n";
    session_start();
    
    echo "Current session data:\n";
    if (isset($_SESSION['user_id'])) {
        echo "- Logged in as: " . ($_SESSION['username'] ?? 'Unknown') . "\n";
        echo "- User ID: " . $_SESSION['user_id'] . "\n";
        echo "- Is Admin (session): " . (($_SESSION['is_admin'] ?? false) ? 'YES' : 'NO') . "\n";
        echo "- Email: " . ($_SESSION['email'] ?? 'Unknown') . "\n";
        echo "- Login time: " . ($_SESSION['login_time'] ?? 'Unknown') . "\n";
        echo "- Last activity: " . ($_SESSION['last_activity'] ?? 'Unknown') . "\n";
    } else {
        echo "- Not logged in\n";
    }
    
    echo "\nAll session variables:\n";
    foreach ($_SESSION as $key => $value) {
        if (is_scalar($value)) {
            echo "- $key: $value\n";
        } else {
            echo "- $key: " . print_r($value, true) . "\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}