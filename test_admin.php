<?php
// Test admin access
session_start();
require_once __DIR__ . '/vendor/autoload.php';

// Test 1: Check if config loads
echo "1. Testing config load...\n";
$config = require __DIR__ . '/config/config.php';
echo "Config loaded: " . (is_array($config) ? 'YES' : 'NO') . "\n";
echo "Database config: " . json_encode($config['database']) . "\n\n";

// Test 2: Check database connection
echo "2. Testing database connection...\n";
try {
    $dsn = "mysql:host={$config['database']['host']};dbname={$config['database']['dbname']};charset={$config['database']['charset']}";
    $pdo = new PDO($dsn, $config['database']['username'], $config['database']['password']);
    echo "Database connected successfully!\n\n";
    
    // Test 3: Check if users table exists
    echo "3. Checking users table...\n";
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $count = $stmt->fetchColumn();
    echo "Users table exists with $count users\n\n";
    
    // Test 4: Check kevin user
    echo "4. Checking kevin user...\n";
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute(['kevin']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        echo "Kevin user found:\n";
        echo "- ID: {$user['user_id']}\n";
        echo "- Email: {$user['email']}\n";
        echo "- Is Admin: " . ($user['is_admin'] ? 'YES' : 'NO') . "\n\n";
    } else {
        echo "Kevin user NOT found!\n\n";
    }
    
    // Test 5: Check session
    echo "5. Checking session...\n";
    echo "Session ID: " . session_id() . "\n";
    echo "Session data: " . json_encode($_SESSION) . "\n\n";
    
    // Test 6: Check Router
    echo "6. Testing Router class...\n";
    if (class_exists('CMS\Router')) {
        echo "Router class exists\n";
        $router = new CMS\Router();
        echo "Router instantiated successfully\n";
    } else {
        echo "Router class NOT found!\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>