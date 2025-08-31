<?php
/**
 * Production Database Setup and Admin Creation Script
 * 
 * This script performs a complete database setup for the Dalthaus Photography CMS:
 * - Creates database and all required tables
 * - Sets up admin user with proper authentication
 * - Configures session handling
 * - Tests authentication flow
 * 
 * SECURITY: This script should be deleted after successful setup
 */

// Start output buffering for clean HTML output
ob_start();

// Load configuration
require_once __DIR__ . '/includes/config.php';

// Set error reporting for setup process
ini_set('display_errors', 1);
error_reporting(E_ALL);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dalthaus CMS - Production Setup</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background: #f8f9fa;
            color: #333;
        }
        .header {
            background: #2c3e50;
            color: white;
            padding: 20px;
            border-radius: 8px 8px 0 0;
            text-align: center;
        }
        .content {
            background: white;
            padding: 30px;
            border-radius: 0 0 8px 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .step {
            margin: 20px 0;
            padding: 15px;
            border-left: 4px solid #3498db;
            background: #f0f8ff;
        }
        .success {
            border-left-color: #27ae60;
            background: #f0fff0;
        }
        .error {
            border-left-color: #e74c3c;
            background: #fff0f0;
            color: #c0392b;
        }
        .warning {
            border-left-color: #f39c12;
            background: #fffacd;
            color: #d68910;
        }
        .info {
            margin: 15px 0;
            padding: 10px;
            background: #e8f4f8;
            border-radius: 4px;
        }
        .credentials {
            background: #2c3e50;
            color: white;
            padding: 15px;
            border-radius: 4px;
            font-family: monospace;
            margin: 15px 0;
        }
        code {
            background: #f4f4f4;
            padding: 2px 4px;
            border-radius: 3px;
            font-family: monospace;
        }
        .section {
            margin: 30px 0;
        }
        .section h3 {
            color: #2c3e50;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        .test-result {
            margin: 10px 0;
            padding: 10px;
            border-radius: 4px;
        }
        .pass {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .fail {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🔧 Dalthaus CMS Production Setup</h1>
        <p>Initializing database and admin user for production environment</p>
    </div>
    <div class="content">

<?php

$setupResults = [];
$errors = [];
$allTestsPassed = true;

// Function to log results
function logResult($step, $success, $message, $details = '') {
    global $setupResults, $allTestsPassed;
    $setupResults[] = [
        'step' => $step,
        'success' => $success,
        'message' => $message,
        'details' => $details
    ];
    if (!$success) {
        $allTestsPassed = false;
    }
}

// Function to display step results
function displayStep($step, $success, $message, $details = '') {
    $class = $success ? 'step success' : 'step error';
    $icon = $success ? '✅' : '❌';
    
    echo "<div class='$class'>";
    echo "<strong>$icon Step: $step</strong><br>";
    echo $message;
    if ($details) {
        echo "<br><small>$details</small>";
    }
    echo "</div>";
}

try {
    // Step 1: Test database connection
    echo "<div class='section'><h3>🔌 Database Connection Test</h3>";
    
    try {
        $dsn = sprintf('mysql:host=%s;charset=utf8mb4', DB_HOST);
        $pdo = new PDO($dsn, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        
        displayStep("Database Connection", true, "Successfully connected to MySQL server", "Host: " . DB_HOST . " | User: " . DB_USER);
        logResult("db_connection", true, "Database connection successful");
        
    } catch (PDOException $e) {
        displayStep("Database Connection", false, "Failed to connect to database", $e->getMessage());
        logResult("db_connection", false, "Database connection failed: " . $e->getMessage());
        throw $e;
    }
    
    // Step 2: Create database
    echo "</div><div class='section'><h3>🗄️ Database Creation</h3>";
    
    try {
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "`");
        $pdo->exec("USE `" . DB_NAME . "`");
        
        displayStep("Database Creation", true, "Database '" . DB_NAME . "' created and selected", "Ready for table creation");
        logResult("db_creation", true, "Database created successfully");
        
    } catch (PDOException $e) {
        displayStep("Database Creation", false, "Failed to create database", $e->getMessage());
        logResult("db_creation", false, "Database creation failed: " . $e->getMessage());
        throw $e;
    }
    
    // Step 3: Create tables
    echo "</div><div class='section'><h3>📋 Table Creation</h3>";
    
    // Users table
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            email VARCHAR(100),
            role ENUM('admin', 'editor') DEFAULT 'editor',
            last_login TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_username (username)
        )");
        
        displayStep("Users Table", true, "Users table created with authentication fields", "Supports admin/editor roles with secure password hashing");
        logResult("users_table", true, "Users table created");
        
    } catch (PDOException $e) {
        displayStep("Users Table", false, "Failed to create users table", $e->getMessage());
        logResult("users_table", false, "Users table creation failed: " . $e->getMessage());
        throw $e;
    }
    
    // Sessions table
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS sessions (
            id VARCHAR(128) PRIMARY KEY,
            user_id INT,
            data TEXT,
            last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ip_address VARCHAR(45),
            user_agent VARCHAR(255),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_last_activity (last_activity)
        )");
        
        displayStep("Sessions Table", true, "Sessions table created for secure authentication", "Includes IP and user agent fingerprinting");
        logResult("sessions_table", true, "Sessions table created");
        
    } catch (PDOException $e) {
        displayStep("Sessions Table", false, "Failed to create sessions table", $e->getMessage());
        logResult("sessions_table", false, "Sessions table creation failed: " . $e->getMessage());
        throw $e;
    }
    
    // Content table
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS content (
            id INT PRIMARY KEY AUTO_INCREMENT,
            type ENUM('article', 'photobook', 'page') NOT NULL,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) UNIQUE NOT NULL,
            author VARCHAR(100) DEFAULT 'Don Althaus',
            body LONGTEXT,
            status ENUM('draft', 'published') DEFAULT 'draft',
            sort_order INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at TIMESTAMP NULL,
            published_at TIMESTAMP NULL,
            page_breaks JSON DEFAULT NULL,
            page_count INT DEFAULT 1,
            INDEX idx_slug (slug),
            INDEX idx_type_status (type, status),
            INDEX idx_sort (sort_order)
        )");
        
        displayStep("Content Table", true, "Content table created for articles/photobooks/pages", "Supports versioning, soft deletes, and page tracking");
        logResult("content_table", true, "Content table created");
        
    } catch (PDOException $e) {
        displayStep("Content Table", false, "Failed to create content table", $e->getMessage());
        logResult("content_table", false, "Content table creation failed: " . $e->getMessage());
        throw $e;
    }
    
    // Content versions table
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS content_versions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            content_id INT NOT NULL,
            version_number INT NOT NULL,
            title VARCHAR(255),
            body LONGTEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            is_autosave BOOLEAN DEFAULT FALSE,
            FOREIGN KEY (content_id) REFERENCES content(id) ON DELETE CASCADE,
            INDEX idx_content_version (content_id, version_number)
        )");
        
        displayStep("Content Versions Table", true, "Content versions table created", "Supports autosave and manual versioning");
        logResult("content_versions_table", true, "Content versions table created");
        
    } catch (PDOException $e) {
        displayStep("Content Versions Table", false, "Failed to create content versions table", $e->getMessage());
        logResult("content_versions_table", false, "Content versions table creation failed: " . $e->getMessage());
        throw $e;
    }
    
    // Menus table
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS menus (
            id INT PRIMARY KEY AUTO_INCREMENT,
            location ENUM('top', 'bottom') NOT NULL,
            content_id INT,
            sort_order INT DEFAULT 0,
            is_active BOOLEAN DEFAULT TRUE,
            FOREIGN KEY (content_id) REFERENCES content(id) ON DELETE CASCADE,
            INDEX idx_location_order (location, sort_order)
        )");
        
        displayStep("Menus Table", true, "Menus table created for navigation", "Supports top/bottom menu locations with drag-drop ordering");
        logResult("menus_table", true, "Menus table created");
        
    } catch (PDOException $e) {
        displayStep("Menus Table", false, "Failed to create menus table", $e->getMessage());
        logResult("menus_table", false, "Menus table creation failed: " . $e->getMessage());
        throw $e;
    }
    
    // Settings and attachments tables
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value TEXT,
            setting_type VARCHAR(50) DEFAULT 'text',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_key (setting_key)
        )");
        
        $pdo->exec("CREATE TABLE IF NOT EXISTS attachments (
            id INT PRIMARY KEY AUTO_INCREMENT,
            content_id INT,
            filename VARCHAR(255) NOT NULL,
            original_name VARCHAR(255),
            mime_type VARCHAR(100),
            size INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (content_id) REFERENCES content(id) ON DELETE CASCADE
        )");
        
        displayStep("Support Tables", true, "Settings and attachments tables created", "Settings for configuration, attachments for file uploads");
        logResult("support_tables", true, "Support tables created");
        
    } catch (PDOException $e) {
        displayStep("Support Tables", false, "Failed to create support tables", $e->getMessage());
        logResult("support_tables", false, "Support tables creation failed: " . $e->getMessage());
        throw $e;
    }
    
    // Step 4: Create admin user
    echo "</div><div class='section'><h3>👤 Admin User Creation</h3>";
    
    try {
        // Check if admin user already exists
        $stmt = $pdo->prepare("SELECT id, username FROM users WHERE username = ?");
        $stmt->execute([DEFAULT_ADMIN_USER]);
        $existingUser = $stmt->fetch();
        
        if ($existingUser) {
            // Update existing user password
            $hash = password_hash(DEFAULT_ADMIN_PASS, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, role = 'admin' WHERE username = ?");
            $stmt->execute([$hash, DEFAULT_ADMIN_USER]);
            
            displayStep("Admin User Update", true, "Existing admin user updated with new credentials", "Username: " . DEFAULT_ADMIN_USER);
            logResult("admin_user", true, "Admin user updated");
            
        } else {
            // Create new admin user
            $hash = password_hash(DEFAULT_ADMIN_PASS, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role) VALUES (?, ?, 'admin')");
            $stmt->execute([DEFAULT_ADMIN_USER, $hash]);
            
            displayStep("Admin User Creation", true, "New admin user created successfully", "Username: " . DEFAULT_ADMIN_USER);
            logResult("admin_user", true, "Admin user created");
        }
        
        echo "<div class='credentials'>";
        echo "<strong>🔐 Admin Login Credentials:</strong><br>";
        echo "URL: <strong>https://dalthaus.net/admin/login.php</strong><br>";
        echo "Username: <strong>" . DEFAULT_ADMIN_USER . "</strong><br>";
        echo "Password: <strong>" . DEFAULT_ADMIN_PASS . "</strong>";
        echo "</div>";
        
    } catch (PDOException $e) {
        displayStep("Admin User Creation", false, "Failed to create admin user", $e->getMessage());
        logResult("admin_user", false, "Admin user creation failed: " . $e->getMessage());
        throw $e;
    }
    
    // Step 5: Create default settings
    echo "</div><div class='section'><h3>⚙️ Default Settings</h3>";
    
    try {
        $defaultSettings = [
            ['maintenance_mode', '0', 'boolean'],
            ['site_title', 'Dalthaus Photography', 'text'],
            ['site_description', 'Professional Photography Portfolio', 'text'],
            ['cache_enabled', '1', 'boolean'],
            ['session_lifetime', '3600', 'integer']
        ];
        
        foreach ($defaultSettings as $setting) {
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, setting_type) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            $stmt->execute($setting);
        }
        
        displayStep("Default Settings", true, "System settings configured", "Site title, cache settings, session configuration");
        logResult("default_settings", true, "Default settings created");
        
    } catch (PDOException $e) {
        displayStep("Default Settings", false, "Failed to create default settings", $e->getMessage());
        logResult("default_settings", false, "Default settings creation failed: " . $e->getMessage());
        throw $e;
    }
    
    // Step 6: Test authentication flow
    echo "</div><div class='section'><h3>🔐 Authentication Testing</h3>";
    
    try {
        // Test password verification
        $stmt = $pdo->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ?");
        $stmt->execute([DEFAULT_ADMIN_USER]);
        $user = $stmt->fetch();
        
        if ($user && password_verify(DEFAULT_ADMIN_PASS, $user['password_hash'])) {
            displayStep("Password Verification", true, "Admin password verification successful", "Password hash correctly generated and verified");
            logResult("password_test", true, "Password verification test passed");
            
            // Test role assignment
            if ($user['role'] === 'admin') {
                displayStep("Role Assignment", true, "Admin role correctly assigned", "User has admin privileges");
                logResult("role_test", true, "Role assignment test passed");
            } else {
                displayStep("Role Assignment", false, "Incorrect role assigned", "Expected 'admin', got '" . $user['role'] . "'");
                logResult("role_test", false, "Role assignment test failed");
            }
            
        } else {
            displayStep("Password Verification", false, "Password verification failed", "Hash mismatch or user not found");
            logResult("password_test", false, "Password verification test failed");
        }
        
    } catch (PDOException $e) {
        displayStep("Authentication Testing", false, "Authentication test failed", $e->getMessage());
        logResult("auth_test", false, "Authentication test failed: " . $e->getMessage());
        throw $e;
    }
    
    // Step 7: Test session functionality
    echo "</div><div class='section'><h3>🍪 Session Testing</h3>";
    
    try {
        // Start session and test basic functionality
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Test session storage
        $_SESSION['test_key'] = 'test_value';
        
        if (isset($_SESSION['test_key']) && $_SESSION['test_key'] === 'test_value') {
            displayStep("Session Storage", true, "Session storage working correctly", "PHP session successfully started and data stored");
            logResult("session_test", true, "Session storage test passed");
            
            // Clean up test data
            unset($_SESSION['test_key']);
            
        } else {
            displayStep("Session Storage", false, "Session storage not working", "Unable to store or retrieve session data");
            logResult("session_test", false, "Session storage test failed");
        }
        
    } catch (Exception $e) {
        displayStep("Session Testing", false, "Session test failed", $e->getMessage());
        logResult("session_test", false, "Session test failed: " . $e->getMessage());
    }
    
} catch (Exception $e) {
    echo "</div><div class='section'>";
    displayStep("Critical Error", false, "Setup failed with critical error", $e->getMessage());
    $allTestsPassed = false;
}

