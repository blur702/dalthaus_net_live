<?php
// Simple database connection test
$config = require __DIR__ . '/config/config.php';

echo "<h1>Database Connection Test</h1>\n";

try {
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        $config['database']['host'],
        $config['database']['dbname'],
        $config['database']['charset']
    );
    
    $pdo = new PDO(
        $dsn,
        $config['database']['username'],
        $config['database']['password'],
        $config['database']['options']
    );
    
    echo "<p>✓ Database connection successful</p>\n";
    echo "<p>Connected to: {$config['database']['dbname']} as {$config['database']['username']}</p>\n";
    
    // Test user query
    $stmt = $pdo->prepare("SELECT user_id, username, email FROM users WHERE username = ? OR email = ?");
    $stmt->execute(['kevin', 'kevin']);
    $user = $stmt->fetch();
    
    if ($user) {
        echo "<p>✓ User 'kevin' found</p>\n";
        echo "<pre>" . print_r($user, true) . "</pre>\n";
    } else {
        echo "<p>❌ User 'kevin' not found</p>\n";
        
        // Show all users
        $stmt = $pdo->query("SELECT user_id, username, email FROM users");
        $users = $stmt->fetchAll();
        echo "<p>All users:</p>\n";
        echo "<pre>" . print_r($users, true) . "</pre>\n";
    }
    
} catch (PDOException $e) {
    echo "<p>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>\n";
} catch (Exception $e) {
    echo "<p>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}
?>