#!/bin/bash

echo "Running Database Setup via Web Request..."
echo "========================================"

# Create a simple PHP script to run database setup
cat > /tmp/db_setup.php << 'EOF'
<?php
declare(strict_types=1);

// Set working directory
chdir('/var/public_html/www');

require_once 'includes/config.php';
require_once 'includes/database.php';

echo "Database Setup Tool\n";
echo "==================\n\n";

try {
    echo "Attempting database setup...\n";
    Database::setup();
    echo "✅ Database setup completed successfully!\n\n";
    
    // Verify admin user was created
    $pdo = Database::getInstance();
    $stmt = $pdo->prepare("SELECT username, role, created_at FROM users WHERE role = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "✅ Admin user found:\n";
        echo "   Username: {$admin['username']}\n";
        echo "   Role: {$admin['role']}\n";
        echo "   Created: {$admin['created_at']}\n";
        
        // Test password
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE username = ?");
        $stmt->execute([$admin['username']]);
        $hash = $stmt->fetchColumn();
        
        if (password_verify(DEFAULT_ADMIN_PASS, $hash)) {
            echo "✅ Password verification: SUCCESS\n";
            echo "   Credentials: {$admin['username']} / " . DEFAULT_ADMIN_PASS . "\n";
        } else {
            echo "❌ Password verification: FAILED\n";
        }
    } else {
        echo "❌ No admin user found after setup\n";
    }
    
    // Show all tables
    echo "\n📋 Database tables created:\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        $count_stmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
        $count = $count_stmt->fetchColumn();
        echo "   - $table ($count rows)\n";
    }
    
} catch (Exception $e) {
    echo "❌ Database setup failed: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
    exit(1);
}
?>
EOF

# Execute the setup via web request using POST to a temporary endpoint
cat > /var/public_html/www/temp_db_setup.php << 'EOF'
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
EOF

echo "1. Running database setup..."
curl -s -X POST -d "admin_setup_key=setup_db_now" https://dalthaus.net/temp_db_setup.php

echo -e "\n2. Testing login after setup..."
# Test login with the credentials from config
csrf_token=$(curl -s -c /tmp/setup_cookies.txt https://dalthaus.net/admin/login.php | grep 'csrf_token' | sed -n 's/.*value="\([^"]*\)".*/\1/p')

if [ -n "$csrf_token" ]; then
    login_result=$(curl -s -w "HTTP_CODE:%{http_code}\n" \
        -b /tmp/setup_cookies.txt \
        -c /tmp/setup_cookies.txt \
        -X POST \
        -d "username=kevin&password=(130Bpm)&csrf_token=${csrf_token}" \
        https://dalthaus.net/admin/login.php)
    
    http_code=$(echo "$login_result" | grep "HTTP_CODE" | cut -d':' -f2)
    echo "Login test HTTP code: $http_code"
    
    if [ "$http_code" = "302" ] || [ "$http_code" = "301" ]; then
        echo "✅ Login test: SUCCESS"
    elif [ "$http_code" = "500" ]; then
        echo "❌ Login test: Still failing with 500"
    else
        echo "⚠️ Login test: HTTP $http_code"
    fi
else
    echo "❌ Could not get CSRF token for login test"
fi

# Cleanup
rm -f /tmp/setup_cookies.txt /tmp/db_setup.php

echo "Database setup complete."