<?php
/**
 * Fix Admin User - Ensure admin user exists with correct credentials
 */
declare(strict_types=1);

// Prevent running from browser for security
if (php_sapi_name() !== 'cli' && !isset($_GET['allow'])) {
    die('This script must be run from command line or with ?allow parameter');
}

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';

echo "Admin User Fix Tool\n";
echo "==================\n\n";

try {
    $pdo = Database::getInstance();
    echo "✅ Database connection: SUCCESS\n";
    
    // Check if users table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() == 0) {
        echo "❌ Users table does not exist!\n";
        echo "   Run setup.php first to create database tables.\n";
        exit(1);
    }
    echo "✅ Users table: EXISTS\n";
    
    // Check for existing admin user
    $stmt = $pdo->prepare("SELECT id, username, role, created_at FROM users WHERE username = 'admin'");
    $stmt->execute();
    $existing_admin = $stmt->fetch();
    
    if ($existing_admin) {
        echo "⚠️  Admin user already exists:\n";
        echo "   ID: {$existing_admin['id']}\n";
        echo "   Username: {$existing_admin['username']}\n";
        echo "   Role: {$existing_admin['role']}\n";
        echo "   Created: {$existing_admin['created_at']}\n";
        
        // Test current password
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE username = 'admin'");
        $stmt->execute();
        $current_hash = $stmt->fetchColumn();
        
        if (password_verify('130Bpm', $current_hash)) {
            echo "✅ Current password (130Bpm): VALID\n";
            echo "   Admin user is properly configured.\n";
            exit(0);
        } else {
            echo "❌ Current password (130Bpm): INVALID\n";
            echo "   Updating password...\n";
            
            $new_hash = password_hash('130Bpm', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE username = 'admin'");
            if ($stmt->execute([$new_hash])) {
                echo "✅ Password updated successfully!\n";
                
                // Verify the update
                $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE username = 'admin'");
                $stmt->execute();
                $updated_hash = $stmt->fetchColumn();
                
                if (password_verify('130Bpm', $updated_hash)) {
                    echo "✅ Password verification: SUCCESS\n";
                } else {
                    echo "❌ Password verification: FAILED after update\n";
                    exit(1);
                }
            } else {
                echo "❌ Failed to update password\n";
                exit(1);
            }
        }
        
    } else {
        echo "❌ Admin user does not exist. Creating...\n";
        
        $password_hash = password_hash('130Bpm', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (username, password_hash, email, role, created_at) 
            VALUES ('admin', ?, 'admin@dalthaus.net', 'admin', NOW())
        ");
        
        if ($stmt->execute([$password_hash])) {
            echo "✅ Admin user created successfully!\n";
            
            // Get the new user ID
            $admin_id = $pdo->lastInsertId();
            echo "   New admin ID: $admin_id\n";
            
            // Verify password works
            $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE username = 'admin'");
            $stmt->execute();
            $new_hash = $stmt->fetchColumn();
            
            if (password_verify('130Bpm', $new_hash)) {
                echo "✅ Password verification: SUCCESS\n";
            } else {
                echo "❌ Password verification: FAILED\n";
                exit(1);
            }
            
        } else {
            echo "❌ Failed to create admin user\n";
            exit(1);
        }
    }
    
    // Show all users for debugging
    echo "\n📊 All users in database:\n";
    $stmt = $pdo->query("SELECT id, username, email, role, created_at FROM users ORDER BY id");
    $users = $stmt->fetchAll();
    
    if (count($users) == 0) {
        echo "   No users found.\n";
    } else {
        foreach ($users as $user) {
            echo "   #{$user['id']}: {$user['username']} ({$user['role']}) - {$user['email']} - {$user['created_at']}\n";
        }
    }
    
    echo "\n✅ Admin user fix complete!\n";
    echo "   Username: admin\n";
    echo "   Password: 130Bpm\n";
    echo "   Login URL: /admin/login.php\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
    exit(1);
}
?>