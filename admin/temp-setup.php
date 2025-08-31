<?php
/**
 * Temporary Database Setup - Admin Access
 * This file runs database initialization from within the admin folder
 */
declare(strict_types=1);

// Security check - allow only from localhost or with special parameter
if (!isset($_GET['init_db']) || $_GET['init_db'] !== 'run_now') {
    http_response_code(403);
    die('Access denied. Use ?init_db=run_now');
}

header('Content-Type: text/plain');

echo "Database Initialization Starting...\n";
echo "==================================\n\n";

try {
    // Change to root directory
    chdir('..');
    
    require_once 'includes/config.php';
    require_once 'includes/database.php';
    
    echo "Configuration loaded:\n";
    echo "- Database Host: " . DB_HOST . "\n";
    echo "- Database Name: " . DB_NAME . "\n";
    echo "- Database User: " . DB_USER . "\n";
    echo "- Default Admin User: " . DEFAULT_ADMIN_USER . "\n";
    echo "- Default Admin Pass: " . DEFAULT_ADMIN_PASS . "\n\n";
    
    echo "Running Database::setup()...\n";
    Database::setup();
    echo "✅ Database setup completed successfully!\n\n";
    
    // Verify the setup
    $pdo = Database::getInstance();
    
    // Check tables
    echo "Tables created:\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        $count_stmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
        $count = $count_stmt->fetchColumn();
        echo "  - $table ($count rows)\n";
    }
    echo "\n";
    
    // Check admin user
    echo "Admin user verification:\n";
    $stmt = $pdo->prepare("SELECT id, username, role, created_at FROM users WHERE role = 'admin'");
    $stmt->execute();
    $admin_users = $stmt->fetchAll();
    
    if (count($admin_users) > 0) {
        foreach ($admin_users as $admin) {
            echo "✅ Admin found - ID: {$admin['id']}, Username: {$admin['username']}, Created: {$admin['created_at']}\n";
            
            // Test password
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE username = ?");
            $stmt->execute([$admin['username']]);
            $hash = $stmt->fetchColumn();
            
            if (password_verify(DEFAULT_ADMIN_PASS, $hash)) {
                echo "✅ Password verified for {$admin['username']}\n";
            } else {
                echo "❌ Password verification failed for {$admin['username']}\n";
            }
        }
    } else {
        echo "❌ No admin users found\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "✅ DATABASE SETUP COMPLETE!\n";
    echo str_repeat("=", 50) . "\n\n";
    
    echo "Login credentials:\n";
    echo "- Username: " . DEFAULT_ADMIN_USER . "\n";
    echo "- Password: " . DEFAULT_ADMIN_PASS . "\n";
    echo "- URL: /admin/login.php\n\n";
    
    echo "Next steps:\n";
    echo "1. Test login at /admin/login.php\n";
    echo "2. Delete this file after successful login\n";
    
} catch (Exception $e) {
    echo "❌ Database setup failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
}

// Auto-delete this file after successful execution for security
if (!isset($_GET['keep'])) {
    unlink(__FILE__);
    echo "\n🗑️  This file has been automatically deleted for security.\n";
}
?>