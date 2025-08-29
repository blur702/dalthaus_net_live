<?php
/**
 * Comprehensive Production Test Suite for Dalthaus.net
 * 
 * Tests ALL features directly on the production server
 * Generates detailed reports and identifies issues
 * 
 * Usage: https://dalthaus.net/production-test-suite.php?token=test-YYYYMMDD
 */

// Security check
$token = $_GET['token'] ?? '';
if ($token !== 'test-' . date('Ymd')) {
    die('Invalid token. Use: test-' . date('Ymd'));
}

// Start output buffering for clean HTML
ob_start();

// Test results storage
$testResults = [];
$totalTests = 0;
$passedTests = 0;
$failedTests = 0;
$warnings = 0;

// Test logging function
function logTest($testName, $status, $message, $details = '') {
    global $testResults, $totalTests, $passedTests, $failedTests, $warnings;
    
    $totalTests++;
    $result = [
        'name' => $testName,
        'status' => $status,
        'message' => $message,
        'details' => $details,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    $testResults[] = $result;
    
    switch ($status) {
        case 'PASS':
            $passedTests++;
            break;
        case 'FAIL':
            $failedTests++;
            break;
        case 'WARN':
            $warnings++;
            break;
    }
    
    return $result;
}

// HTTP request function for testing endpoints
function testHttpRequest($url, $method = 'GET', $data = null, $headers = []) {
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Production Test Suite v1.0',
        CURLOPT_HTTPHEADER => $headers
    ]);
    
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    return [
        'response' => $response,
        'http_code' => $httpCode,
        'error' => $error,
        'success' => empty($error) && $httpCode < 400
    ];
}

// Database connection test
function testDatabaseConnection() {
    try {
        require_once 'includes/config.php';
        require_once 'includes/database.php';
        
        $pdo = Database::getInstance();
        $stmt = $pdo->query("SELECT 1");
        $result = $stmt->fetch();
        
        if ($result) {
            return logTest('Database Connection', 'PASS', 'Successfully connected to database');
        } else {
            return logTest('Database Connection', 'FAIL', 'Query executed but no result returned');
        }
    } catch (Exception $e) {
        return logTest('Database Connection', 'FAIL', 'Database connection failed', $e->getMessage());
    }
}

// Test database schema
function testDatabaseSchema() {
    try {
        require_once 'includes/database.php';
        $pdo = Database::getInstance();
        
        $requiredTables = [
            'content' => ['id', 'type', 'title', 'slug', 'body', 'status', 'created_at'],
            'content_versions' => ['id', 'content_id', 'version_number', 'is_autosave', 'created_at'],
            'menus' => ['id', 'location', 'content_id', 'sort_order'],
            'users' => ['id', 'username', 'password_hash', 'is_admin'],
            'sessions' => ['id', 'user_id', 'session_token', 'expires_at'],
            'settings' => ['setting_key', 'setting_value']
        ];
        
        $missingTables = [];
        $missingColumns = [];
        
        foreach ($requiredTables as $table => $columns) {
            // Check if table exists
            $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            
            if (!$stmt->fetch()) {
                $missingTables[] = $table;
                continue;
            }
            
            // Check columns
            $stmt = $pdo->prepare("DESCRIBE $table");
            $stmt->execute();
            $existingColumns = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
            
            foreach ($columns as $column) {
                if (!in_array($column, $existingColumns)) {
                    $missingColumns[] = "$table.$column";
                }
            }
        }
        
        if (empty($missingTables) && empty($missingColumns)) {
            return logTest('Database Schema', 'PASS', 'All required tables and columns exist');
        } else {
            $issues = [];
            if (!empty($missingTables)) {
                $issues[] = 'Missing tables: ' . implode(', ', $missingTables);
            }
            if (!empty($missingColumns)) {
                $issues[] = 'Missing columns: ' . implode(', ', $missingColumns);
            }
            return logTest('Database Schema', 'FAIL', 'Schema issues found', implode('; ', $issues));
        }
        
    } catch (Exception $e) {
        return logTest('Database Schema', 'FAIL', 'Schema check failed', $e->getMessage());
    }
}

// Test file permissions
function testFilePermissions() {
    $paths = [
        'uploads' => 'uploads',
        'cache' => 'cache',
        'logs' => 'logs',
        'temp' => 'temp'
    ];
    
    $issues = [];
    
    foreach ($paths as $name => $path) {
        if (!is_dir($path)) {
            $issues[] = "$name directory missing";
            continue;
        }
        
        if (!is_writable($path)) {
            $issues[] = "$name directory not writable";
        }
    }
    
    if (empty($issues)) {
        return logTest('File Permissions', 'PASS', 'All directories exist and are writable');
    } else {
        return logTest('File Permissions', 'FAIL', 'Permission issues found', implode('; ', $issues));
    }
}

