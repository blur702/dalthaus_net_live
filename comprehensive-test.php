<?php
/**
 * Comprehensive Site Test - Tests all critical functionality
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

echo "<h2>Comprehensive Site Functionality Test</h2>\n";
echo "<p>Environment: " . ENV . "</p>\n";
echo "<hr>\n";

// Test 1: Database Connection
echo "<h3>1. Database Connection Test</h3>\n";
try {
    $pdo = Database::getInstance();
    echo "<p style='color: green;'>✓ Database connection successful!</p>\n";
    
    // Test essential tables
    $tables = ['users', 'content', 'settings', 'content_versions', 'menus'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table");
            $stmt->execute();
            $count = $stmt->fetchColumn();
            echo "<p>✓ Table '$table': $count records</p>\n";
        } catch (Exception $e) {
            echo "<p style='color: red;'>✗ Table '$table' error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

echo "<hr>\n";

// Test 2: Cache Functions
echo "<h3>2. Cache Functions Test</h3>\n";
try {
    $testValue = 'Test cache value: ' . time();
    $cacheResult = cacheSet('test_key', $testValue);
    echo "<p>" . ($cacheResult ? '✓' : '✗') . " Cache set function</p>\n";
    
    $retrievedValue = cacheGet('test_key');
    echo "<p>" . ($retrievedValue === $testValue ? '✓' : '✗') . " Cache get function</p>\n";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Cache functions error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

echo "<hr>\n";

// Test 3: Session Functions  
echo "<h3>3. Session Test</h3>\n";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['test'] = 'session_working';
echo "<p>" . (isset($_SESSION['test']) && $_SESSION['test'] === 'session_working' ? '✓' : '✗') . " Session functionality</p>\n";

echo "<hr>\n";

// Test 4: Content Queries
echo "<h3>4. Content Queries Test</h3>\n";
try {
    if (isset($pdo)) {
        // Test articles
        $stmt = $pdo->query("SELECT COUNT(*) FROM content WHERE type = 'article' AND status = 'published' AND deleted_at IS NULL");
        $articleCount = $stmt->fetchColumn();
        echo "<p>✓ Published articles: $articleCount</p>\n";
        
        // Test photobooks
        $stmt = $pdo->query("SELECT COUNT(*) FROM content WHERE type = 'photobook' AND status = 'published' AND deleted_at IS NULL");
        $photobookCount = $stmt->fetchColumn();
        echo "<p>✓ Published photobooks: $photobookCount</p>\n";
        
        // Test if homepage will have content
        if ($articleCount > 0 || $photobookCount > 0) {
            echo "<p style='color: green;'>✓ Homepage will display content</p>\n";
        } else {
            echo "<p style='color: orange;'>⚠ Homepage will show 'no content' messages</p>\n";
        }
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Content queries error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

echo "<hr>\n";

// Test 5: File Permissions
echo "<h3>5. File Permissions Test</h3>\n";
$paths = [
    CACHE_PATH => 'Cache directory',
    LOG_PATH => 'Log directory', 
    UPLOAD_PATH => 'Upload directory',
    TEMP_PATH => 'Temp directory'
];

foreach ($paths as $path => $name) {
    if (is_dir($path)) {
        echo "<p>" . (is_writable($path) ? '✓' : '✗') . " $name writable</p>\n";
    } else {
        echo "<p style='color: orange;'>⚠ $name does not exist</p>\n";
    }
}

echo "<hr>\n";

// Test 6: Critical Constants
echo "<h3>6. Configuration Constants Test</h3>\n";
$constants = [
    'DB_HOST' => DB_HOST,
    'DB_NAME' => DB_NAME,
    'DB_USER' => DB_USER,
    'DB_PASS' => DB_PASS ? '[SET]' : '[EMPTY]',
    'ENV' => ENV,
    'ROOT_PATH' => ROOT_PATH,
    'CACHE_ENABLED' => CACHE_ENABLED ? 'true' : 'false'
];

foreach ($constants as $name => $value) {
    echo "<p>$name: " . htmlspecialchars($value) . "</p>\n";
}

echo "<hr>\n";

// Test 7: Admin User Check
echo "<h3>7. Admin User Test</h3>\n";
try {
    if (isset($pdo)) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
        $adminCount = $stmt->fetchColumn();
        echo "<p>" . ($adminCount > 0 ? '✓' : '✗') . " Admin users exist: $adminCount</p>\n";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Admin user check error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

echo "<hr>\n";
echo "<h3>Summary</h3>\n";
echo "<p>Test completed at: " . date('Y-m-d H:i:s') . "</p>\n";

// Clean up test cache
try {
    cacheClear('test_key');
} catch (Exception $e) {
    // Ignore cache clear errors
}
?>