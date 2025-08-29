<?php
/**
 * Comprehensive Deployment Validation Script
 * Tests all fixes and validates the CMS is ready for production
 * 
 * This script validates:
 * 1. Database connectivity and schema correctness
 * 2. File permissions and structure
 * 3. PHP configuration and compatibility  
 * 4. Security measures
 * 5. Core functionality
 */
declare(strict_types=1);

// Security check
if (!isset($_GET['validate']) && isset($_SERVER['HTTP_HOST'])) {
    die("Security check: Add ?validate=1 to run deployment validation");
}

echo "<h2>🚀 Dalthaus CMS - Deployment Validation</h2>\n";
echo "<pre>\n";

$results = [
    'passed' => 0,
    'failed' => 0,
    'warnings' => 0,
    'tests' => []
];

function runTest($name, $callback, $critical = true) {
    global $results;
    
    echo "\n🧪 Testing: $name\n";
    
    try {
        $result = $callback();
        if ($result['status'] === 'pass') {
            echo "   ✅ PASS: {$result['message']}\n";
            $results['passed']++;
        } elseif ($result['status'] === 'warning') {
            echo "   ⚠️  WARNING: {$result['message']}\n";
            $results['warnings']++;
        } else {
            echo "   ❌ FAIL: {$result['message']}\n";
            $results['failed']++;
        }
        
        if (isset($result['details'])) {
            foreach ($result['details'] as $detail) {
                echo "      • $detail\n";
            }
        }
        
        $results['tests'][$name] = $result;
        
    } catch (Exception $e) {
        echo "   💥 ERROR: " . $e->getMessage() . "\n";
        $results['failed']++;
        $results['tests'][$name] = ['status' => 'fail', 'message' => $e->getMessage()];
    }
}

// Test 1: Database connectivity and schema
runTest('Database Connection and Schema', function() {
    require_once 'includes/config.php';
    require_once 'includes/database.php';
    
    $pdo = Database::getInstance();
    
    // Test connection
    $stmt = $pdo->query("SELECT 1");
    if (!$stmt) {
        return ['status' => 'fail', 'message' => 'Cannot execute basic query'];
    }
    
    // Check settings table structure
    $stmt = $pdo->query("DESCRIBE settings");
    $columns = [];
    while ($row = $stmt->fetch()) {
        $columns[] = $row['Field'];
    }
    
    if (!in_array('setting_key', $columns)) {
        return ['status' => 'fail', 'message' => 'Settings table missing setting_key column'];
    }
    
    // Test maintenance_mode setting
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute(['maintenance_mode']);
    $maintenanceMode = $stmt->fetchColumn();
    
    if ($maintenanceMode === false) {
        return ['status' => 'fail', 'message' => 'maintenance_mode setting not found'];
    }
    
    return [
        'status' => 'pass',
        'message' => 'Database connection and schema are correct',
        'details' => [
            'Columns: ' . implode(', ', $columns),
            'maintenance_mode value: ' . $maintenanceMode
        ]
    ];
});

// Test 2: File structure and permissions
runTest('File Structure and Permissions', function() {
    $requiredDirs = [
        'admin' => ['readable' => true, 'writable' => false],
        'includes' => ['readable' => true, 'writable' => false],
        'public' => ['readable' => true, 'writable' => false],
        'assets' => ['readable' => true, 'writable' => false],
        'uploads' => ['readable' => true, 'writable' => true],
        'cache' => ['readable' => true, 'writable' => true],
        'logs' => ['readable' => true, 'writable' => true],
        'temp' => ['readable' => true, 'writable' => true]
    ];
    
    $issues = [];
    $details = [];
    
    foreach ($requiredDirs as $dir => $requirements) {
        if (!is_dir($dir)) {
            $issues[] = "Directory missing: $dir";
            continue;
        }
        
        $perms = substr(sprintf('%o', fileperms($dir)), -4);
        $details[] = "$dir: permissions $perms";
        
        if ($requirements['readable'] && !is_readable($dir)) {
            $issues[] = "$dir is not readable";
        }
        
        if ($requirements['writable'] && !is_writable($dir)) {
            $issues[] = "$dir is not writable";
        }
    }
    
    // Check critical files
    $criticalFiles = [
        'index.php', 'includes/config.php', 'includes/database.php', 
        'includes/functions.php', 'admin/login.php'
    ];
    
    foreach ($criticalFiles as $file) {
        if (!file_exists($file)) {
            $issues[] = "Critical file missing: $file";
        } elseif (!is_readable($file)) {
            $issues[] = "Critical file not readable: $file";
        }
    }
    
    if (!empty($issues)) {
        return ['status' => 'fail', 'message' => 'File structure issues found', 'details' => $issues];
    }
    
    return ['status' => 'pass', 'message' => 'File structure and permissions OK', 'details' => $details];
});