// Test configuration
function testConfiguration() {
    require_once 'includes/config.php';
    
    $issues = [];
    
    // Check critical constants
    if (!defined('DB_NAME') || empty(DB_NAME)) {
        $issues[] = 'DB_NAME not configured';
    }
    
    if (!defined('ENV') || empty(ENV)) {
        $issues[] = 'ENV not configured';
    }
    
    if (ENV !== 'production') {
        $issues[] = 'ENV should be "production" on live server';
    }
    
    if (DEFAULT_ADMIN_PASS === '130Bpm' || DEFAULT_ADMIN_PASS === '(130Bpm)') {
        $issues[] = 'Default admin password should be changed';
    }
    
    if (empty($issues)) {
        return logTest('Configuration', 'PASS', 'Configuration looks good');
    } else {
        return logTest('Configuration', 'WARN', 'Configuration issues found', implode('; ', $issues));
    }
}

// Test public pages
function testPublicPages() {
    $baseUrl = 'https://dalthaus.net';
    
    $pages = [
        '/' => 'Homepage',
        '/articles' => 'Articles List',
        '/photobooks' => 'Photobooks List',
        '/public/error.php' => 'Error Page'
    ];
    
    $failures = [];
    $warnings = [];
    
    foreach ($pages as $path => $name) {
        $result = testHttpRequest($baseUrl . $path);
        
        if (!$result['success']) {
            $failures[] = "$name ({$result['http_code']})";
        } elseif ($result['http_code'] !== 200) {
            $warnings[] = "$name ({$result['http_code']})";
        }
        
        // Check for PHP errors in response
        if (strpos($result['response'], 'Parse error') !== false || 
            strpos($result['response'], 'Fatal error') !== false) {
            $failures[] = "$name (PHP errors detected)";
        }
    }
    
    if (empty($failures) && empty($warnings)) {
        return logTest('Public Pages', 'PASS', 'All public pages accessible');
    } elseif (empty($failures)) {
        return logTest('Public Pages', 'WARN', 'Some pages have warnings', implode('; ', $warnings));
    } else {
        return logTest('Public Pages', 'FAIL', 'Page access failures', implode('; ', $failures));
    }
}

// Test admin login
function testAdminLogin() {
    $baseUrl = 'https://dalthaus.net';
    
    // Test login page accessibility
    $result = testHttpRequest($baseUrl . '/admin/login.php');
    
    if (!$result['success']) {
        return logTest('Admin Login', 'FAIL', 'Login page not accessible', "HTTP {$result['http_code']}");
    }
    
    // Check for login form
    if (strpos($result['response'], 'form') === false || 
        strpos($result['response'], 'username') === false) {
        return logTest('Admin Login', 'FAIL', 'Login form not found in page');
    }
    
    return logTest('Admin Login', 'PASS', 'Login page accessible and form present');
}

// Test CSS and assets
function testAssets() {
    $baseUrl = 'https://dalthaus.net';
    
    $assets = [
        '/assets/css/public.css' => 'Public CSS',
        '/assets/css/admin.css' => 'Admin CSS',
        '/assets/js/autosave.js' => 'Autosave JS',
        '/assets/js/sorting.js' => 'Sorting JS'
    ];
    
    $failures = [];
    
    foreach ($assets as $path => $name) {
        $result = testHttpRequest($baseUrl . $path);
        
        if (!$result['success'] || $result['http_code'] !== 200) {
            $failures[] = "$name ({$result['http_code']})";
        }
    }
    
    if (empty($failures)) {
        return logTest('CSS & JS Assets', 'PASS', 'All assets accessible');
    } else {
        return logTest('CSS & JS Assets', 'FAIL', 'Asset loading failures', implode('; ', $failures));
    }
}

// Test content functionality
function testContentExists() {
    try {
        require_once 'includes/database.php';
        $pdo = Database::getInstance();
        
        // Check for published content
        $stmt = $pdo->query("SELECT COUNT(*) FROM content WHERE status = 'published' AND deleted_at IS NULL");
        $contentCount = $stmt->fetchColumn();
        
        if ($contentCount > 0) {
            return logTest('Published Content', 'PASS', "$contentCount published items found");
        } else {
            return logTest('Published Content', 'WARN', 'No published content found');
        }
        
    } catch (Exception $e) {
        return logTest('Published Content', 'FAIL', 'Content check failed', $e->getMessage());
    }
}

