<?php
/**
 * Final Deployment Validation Script
 * 
 * Quick health check for production deployment
 * Tests critical functionality and reports issues
 */

// Security check
$token = $_GET['token'] ?? '';
if ($token !== 'validate-' . date('Ymd')) {
    die('Invalid token. Use: validate-' . date('Ymd'));
}

// Test results
$results = [];
$criticalIssues = [];
$warnings = [];
$success = 0;
$total = 0;

function test($name, $condition, $errorMessage = '') {
    global $results, $criticalIssues, $warnings, $success, $total;
    
    $total++;
    $result = [
        'name' => $name,
        'status' => $condition ? 'PASS' : 'FAIL',
        'message' => $condition ? 'OK' : $errorMessage,
        'critical' => strpos(strtolower($name), 'critical') !== false
    ];
    
    $results[] = $result;
    
    if ($condition) {
        $success++;
    } else {
        if ($result['critical']) {
            $criticalIssues[] = $name;
        } else {
            $warnings[] = $name;
        }
    }
    
    return $condition;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deployment Validation - Dalthaus.net</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        .header h1 { font-size: 2.5em; margin-bottom: 10px; }
        .header p { opacity: 0.9; font-size: 1.1em; }
        .status-banner {
            padding: 20px;
            text-align: center;
            font-size: 1.2em;
            font-weight: bold;
        }
        .status-banner.success {
            background: #2ecc71;
            color: white;
        }
        .status-banner.warning {
            background: #f39c12;
            color: white;
        }
        .status-banner.critical {
            background: #e74c3c;
            color: white;
        }
        .content { padding: 30px; }
        .test-group {
            margin-bottom: 30px;
            border: 1px solid #ecf0f1;
            border-radius: 8px;
            overflow: hidden;
        }
        .test-group h3 {
            background: #f8f9fa;
            padding: 15px 20px;
            margin: 0;
            color: #2c3e50;
            border-bottom: 1px solid #ecf0f1;
        }
        .test-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid #ecf0f1;
        }
        .test-item:last-child { border-bottom: none; }
        .test-name { font-weight: 500; }
        .test-status {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: bold;
        }
        .test-status.pass {
            background: #2ecc71;
            color: white;
        }
        .test-status.fail {
            background: #e74c3c;
            color: white;
        }
        .summary {
            text-align: center;
            padding: 30px;
            background: #f8f9fa;
            margin-top: 30px;
            border-radius: 8px;
        }
        .summary-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .stat {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #2c3e50;
        }
        .stat-label {
            color: #7f8c8d;
            font-size: 0.9em;
            margin-top: 5px;
        }
        .actions {
            text-align: center;
            margin-top: 30px;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 25px;
            margin: 0 10px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .btn.success { background: #2ecc71; }
        .btn.warning { background: #f39c12; }
        .btn.danger { background: #e74c3c; }
        .timestamp {
            text-align: center;
            color: #7f8c8d;
            font-size: 0.9em;
            margin-top: 20px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>🔍 Deployment Validation</h1>
        <p>Quick health check for Dalthaus.net production deployment</p>
    </div>

    <?php
    // Run critical tests
    echo "<div class='content'>";
    
    // Database Connection Test
    try {
        require_once 'includes/config.php';
        require_once 'includes/database.php';
        $pdo = Database::getInstance();
        $stmt = $pdo->query("SELECT 1");
        test('Critical: Database Connection', (bool)$stmt->fetch(), 'Cannot connect to database');
    } catch (Exception $e) {
        test('Critical: Database Connection', false, $e->getMessage());
    }
    
    // Configuration Test
    test('Critical: Configuration File', file_exists('includes/config.php'), 'Configuration file missing');
    test('Environment Setting', defined('ENV') && ENV === 'production', 'Environment not set to production');
    
    // File System Tests
    test('Critical: Uploads Directory', is_dir('uploads') && is_writable('uploads'), 'Uploads directory not writable');
    test('Cache Directory', is_dir('cache') && is_writable('cache'), 'Cache directory not writable');
    test('Logs Directory', is_dir('logs') && is_writable('logs'), 'Logs directory not writable');
    
    // Core Files Test
    $coreFiles = [
        'index.php',
        'admin/login.php',
        'admin/dashboard.php',
        'public/articles.php',
        'public/photobooks.php',
        'includes/functions.php',
        'includes/auth.php'
    ];
    
    foreach ($coreFiles as $file) {
        test("Critical: Core File - $file", file_exists($file), "$file is missing");
    }
    
    // Asset Files Test
    $assetFiles = [
        'assets/css/public.css',
        'assets/css/admin.css',
        'assets/js/autosave.js',
        'assets/js/sorting.js'
    ];
    
    foreach ($assetFiles as $file) {
        test("Asset File - $file", file_exists($file), "$file is missing");
    }
    
    // Database Schema Test
    try {
        $requiredTables = ['content', 'content_versions', 'menus', 'users', 'sessions', 'settings'];
        foreach ($requiredTables as $table) {
            $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            test("Database Table - $table", (bool)$stmt->fetch(), "Table $table missing");
        }
    } catch (Exception $e) {
        test('Database Schema', false, 'Cannot check database schema: ' . $e->getMessage());
    }
    
    // Content Check
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM content WHERE status = 'published' AND deleted_at IS NULL");
        $contentCount = $stmt->fetchColumn();
        test('Published Content', $contentCount > 0, "No published content found (found: $contentCount)");
    } catch (Exception $e) {
        test('Published Content', false, 'Cannot check content: ' . $e->getMessage());
    }
    
    // Admin User Check
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 1");
        $adminCount = $stmt->fetchColumn();
        test('Admin User Exists', $adminCount > 0, "No admin users found");
    } catch (Exception $e) {
        test('Admin User Exists', false, 'Cannot check users: ' . $e->getMessage());
    }
    
    // Calculate overall status
    $percentage = $total > 0 ? round(($success / $total) * 100, 1) : 0;
    $overallStatus = 'success';
    $statusMessage = 'All systems operational';
    
    if (!empty($criticalIssues)) {
        $overallStatus = 'critical';
        $statusMessage = 'Critical issues found - immediate attention required';
    } elseif (!empty($warnings)) {
        $overallStatus = 'warning';
        $statusMessage = 'Some issues found - should be addressed';
    }
    ?>

    <div class="status-banner <?php echo $overallStatus; ?>">
        <?php
        if ($overallStatus === 'success') echo '✅ ';
        elseif ($overallStatus === 'warning') echo '⚠️ ';
        else echo '🚨 ';
        ?>
        <?php echo $statusMessage; ?>
    </div>

    <div class="content">
        <div class="test-group">
            <h3>📊 Test Results</h3>
            <?php foreach ($results as $result): ?>
            <div class="test-item">
                <div class="test-name">
                    <?php echo htmlspecialchars($result['name']); ?>
                    <?php if ($result['status'] === 'FAIL' && $result['message']): ?>
                    <div style="font-size: 0.9em; color: #7f8c8d; margin-top: 5px;">
                        <?php echo htmlspecialchars($result['message']); ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="test-status <?php echo strtolower($result['status']); ?>">
                    <?php echo $result['status']; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="summary">
            <h3>📈 Summary Statistics</h3>
            <div class="summary-stats">
                <div class="stat">
                    <div class="stat-number"><?php echo $percentage; ?>%</div>
                    <div class="stat-label">Success Rate</div>
                </div>
                <div class="stat">
                    <div class="stat-number"><?php echo $success; ?></div>
                    <div class="stat-label">Tests Passed</div>
                </div>
                <div class="stat">
                    <div class="stat-number"><?php echo count($criticalIssues) + count($warnings); ?></div>
                    <div class="stat-label">Issues Found</div>
                </div>
                <div class="stat">
                    <div class="stat-number"><?php echo $total; ?></div>
                    <div class="stat-label">Total Tests</div>
                </div>
            </div>

            <?php if (!empty($criticalIssues)): ?>
            <div style="background: #e74c3c; color: white; padding: 15px; border-radius: 8px; margin: 20px 0;">
                <h4>🚨 Critical Issues:</h4>
                <ul style="text-align: left; margin-top: 10px;">
                    <?php foreach ($criticalIssues as $issue): ?>
                    <li><?php echo htmlspecialchars($issue); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if (!empty($warnings)): ?>
            <div style="background: #f39c12; color: white; padding: 15px; border-radius: 8px; margin: 20px 0;">
                <h4>⚠️ Warnings:</h4>
                <ul style="text-align: left; margin-top: 10px;">
                    <?php foreach ($warnings as $warning): ?>
                    <li><?php echo htmlspecialchars($warning); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>

        <div class="actions">
            <?php if ($overallStatus === 'success'): ?>
            <a href="https://dalthaus.net/" class="btn success">🏠 View Live Site</a>
            <a href="production-test-suite.php?token=test-<?php echo date('Ymd'); ?>" class="btn">🧪 Full Test Suite</a>
            <?php elseif ($overallStatus === 'warning'): ?>
            <a href="production-test-suite.php?token=test-<?php echo date('Ymd'); ?>" class="btn warning">🔍 Run Detailed Tests</a>
            <a href="feature-checklist.php?token=checklist-<?php echo date('Ymd'); ?>" class="btn">📋 Manual Testing</a>
            <?php else: ?>
            <a href="auto-deploy.php?action=maintenance_on&token=deploy-<?php echo date('Ymd'); ?>" class="btn danger">🛠️ Enable Maintenance Mode</a>
            <a href="production-test-suite.php?token=test-<?php echo date('Ymd'); ?>" class="btn">🔍 Detailed Diagnostics</a>
            <?php endif; ?>
            <a href="?token=<?php echo $_GET['token']; ?>" class="btn">🔄 Refresh</a>
        </div>

        <div class="timestamp">
            Validation completed: <?php echo date('Y-m-d H:i:s T'); ?>
        </div>
    </div>
</div>

</body>
</html>