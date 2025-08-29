<?php
/**
 * QUICK VALIDATION TEST
 * 
 * Simple test to verify basic functionality is working
 * Access via: https://dalthaus.net/QUICK_TEST.php
 */
declare(strict_types=1);

// Enable error display
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<!DOCTYPE html><html><head><title>Quick Test</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;}";
echo ".pass{color:green;font-weight:bold;}.fail{color:red;font-weight:bold;}</style></head><body>";

echo "<h1>🔍 Dalthaus CMS Quick Test</h1>";
echo "<p>Testing core functionality...</p><hr>";

$tests = 0;
$passed = 0;

function test($description, $condition) {
    global $tests, $passed;
    $tests++;
    $status = $condition ? 'PASS' : 'FAIL';
    $class = $condition ? 'pass' : 'fail';
    echo "<p>$tests. $description: <span class='$class'>$status</span></p>";
    if ($condition) $passed++;
    return $condition;
}

// Test 1: Configuration loading
try {
    require_once 'includes/config.php';
    test('Configuration file loads', true);
    test('Database name is dalthaus_cms', DB_NAME === 'dalthaus_cms');
    test('Database user is kevin', DB_USER === 'kevin');
    test('Database password is set', !empty(DB_PASS));
    test('Environment is production', ENV === 'production');
} catch (Exception $e) {
    test('Configuration file loads', false);
    echo "<p>Error: " . $e->getMessage() . "</p>";
}

// Test 2: Database connection
try {
    require_once 'includes/database.php';
    $pdo = new PDO(sprintf('mysql:host=%s;charset=utf8mb4', DB_HOST), DB_USER, DB_PASS);
    test('Database connection successful', true);
    
    // Check if database exists
    $stmt = $pdo->prepare("SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?");
    $stmt->execute([DB_NAME]);
    $dbExists = $stmt->fetchColumn();
    test('Database exists', (bool)$dbExists);
    
    if ($dbExists) {
        $pdo->exec("USE " . DB_NAME);
        
        // Check critical tables
        $tables = ['users', 'content', 'settings'];
        foreach ($tables as $table) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = ?");
            $stmt->execute([DB_NAME, $table]);
            $exists = $stmt->fetchColumn();
            test("Table '$table' exists", (bool)$exists);
        }
        
        // Check admin user
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
            $stmt->execute();
            $adminExists = $stmt->fetchColumn() > 0;
            test('Admin user exists', $adminExists);
        } catch (Exception $e) {
            test('Admin user exists', false);
        }
    }
    
} catch (PDOException $e) {
    test('Database connection successful', false);
    echo "<p>Database Error: " . $e->getMessage() . "</p>";
}

// Test 3: Authentication system
try {
    require_once 'includes/auth.php';
    test('Authentication class loads', true);
    
    // Test method exists
    test('Auth::login method exists', method_exists('Auth', 'login'));
    test('Auth::requireAdmin method exists', method_exists('Auth', 'requireAdmin'));
    
} catch (Exception $e) {
    test('Authentication class loads', false);
    echo "<p>Auth Error: " . $e->getMessage() . "</p>";
}

// Test 4: Core functions
try {
    require_once 'includes/functions.php';
    test('Functions file loads', true);
    
    // Test CSRF functions
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $csrf = generateCSRFToken();
    test('CSRF token generation works', !empty($csrf));
    test('CSRF token validation works', validateCSRFToken($csrf));
    
} catch (Exception $e) {
    test('Functions file loads', false);
    echo "<p>Functions Error: " . $e->getMessage() . "</p>";
}

// Test 5: File system
$criticalFiles = [
    '.htaccess' => 'URL rewriting',
    'admin/login.php' => 'Admin login',
    'assets/css/admin.css' => 'Admin styles',
    'assets/css/public.css' => 'Public styles',
];

foreach ($criticalFiles as $file => $desc) {
    test("$desc file exists", file_exists($file));
}

$criticalDirs = ['uploads', 'cache', 'logs'];
foreach ($criticalDirs as $dir) {
    test("$dir directory writable", is_writable($dir));
}

// Summary
echo "<hr><h2>Test Results</h2>";
echo "<p><strong>Passed: $passed / $tests tests</strong></p>";

if ($passed === $tests) {
    echo "<div style='background:#d4edda;border:1px solid #c3e6cb;color:#155724;padding:15px;border-radius:5px;'>";
    echo "<h3>🎉 All Tests Passed!</h3>";
    echo "<p>Your CMS appears to be configured correctly. You can now:</p>";
    echo "<ul>";
    echo "<li><a href='/admin/login.php'>Login to Admin Panel</a> (kevin / (130Bpm))</li>";
    echo "<li><a href='/'>View Public Site</a></li>";
    echo "</ul>";
    echo "<p><strong>IMPORTANT:</strong> Delete this test file and change the admin password!</p>";
    echo "</div>";
} else {
    echo "<div style='background:#f8d7da;border:1px solid #f5c6cb;color:#721c24;padding:15px;border-radius:5px;'>";
    echo "<h3>❌ Issues Found</h3>";
    echo "<p>Please review the failed tests above and run the full <a href='/PRODUCTION_SETUP.php'>PRODUCTION_SETUP.php</a> for detailed diagnostics.</p>";
    echo "</div>";
}

echo "<p><em>Generated: " . date('Y-m-d H:i:s') . "</em></p>";
echo "</body></html>";
?>