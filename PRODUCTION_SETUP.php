<?php
/**
 * PRODUCTION SETUP SCRIPT
 * 
 * Comprehensive setup and validation script for Dalthaus.net CMS
 * Runs database setup, tests all functionality, and reports issues
 * 
 * USAGE: Access via web browser: https://dalthaus.net/PRODUCTION_SETUP.php
 */
declare(strict_types=1);

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');

// Start output buffering for clean error handling
ob_start();

echo "<!DOCTYPE html>\n<html><head><title>Dalthaus CMS - Production Setup</title>\n";
echo "<style>body{font-family:monospace;max-width:1200px;margin:0 auto;padding:20px;}";
echo ".success{color:green;}.error{color:red;}.warning{color:orange;}.info{color:blue;}";
echo ".section{margin:20px 0;border:1px solid #ccc;padding:15px;border-radius:5px;}";
echo "pre{background:#f5f5f5;padding:10px;overflow-x:auto;}</style></head><body>\n";

echo "<h1>🔧 Dalthaus CMS Production Setup</h1>\n";
echo "<p>Starting comprehensive setup and validation...</p>\n";

$errors = [];
$warnings = [];
$successes = [];

function logResult($type, $message) {
    global $errors, $warnings, $successes;
    $timestamp = date('H:i:s');
    $formatted = "[$timestamp] $message";
    
    switch($type) {
        case 'error':
            $errors[] = $formatted;
            echo "<div class='error'>❌ $formatted</div>\n";
            break;
        case 'warning':
            $warnings[] = $formatted;
            echo "<div class='warning'>⚠️  $formatted</div>\n";
            break;
        case 'success':
            $successes[] = $formatted;
            echo "<div class='success'>✅ $formatted</div>\n";
            break;
        case 'info':
            echo "<div class='info'>ℹ️  [$timestamp] $message</div>\n";
            break;
    }
    
    // Flush output immediately
    if (ob_get_level()) {
        ob_flush();
    }
    flush();
}

// ============================================================================
// STEP 1: VALIDATE CONFIGURATION
// ============================================================================
echo "<div class='section'><h2>📋 Step 1: Configuration Validation</h2>\n";

try {
    require_once 'includes/config.php';
    logResult('success', 'Configuration file loaded successfully');
    
    // Test critical constants
    $requiredConstants = [
        'DB_HOST' => DB_HOST,
        'DB_NAME' => DB_NAME,
        'DB_USER' => DB_USER,
        'DB_PASS' => DB_PASS,
        'ENV' => ENV,
        'DEFAULT_ADMIN_USER' => DEFAULT_ADMIN_USER,
        'DEFAULT_ADMIN_PASS' => DEFAULT_ADMIN_PASS
    ];
    
    foreach ($requiredConstants as $name => $value) {
        if (empty($value) && $value !== '0') {
            logResult('error', "Required constant $name is empty");
        } else {
            logResult('info', "$name = " . ($name === 'DB_PASS' || $name === 'DEFAULT_ADMIN_PASS' ? '***' : $value));
        }
    }
    
    // Check environment
    if (ENV === 'production') {
        logResult('success', 'Environment set to production');
    } else {
        logResult('warning', 'Environment is not production mode');
    }
    
} catch (Exception $e) {
    logResult('error', 'Configuration error: ' . $e->getMessage());
}

// ============================================================================  
// STEP 2: DATABASE CONNECTION TEST
// ============================================================================
echo "<h2>🗄️ Step 2: Database Connection</h2>\n";

try {
    require_once 'includes/database.php';
    
    // Test connection without database
    $dsn = sprintf('mysql:host=%s;charset=utf8mb4', DB_HOST);
    $testPdo = new PDO($dsn, DB_USER, DB_PASS);
    logResult('success', 'MySQL connection successful');
    
    // Check if database exists
    $stmt = $testPdo->prepare("SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?");
    $stmt->execute([DB_NAME]);
    $dbExists = $stmt->fetchColumn();
    
    if ($dbExists) {
        logResult('info', "Database '" . DB_NAME . "' already exists");
    } else {
        logResult('warning', "Database '" . DB_NAME . "' does not exist - will be created");
    }
    
} catch (PDOException $e) {
    logResult('error', 'Database connection failed: ' . $e->getMessage());
    logResult('error', 'Check your database credentials in includes/config.php');
}

