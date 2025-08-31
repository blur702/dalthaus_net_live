<?php
declare(strict_types=1);

// Security check - only allow from localhost or specific IPs
$allowed_ips = ['127.0.0.1', '::1'];
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', $allowed_ips) && 
    !isset($_POST['admin_setup_key']) || $_POST['admin_setup_key'] !== 'setup_db_now') {
    http_response_code(403);
    die('Access denied');
}

// Set working directory and include necessary files
chdir(__DIR__);
require_once 'includes/config.php';
require_once 'includes/database.php';

header('Content-Type: text/plain');

try {
    echo "Database Setup Starting...\n";
    echo "========================\n\n";
    
    Database::setup();
    echo "✅ Database setup completed!\n\n";
    
    // Verify admin user
    $pdo = Database::getInstance();
    $stmt = $pdo->prepare("SELECT username, role FROM users WHERE role = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "✅ Admin user: {$admin['username']}\n";
        echo "✅ Password: " . DEFAULT_ADMIN_PASS . "\n";
        echo "✅ URL: /admin/login.php\n";
    } else {
        echo "❌ Admin user not created\n";
    }
    
    echo "\nSetup complete!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

// Clean up this file
unlink(__FILE__);
?>