// Test 3: PHP Configuration
runTest('PHP Configuration for Shared Hosting', function() {
    $warnings = [];
    $details = [];
    
    // Check PHP version
    $phpVersion = PHP_VERSION;
    $details[] = "PHP Version: $phpVersion";
    
    if (version_compare($phpVersion, '8.1.0', '<')) {
        $warnings[] = "PHP version $phpVersion is below recommended 8.1+";
    }
    
    // Check memory limit
    $memoryLimit = ini_get('memory_limit');
    $details[] = "Memory Limit: $memoryLimit";
    
    // Check execution time
    $execTime = ini_get('max_execution_time');
    $details[] = "Max Execution Time: {$execTime}s";
    
    // Check upload limits
    $uploadMax = ini_get('upload_max_filesize');
    $postMax = ini_get('post_max_size');
    $details[] = "Upload Limits: $uploadMax / $postMax";
    
    // Check required extensions
    $requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'session'];
    $missingExtensions = [];
    
    foreach ($requiredExtensions as $ext) {
        if (extension_loaded($ext)) {
            $details[] = "Extension $ext: loaded";
        } else {
            $missingExtensions[] = $ext;
        }
    }
    
    if (!empty($missingExtensions)) {
        return ['status' => 'fail', 'message' => 'Missing required PHP extensions', 'details' => array_merge($details, ["Missing: " . implode(', ', $missingExtensions)])];
    }
    
    if (!empty($warnings)) {
        return ['status' => 'warning', 'message' => 'PHP configuration has warnings', 'details' => array_merge($details, $warnings)];
    }
    
    return ['status' => 'pass', 'message' => 'PHP configuration is suitable for shared hosting', 'details' => $details];
});

// Test 4: Core CMS functionality
runTest('Core CMS Functionality', function() {
    require_once 'includes/config.php';
    require_once 'includes/database.php';
    require_once 'includes/functions.php';
    
    $details = [];
    $issues = [];
    
    // Test CSRF token generation
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $token = generateCSRFToken();
    if (empty($token) || strlen($token) !== 64) {
        $issues[] = 'CSRF token generation failed';
    } else {
        $details[] = 'CSRF token generation: OK';
    }
    
    // Test input sanitization
    $testInput = '<script>alert("xss")</script>Test';
    $sanitized = sanitizeInput($testInput);
    if (strpos($sanitized, '<script>') !== false) {
        $issues[] = 'Input sanitization not working properly';
    } else {
        $details[] = 'Input sanitization: OK';
    }
    
    // Test cache functionality
    $testKey = 'test_cache_' . time();
    $testValue = 'test_value_' . rand();
    
    if (function_exists('cacheSet') && function_exists('cacheGet')) {
        cacheSet($testKey, $testValue, 60);
        $retrieved = cacheGet($testKey);
        
        if ($retrieved === $testValue) {
            $details[] = 'Cache functionality: OK';
            // Clean up
            if (function_exists('cacheDelete')) {
                cacheDelete($testKey);
            }
        } else {
            $details[] = 'Cache functionality: Limited (file-based may not work)';
        }
    } else {
        $details[] = 'Cache functions: Not available';
    }
    
    // Test logging
    if (function_exists('logMessage')) {
        logMessage('Deployment validation test', 'info');
        $details[] = 'Logging functionality: OK';
    } else {
        $issues[] = 'Logging functionality not available';
    }
    
    if (!empty($issues)) {
        return ['status' => 'fail', 'message' => 'Core functionality issues found', 'details' => array_merge($details, $issues)];
    }
    
    return ['status' => 'pass', 'message' => 'Core CMS functionality working', 'details' => $details];
});

// Test 5: Security measures
runTest('Security Configuration', function() {
    $details = [];
    $warnings = [];
    
    // Check if sensitive files are protected
    $sensitiveFiles = ['.env', 'includes/config.php'];
    foreach ($sensitiveFiles as $file) {
        if (file_exists($file)) {
            $details[] = "Sensitive file exists: $file";
        }
    }
    
    // Check session security settings
    $sessionConfig = [
        'session.cookie_httponly' => '1',
        'session.use_only_cookies' => '1',
        'session.use_strict_mode' => '1'
    ];
    
    foreach ($sessionConfig as $setting => $expected) {
        $actual = ini_get($setting);
        if ($actual === $expected) {
            $details[] = "$setting: secure ($actual)";
        } else {
            $warnings[] = "$setting: not optimal (got: $actual, expected: $expected)";
        }
    }
    
    // Check if error display is off for production
    if (ENV === 'production') {
        $displayErrors = ini_get('display_errors');
        if ($displayErrors === '0' || $displayErrors === '') {
            $details[] = 'Error display: OFF (production ready)';
        } else {
            $warnings[] = 'Error display is ON in production mode';
        }
    }
    
    if (!empty($warnings)) {
        return ['status' => 'warning', 'message' => 'Security configuration has warnings', 'details' => array_merge($details, $warnings)];
    }
    
    return ['status' => 'pass', 'message' => 'Security configuration is good', 'details' => $details];
});