// ============================================================================
// STEP 3: DATABASE SETUP
// ============================================================================
echo "<h2>🔨 Step 3: Database Setup</h2>\n";

try {
    Database::setup();
    logResult('success', 'Database setup completed successfully');
    
    // Verify tables were created
    $pdo = Database::getInstance();
    $tables = ['users', 'content', 'content_versions', 'menus', 'attachments', 'settings', 'sessions'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = ?");
        $stmt->execute([DB_NAME, $table]);
        $exists = $stmt->fetchColumn();
        
        if ($exists) {
            logResult('success', "Table '$table' created successfully");
        } else {
            logResult('error', "Table '$table' was not created");
        }
    }
    
    // Check admin user
    $stmt = $pdo->prepare("SELECT username, role FROM users WHERE role = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if ($admin) {
        logResult('success', "Admin user '{$admin['username']}' created successfully");
    } else {
        logResult('error', 'No admin user found');
    }
    
} catch (Exception $e) {
    logResult('error', 'Database setup failed: ' . $e->getMessage());
}

// ============================================================================
// STEP 4: FILE SYSTEM CHECKS
// ============================================================================
echo "<h2>📁 Step 4: File System Validation</h2>\n";

$directories = [
    'admin' => 'Admin interface files',
    'public' => 'Public content files', 
    'includes' => 'Core system files',
    'assets' => 'CSS/JS/image assets',
    'uploads' => 'User uploaded files',
    'cache' => 'Performance cache',
    'logs' => 'Application logs',
    'temp' => 'Temporary files'
];

foreach ($directories as $dir => $description) {
    if (is_dir($dir)) {
        if (is_writable($dir)) {
            logResult('success', "$dir/ - $description (writable)");
        } else {
            logResult('warning', "$dir/ - $description (not writable)");
        }
    } else {
        logResult('error', "$dir/ - Missing directory: $description");
    }
}

// Check critical files
$criticalFiles = [
    '.htaccess' => 'URL rewriting configuration',
    'index.php' => 'Main router',
    'admin/login.php' => 'Admin authentication',
    'includes/config.php' => 'System configuration',
    'includes/database.php' => 'Database layer',
    'includes/auth.php' => 'Authentication system',
    'assets/css/admin.css' => 'Admin styles',
    'assets/css/public.css' => 'Public styles'
];

foreach ($criticalFiles as $file => $description) {
    if (file_exists($file)) {
        logResult('success', "$file - $description");
    } else {
        logResult('error', "$file - Missing: $description");
    }
}

// ============================================================================
// STEP 5: AUTHENTICATION TEST
// ============================================================================
echo "<h2>🔐 Step 5: Authentication System Test</h2>\n";

try {
    require_once 'includes/auth.php';
    
    // Test with correct credentials
    if (Auth::login(DEFAULT_ADMIN_USER, DEFAULT_ADMIN_PASS)) {
        logResult('success', 'Admin authentication test passed');
        Auth::logout(); // Clean up session
    } else {
        logResult('error', 'Admin authentication test failed');
    }
    
    // Test with wrong credentials  
    if (!Auth::login(DEFAULT_ADMIN_USER, 'wrongpassword')) {
        logResult('success', 'Authentication properly rejects invalid credentials');
    } else {
        logResult('error', 'Authentication security issue: accepted wrong password');
        Auth::logout();
    }
    
} catch (Exception $e) {
    logResult('error', 'Authentication system error: ' . $e->getMessage());
}

// ============================================================================
// STEP 6: URL ROUTING TEST
// ============================================================================
echo "<h2>🔗 Step 6: URL Routing Validation</h2>\n";

// Test if .htaccess is working
if (isset($_SERVER['REQUEST_URI'])) {
    logResult('info', 'Current URL: ' . $_SERVER['REQUEST_URI']);
}

// Check if mod_rewrite is available
if (function_exists('apache_get_modules')) {
    $modules = apache_get_modules();
    if (in_array('mod_rewrite', $modules)) {
        logResult('success', 'mod_rewrite is enabled');
    } else {
        logResult('error', 'mod_rewrite is not enabled - required for clean URLs');
    }
} else {
    logResult('info', 'Cannot detect mod_rewrite (function not available)');
}

// ============================================================================
// STEP 7: SECURITY VALIDATION
// ============================================================================
echo "<h2>🛡️ Step 7: Security Check</h2>\n";