// Test maintenance mode functionality
function testMaintenanceMode() {
    try {
        require_once 'includes/database.php';
        $pdo = Database::getInstance();
        
        // Check if maintenance mode setting exists
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'maintenance_mode'");
        $stmt->execute();
        $maintenance = $stmt->fetchColumn();
        
        if ($maintenance !== false) {
            $status = $maintenance === '1' ? 'ENABLED' : 'DISABLED';
            return logTest('Maintenance Mode', 'PASS', "Maintenance mode is $status");
        } else {
            return logTest('Maintenance Mode', 'WARN', 'Maintenance mode setting not found in database');
        }
        
    } catch (Exception $e) {
        return logTest('Maintenance Mode', 'FAIL', 'Maintenance mode check failed', $e->getMessage());
    }
}

// Test fonts loading
function testFonts() {
    $baseUrl = 'https://dalthaus.net';
    
    // Get the homepage and check for font loading
    $result = testHttpRequest($baseUrl);
    
    if (!$result['success']) {
        return logTest('Font Loading', 'FAIL', 'Cannot access homepage to check fonts');
    }
    
    $hasArimo = strpos($result['response'], 'Arimo') !== false;
    $hasGelasio = strpos($result['response'], 'Gelasio') !== false;
    
    if ($hasArimo && $hasGelasio) {
        return logTest('Font Loading', 'PASS', 'Both Arimo and Gelasio fonts referenced');
    } else {
        $missing = [];
        if (!$hasArimo) $missing[] = 'Arimo';
        if (!$hasGelasio) $missing[] = 'Gelasio';
        
        return logTest('Font Loading', 'WARN', 'Missing font references: ' . implode(', ', $missing));
    }
}