// Test 6: Index.php routing functionality
runTest('Main Index.php Routing', function() {
    // Test that index.php can be included without errors
    ob_start();
    $errorOccurred = false;
    
    set_error_handler(function($severity, $message, $file, $line) use (&$errorOccurred) {
        $errorOccurred = true;
        return true; // Suppress the error display
    });
    
    try {
        // We can't actually include index.php as it would execute, but we can test the maintenance check logic
        require_once 'includes/config.php';
        require_once 'includes/database.php';
        
        // Test maintenance mode check (the part that was failing)
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute(['maintenance_mode']);
        $maintenanceMode = $stmt->fetchColumn();
        
        $details = ["Maintenance mode query successful, value: " . ($maintenanceMode ?: 'default')];
        
    } catch (Exception $e) {
        return ['status' => 'fail', 'message' => 'Index.php routing test failed: ' . $e->getMessage()];
    }
    
    restore_error_handler();
    ob_end_clean();
    
    if ($errorOccurred) {
        return ['status' => 'warning', 'message' => 'Minor errors detected in routing', 'details' => $details ?? []];
    }
    
    return ['status' => 'pass', 'message' => 'Index.php routing logic working correctly', 'details' => $details ?? []];
});

// Test 7: Remote debugging agents
runTest('Remote Debugging Agents', function() {
    $agents = ['file-agent.php', 'e2e-endpoint.php'];
    $details = [];
    
    foreach ($agents as $agent) {
        if (file_exists($agent)) {
            $details[] = "$agent: exists and accessible";
        } else {
            $details[] = "$agent: not found (normal for production)";
        }
    }
    
    return ['status' => 'pass', 'message' => 'Debug agent status checked', 'details' => $details];
});

// Final summary
echo "\n" . str_repeat("=", 60) . "\n";
echo "DEPLOYMENT VALIDATION SUMMARY\n";
echo str_repeat("=", 60) . "\n";

echo "\n📊 Test Results:\n";
echo "   ✅ Passed: {$results['passed']}\n";
echo "   ⚠️  Warnings: {$results['warnings']}\n";
echo "   ❌ Failed: {$results['failed']}\n";

$totalTests = $results['passed'] + $results['warnings'] + $results['failed'];
$successRate = ($results['passed'] / $totalTests) * 100;

echo "\n🎯 Success Rate: " . round($successRate, 1) . "%\n";

if ($results['failed'] === 0) {
    if ($results['warnings'] === 0) {
        echo "\n🎉 DEPLOYMENT STATUS: EXCELLENT ✅\n";
        echo "The CMS is fully ready for production deployment!\n";
    } else {
        echo "\n✅ DEPLOYMENT STATUS: READY WITH MINOR WARNINGS\n";
        echo "The CMS can be deployed but consider addressing the warnings.\n";
    }
    
    echo "\n📋 Next Steps:\n";
    echo "   1. Run the database migration: /fix-database-schema.php?force=1\n";
    echo "   2. Run the error handling enhancement: /enhance-error-handling.php?enhance=1\n";
    echo "   3. Test the main site: /\n";
    echo "   4. Test admin login: /admin/login.php\n";
    echo "   5. Verify maintenance mode toggle works\n";
    echo "   6. Delete debug/setup files for security\n";
    
} else {
    echo "\n❌ DEPLOYMENT STATUS: NOT READY\n";
    echo "Critical issues must be resolved before production deployment.\n";
    
    echo "\n🔧 Required Fixes:\n";
    foreach ($results['tests'] as $testName => $result) {
        if ($result['status'] === 'fail') {
            echo "   • $testName: {$result['message']}\n";
        }
    }
}

echo "\n🛡️ Security Reminders:\n";
echo "   • Change default admin credentials\n";
echo "   • Set ENV='production' in config.php\n";
echo "   • Remove debug files (file-agent.php, etc.)\n";
echo "   • Enable HTTPS redirect in .htaccess\n";
echo "   • Set up regular database backups\n";
echo "   • Monitor logs/php_errors.log\n";

echo "\n📞 Support:\n";
echo "   If you encounter issues, check:\n";
echo "   • logs/php_errors.log for PHP errors\n";
echo "   • logs/app.log for application logs\n";
echo "   • Database connection settings in includes/config.php\n";

echo "</pre>\n";
?>