// Check session configuration
session_start();
if (session_status() === PHP_SESSION_ACTIVE) {
    logResult('success', 'PHP sessions are working');
    
    // Check session security settings
    $secureSettings = [
        'session.cookie_httponly' => '1',
        'session.use_only_cookies' => '1'
    ];
    
    foreach ($secureSettings as $setting => $expected) {
        $actual = ini_get($setting);
        if ($actual == $expected) {
            logResult('success', "$setting = $actual (secure)");
        } else {
            logResult('warning', "$setting = $actual (should be $expected)");
        }
    }
} else {
    logResult('error', 'PHP sessions are not working');
}

// Test CSRF functions
require_once 'includes/functions.php';
$csrf = generateCSRFToken();
if (validateCSRFToken($csrf)) {
    logResult('success', 'CSRF protection is working');
} else {
    logResult('error', 'CSRF protection is not working');
}

// ============================================================================
// STEP 8: PERFORMANCE TESTS
// ============================================================================
echo "<h2>⚡ Step 8: Performance Check</h2>\n";

// Test caching
$testKey = 'test_' . time();
$testValue = 'test_data_' . rand(1000, 9999);

// We'll skip cache test since it requires the full system to be running

// Check PHP memory limit
$memoryLimit = ini_get('memory_limit');
logResult('info', "PHP memory limit: $memoryLimit");

$timeLimit = ini_get('max_execution_time');
logResult('info', "PHP execution time limit: {$timeLimit}s");

// ============================================================================
// SUMMARY REPORT
// ============================================================================
echo "</div><div class='section'><h2>📊 Setup Summary</h2>\n";

echo "<h3>✅ Successes (" . count($successes) . ")</h3>\n";
if (!empty($successes)) {
    echo "<pre>" . implode("\n", $successes) . "</pre>\n";
} else {
    echo "<p>No successful operations recorded.</p>\n";
}

echo "<h3>⚠️ Warnings (" . count($warnings) . ")</h3>\n";
if (!empty($warnings)) {
    echo "<pre>" . implode("\n", $warnings) . "</pre>\n";
} else {
    echo "<p>No warnings.</p>\n";
}

echo "<h3>❌ Errors (" . count($errors) . ")</h3>\n";
if (!empty($errors)) {
    echo "<pre>" . implode("\n", $errors) . "</pre>\n";
} else {
    echo "<p>No errors detected!</p>\n";
}

// ============================================================================
// RECOMMENDATIONS
// ============================================================================
echo "<h2>💡 Next Steps</h2>\n";

if (empty($errors)) {
    echo "<div class='success'>\n";
    echo "<h3>🎉 Setup Complete!</h3>\n";
    echo "<p>Your Dalthaus CMS installation appears to be working correctly.</p>\n";
    echo "<ul>\n";
    echo "<li><strong>Admin Login:</strong> <a href='/admin/login.php'>/admin/login.php</a></li>\n";
    echo "<li><strong>Username:</strong> " . DEFAULT_ADMIN_USER . "</li>\n";
    echo "<li><strong>Password:</strong> " . DEFAULT_ADMIN_PASS . "</li>\n";
    echo "<li><strong>Public Site:</strong> <a href='/'>/</a></li>\n";
    echo "</ul>\n";
    echo "<p><strong>IMPORTANT:</strong> Change the admin password after first login!</p>\n";
    echo "<p>You may delete this PRODUCTION_SETUP.php file for security.</p>\n";
    echo "</div>\n";
} else {
    echo "<div class='error'>\n";
    echo "<h3>🚨 Setup Issues Found</h3>\n";
    echo "<p>Please address the errors above before proceeding:</p>\n";
    echo "<ol>\n";
    if (strpos(implode(' ', $errors), 'Database connection') !== false) {
        echo "<li>Verify database credentials in includes/config.php</li>\n";
    }
    if (strpos(implode(' ', $errors), 'mod_rewrite') !== false) {
        echo "<li>Enable mod_rewrite in Apache configuration</li>\n";
    }
    if (strpos(implode(' ', $errors), 'Authentication') !== false) {
        echo "<li>Check admin user creation in database</li>\n";
    }
    echo "</ol>\n";
    echo "</div>\n";
}

$setupTime = number_format((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 2);
echo "<p><em>Setup completed in {$setupTime}ms</em></p>\n";

echo "</div></body></html>\n";

// Clean up output buffer
if (ob_get_level()) {
    ob_end_flush();
}
?>