// Start HTML output
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Production Test Suite - Dalthaus.net</title>
    <style>
        body { 
            font-family: 'Courier New', monospace; 
            background: #0a0a0a; 
            color: #00ff00; 
            margin: 0; 
            padding: 20px;
            line-height: 1.6;
        }
        .header {
            text-align: center;
            border: 2px solid #00ff00;
            padding: 20px;
            margin-bottom: 30px;
            background: #001100;
        }
        .test-section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #004400;
            background: #001a00;
        }
        .test-result {
            margin: 10px 0;
            padding: 10px;
            border-left: 4px solid;
        }
        .test-result.pass {
            border-color: #00ff00;
            background: rgba(0, 255, 0, 0.1);
        }
        .test-result.fail {
            border-color: #ff0000;
            background: rgba(255, 0, 0, 0.1);
            color: #ffcccc;
        }
        .test-result.warn {
            border-color: #ffff00;
            background: rgba(255, 255, 0, 0.1);
            color: #ffffcc;
        }
        .summary {
            text-align: center;
            font-size: 18px;
            padding: 20px;
            margin: 30px 0;
            border: 2px solid;
        }
        .summary.pass { border-color: #00ff00; background: rgba(0, 255, 0, 0.1); }
        .summary.fail { border-color: #ff0000; background: rgba(255, 0, 0, 0.1); }
        .summary.warn { border-color: #ffff00; background: rgba(255, 255, 0, 0.1); }
        .details {
            font-size: 12px;
            opacity: 0.8;
            margin-top: 5px;
            padding: 5px;
            background: rgba(0, 0, 0, 0.3);
        }
        .timestamp {
            color: #666;
            font-size: 11px;
        }
        .actions {
            text-align: center;
            margin-top: 30px;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background: #00aa00;
            color: #000;
            text-decoration: none;
            margin: 10px;
            border: 1px solid #00ff00;
        }
        .button:hover { background: #00ff00; }
    </style>
</head>
<body>

<div class="header">
    <h1>🚀 DALTHAUS.NET PRODUCTION TEST SUITE</h1>
    <p>Comprehensive testing of ALL features on production server</p>
    <div class="timestamp">Started: <?php echo date('Y-m-d H:i:s T'); ?></div>
</div>

<div class="test-section">
    <h2>🔧 INFRASTRUCTURE TESTS</h2>
    <?php
    $tests = [
        'testDatabaseConnection',
        'testDatabaseSchema', 
        'testFilePermissions',
        'testConfiguration'
    ];
    
    foreach ($tests as $test) {
        $result = $test();
        $statusClass = strtolower($result['status']);
        echo "<div class='test-result $statusClass'>";
        echo "<strong>{$result['status']}</strong>: {$result['name']} - {$result['message']}";
        if (!empty($result['details'])) {
            echo "<div class='details'>{$result['details']}</div>";
        }
        echo "</div>";
    }
    ?>
</div>

<div class="test-section">
    <h2>🌐 WEB INTERFACE TESTS</h2>
    <?php
    $tests = [
        'testPublicPages',
        'testAdminLogin',
        'testAssets',
        'testFonts'
    ];
    
    foreach ($tests as $test) {
        $result = $test();
        $statusClass = strtolower($result['status']);
        echo "<div class='test-result $statusClass'>";
        echo "<strong>{$result['status']}</strong>: {$result['name']} - {$result['message']}";
        if (!empty($result['details'])) {
            echo "<div class='details'>{$result['details']}</div>";
        }
        echo "</div>";
    }
    ?>
</div>

<div class="test-section">
    <h2>📄 CONTENT & FEATURES TESTS</h2>
    <?php
    $tests = [
        'testContentExists',
        'testMaintenanceMode'
    ];
    
    foreach ($tests as $test) {
        $result = $test();
        $statusClass = strtolower($result['status']);
        echo "<div class='test-result $statusClass'>";
        echo "<strong>{$result['status']}</strong>: {$result['name']} - {$result['message']}";
        if (!empty($result['details'])) {
            echo "<div class='details'>{$result['details']}</div>";
        }
        echo "</div>";
    }
    ?>
</div>

<?php
// Generate summary
$overallStatus = 'PASS';
if ($failedTests > 0) {
    $overallStatus = 'FAIL';
} elseif ($warnings > 0) {
    $overallStatus = 'WARN';
}

$summaryClass = strtolower($overallStatus);
$percentage = $totalTests > 0 ? round(($passedTests / $totalTests) * 100, 1) : 0;
?>

<div class="summary <?php echo $summaryClass; ?>">
    <h2>📊 TEST SUMMARY</h2>
    <div style="font-size: 24px; margin: 20px 0;">
        <strong><?php echo $overallStatus; ?>: <?php echo $percentage; ?>% Success Rate</strong>
    </div>
    <div>
        ✅ Passed: <?php echo $passedTests; ?> | 
        ❌ Failed: <?php echo $failedTests; ?> | 
        ⚠️ Warnings: <?php echo $warnings; ?> | 
        📊 Total: <?php echo $totalTests; ?>
    </div>
    <div class="timestamp" style="margin-top: 15px;">
        Completed: <?php echo date('Y-m-d H:i:s T'); ?>
    </div>
</div>

<div class="actions">
    <h3>🎛️ QUICK ACTIONS</h3>
    <a href="auto-deploy.php?action=status&token=deploy-<?php echo date('Ymd'); ?>" class="button">
        📋 Check Deployment Status
    </a>
    <a href="auto-deploy.php?action=pull&token=deploy-<?php echo date('Ymd'); ?>" class="button">
        🔄 Pull Latest Code
    </a>
    <a href="/" class="button">
        🏠 View Homepage
    </a>
    <a href="admin/login.php" class="button">
        👤 Admin Login
    </a>
    <a href="?token=<?php echo $_GET['token']; ?>&refresh=1" class="button">
        🔁 Re-run Tests
    </a>
</div>

<?php
// Save test report
$reportData = [
    'timestamp' => date('Y-m-d H:i:s'),
    'total_tests' => $totalTests,
    'passed' => $passedTests,
    'failed' => $failedTests,
    'warnings' => $warnings,
    'success_rate' => $percentage,
    'overall_status' => $overallStatus,
    'tests' => $testResults
];

$reportFile = 'logs/production-test-' . date('Y-m-d-H-i-s') . '.json';
if (!is_dir('logs')) {
    mkdir('logs', 0755, true);
}

file_put_contents($reportFile, json_encode($reportData, JSON_PRETTY_PRINT));

echo "<div style='text-align: center; margin-top: 30px; opacity: 0.7;'>";
echo "📄 Detailed test report saved to: $reportFile<br>";
echo "🔗 Direct access: <a href='production-test-suite.php?token=test-" . date('Ymd') . "' style='color: #00ff00;'>production-test-suite.php?token=test-" . date('Ymd') . "</a>";
echo "</div>";
?>

</body>
</html>