// Final summary
echo "</div><div class='section'><h3>📊 Setup Summary</h3>";

if ($allTestsPassed) {
    echo "<div class='step success'>";
    echo "<strong>✅ Setup Completed Successfully!</strong><br>";
    echo "All database tables created, admin user configured, and authentication tested.<br>";
    echo "Your Dalthaus Photography CMS is ready for production use.";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<strong>🚀 Next Steps:</strong><br>";
    echo "1. Test admin login at <strong>https://dalthaus.net/admin/login.php</strong><br>";
    echo "2. Change admin password after first login<br>";
    echo "3. Delete this setup file: <code>SETUP_ADMIN.php</code><br>";
    echo "4. Start adding content through the admin panel";
    echo "</div>";
    
} else {
    echo "<div class='step error'>";
    echo "<strong>❌ Setup Encountered Errors</strong><br>";
    echo "Some components failed to initialize properly. Check the error details above and resolve any issues before proceeding.";
    echo "</div>";
    
    echo "<div class='warning step'>";
    echo "<strong>⚠️ Troubleshooting:</strong><br>";
    echo "• Verify database credentials in <code>includes/config.php</code><br>";
    echo "• Check database user permissions<br>";
    echo "• Ensure MySQL server is running<br>";
    echo "• Review PHP error logs for additional details";
    echo "</div>";
}

// Security reminder
echo "<div class='section'>";
echo "<div class='warning step'>";
echo "<strong>🔒 SECURITY NOTICE</strong><br>";
echo "This setup script contains sensitive operations and should be <strong>deleted immediately</strong> after successful setup.<br>";
echo "Run: <code>rm SETUP_ADMIN.php</code> or delete through your hosting control panel.";
echo "</div>";
echo "</div>";

?>

    </div>
    
    <div style="text-align: center; margin-top: 20px; color: #7f8c8d;">
        <p>Dalthaus Photography CMS Setup Script</p>
        <p>Generated: <?php echo date('Y-m-d H:i:s T'); ?></p>
    </div>
</body>
</html>