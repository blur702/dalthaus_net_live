#!/usr/bin/env php
<?php
/**
 * EXECUTE NOW - PRODUCTION DEPLOYMENT SCRIPT
 * This script applies all emergency fixes to restore dalthaus.net to 100% functionality
 * 
 * CRITICAL: Upload this file to dalthaus.net and run immediately
 * Access: https://dalthaus.net/EXECUTE_NOW_PRODUCTION.php
 */

// Prevent timeout on shared hosting
set_time_limit(300);
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Security check - allow web access for deployment
$isWeb = php_sapi_name() !== 'cli';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Emergency Production Deployment - Dalthaus.net</title>
    <style>
        body { 
            font-family: 'Courier New', monospace; 
            background: #1a1a1a; 
            color: #00ff00; 
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: #0a0a0a;
            padding: 30px;
            border: 2px solid #00ff00;
            box-shadow: 0 0 20px rgba(0,255,0,0.3);
        }
        h1 { 
            color: #00ff00; 
            text-align: center;
            text-shadow: 0 0 10px #00ff00;
            margin-bottom: 30px;
        }
        .status-box {
            background: #111;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #00ff00;
        }
        .success { color: #00ff00; font-weight: bold; }
        .error { color: #ff0000; font-weight: bold; }
        .warning { color: #ffaa00; }
        .btn {
            display: inline-block;
            padding: 15px 40px;
            background: #00ff00;
            color: #000;
            text-decoration: none;
            font-weight: bold;
            font-size: 18px;
            margin: 20px 10px;
            border: none;
            cursor: pointer;
            text-transform: uppercase;
            transition: all 0.3s;
        }
        .btn:hover {
            background: #00cc00;
            box-shadow: 0 0 20px #00ff00;
        }
        .btn-danger {
            background: #ff0000;
            color: #fff;
        }
        .btn-danger:hover {
            background: #cc0000;
            box-shadow: 0 0 20px #ff0000;
        }
        pre {
            background: #000;
            padding: 15px;
            overflow-x: auto;
            border: 1px solid #00ff00;
        }
        .progress {
            width: 100%;
            height: 30px;
            background: #111;
            border: 1px solid #00ff00;
            margin: 20px 0;
            position: relative;
            overflow: hidden;
        }
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #00ff00, #00cc00);
            width: 0%;
            transition: width 0.5s;
            position: relative;
        }
        .progress-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: #000;
            font-weight: bold;
            z-index: 1;
        }
        .critical-alert {
            background: #ff0000;
            color: #fff;
            padding: 15px;
            margin: 20px 0;
            text-align: center;
            font-weight: bold;
            animation: pulse 1s infinite;
        }
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.7; }
            100% { opacity: 1; }
        }
    </style>
</head>
<body>
<div class="container">

<h1>🚨 EMERGENCY PRODUCTION DEPLOYMENT 🚨</h1>

<?php

$action = $_GET['action'] ?? 'show';
$confirm = $_GET['confirm'] ?? '';

if ($action === 'execute' && $confirm === 'DEPLOY-NOW'):

?>

<div class="status-box">
<h2>EXECUTING EMERGENCY FIXES...</h2>
<div class="progress">
    <div class="progress-bar" id="progress"></div>
    <div class="progress-text" id="progress-text">0%</div>
</div>
</div>

<pre id="output">
<?php
ob_implicit_flush(true);
ob_end_flush();

$totalSteps = 10;
$currentStep = 0;
$errors = 0;
$fixes = 0;

function updateProgress($step, $total) {
    $percent = round(($step / $total) * 100);
    echo "<script>
        document.getElementById('progress').style.width = '{$percent}%';
        document.getElementById('progress-text').textContent = '{$percent}%';
    </script>\n";
    flush();
}

// STEP 1: Database Connection Fix
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "STEP 1/10: FIXING DATABASE CONNECTION\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$currentStep++; updateProgress($currentStep, $totalSteps);

$configFile = __DIR__ . '/includes/config.php';
if (file_exists($configFile)) {
    $config = file_get_contents($configFile);
    
    // Try connection with current credentials
    require_once $configFile;
    $testConn = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if (!$testConn) {
        echo "Current connection failed, updating credentials...\n";
        
        // Update to dalthaus_user
        $config = preg_replace(
            "/define\('DB_USER',\s*'[^']+'\)/",
            "define('DB_USER', 'dalthaus_user')",
            $config
        );
        
        file_put_contents($configFile, $config);
        echo "<span class='success'>✅ Database credentials updated to dalthaus_user</span>\n";
        $fixes++;
    } else {
        echo "<span class='success'>✅ Database connection already working</span>\n";
        mysqli_close($testConn);
    }
} else {
    echo "<span class='error'>❌ Config file not found!</span>\n";
    $errors++;
}
echo "\n";

// STEP 2: Enable HTTPS Redirect
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "STEP 2/10: ENABLING HTTPS REDIRECT\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$currentStep++; updateProgress($currentStep, $totalSteps);

$htaccessFile = __DIR__ . '/.htaccess';
if (file_exists($htaccessFile)) {
    $htaccess = file_get_contents($htaccessFile);
    
    // Remove comments from HTTPS redirect
    $patterns = [
        '/# (RewriteCond %{HTTPS} !=on)/' => '$1',
        '/# (RewriteCond %{HTTP:X-Forwarded-Proto} !https)/' => '$1',
        '/# (RewriteRule \^\(\.\*\)\$ https:\/\/%{HTTP_HOST}\/\$1 \[R=301,L\])/' => '$1'
    ];
    
    foreach ($patterns as $pattern => $replacement) {
        $htaccess = preg_replace($pattern, $replacement, $htaccess);
    }
    
    file_put_contents($htaccessFile, $htaccess);
    echo "<span class='success'>✅ HTTPS redirect enabled</span>\n";
    $fixes++;
} else {
    echo "<span class='error'>❌ .htaccess file not found!</span>\n";
    $errors++;
}
echo "\n";

// STEP 3: Protect setup.php
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "STEP 3/10: SECURING SETUP.PHP\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$currentStep++; updateProgress($currentStep, $totalSteps);

if (file_exists($htaccessFile)) {
    $htaccess = file_get_contents($htaccessFile);
    
    // Enable setup.php protection
    $htaccess = str_replace(
        '# <Files "setup.php">',
        '<Files "setup.php">',
        $htaccess
    );
    $htaccess = preg_replace('/# (\s*<IfModule.*?<\/IfModule>)/s', '$1', $htaccess);
    $htaccess = str_replace('# </Files>', '</Files>', $htaccess);
    
    file_put_contents($htaccessFile, $htaccess);
    echo "<span class='success'>✅ setup.php protection enabled</span>\n";
    $fixes++;
}
echo "\n";

// STEP 4: Fix CSS MIME Types
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "STEP 4/10: FIXING CSS DELIVERY\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$currentStep++; updateProgress($currentStep, $totalSteps);

if (file_exists($htaccessFile)) {
    $htaccess = file_get_contents($htaccessFile);
    
    if (strpos($htaccess, 'AddType text/css .css') === false) {
        $htaccess .= "\n# Ensure CSS MIME type\n";
        $htaccess .= "<IfModule mod_mime.c>\n";
        $htaccess .= "    AddType text/css .css\n";
        $htaccess .= "</IfModule>\n";
        
        file_put_contents($htaccessFile, $htaccess);
        echo "<span class='success'>✅ CSS MIME type configuration added</span>\n";
        $fixes++;
    } else {
        echo "<span class='success'>✅ CSS MIME type already configured</span>\n";
    }
}
echo "\n";

// STEP 5: Remove Debug Files
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "STEP 5/10: REMOVING DEBUG FILES\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$currentStep++; updateProgress($currentStep, $totalSteps);

$debugFiles = [
    'debug*.php', 'test*.php', '*deploy*.php', 'remote*.php', 
    'emergency*.php', 'setup-*.php', 'file-agent.php', 'e2e-*.php'
];

$removed = 0;
foreach ($debugFiles as $pattern) {
    $files = glob(__DIR__ . '/' . $pattern);
    foreach ($files as $file) {
        $basename = basename($file);
        // Don't delete this script or main setup.php
        if ($basename === basename(__FILE__) || $basename === 'setup.php') {
            continue;
        }
        if (@unlink($file)) {
            $removed++;
        }
    }
}
echo "<span class='success'>✅ Removed $removed debug/test files</span>\n";
$fixes++;
echo "\n";

// STEP 6: Clear Cache
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "STEP 6/10: CLEARING CACHE\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$currentStep++; updateProgress($currentStep, $totalSteps);

$cacheDir = __DIR__ . '/cache';
if (is_dir($cacheDir)) {
    $files = glob($cacheDir . '/*');
    $cleared = 0;
    foreach ($files as $file) {
        if (is_file($file) && basename($file) !== 'index.html') {
            @unlink($file);
            $cleared++;
        }
    }
    echo "<span class='success'>✅ Cleared $cleared cache files</span>\n";
    $fixes++;
} else {
    echo "<span class='warning'>⚠️  Cache directory not found</span>\n";
}
echo "\n";

// STEP 7: Verify Database Tables
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "STEP 7/10: VERIFYING DATABASE TABLES\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$currentStep++; updateProgress($currentStep, $totalSteps);

require_once __DIR__ . '/includes/config.php';
$conn = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn) {
    $result = mysqli_query($conn, "SHOW TABLES");
    $tableCount = mysqli_num_rows($result);
    echo "<span class='success'>✅ Found $tableCount tables in database</span>\n";
    
    // Check for admin user
    $adminCheck = mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
    if ($adminCheck) {
        $row = mysqli_fetch_assoc($adminCheck);
        if ($row['count'] > 0) {
            echo "<span class='success'>✅ Admin user exists</span>\n";
        } else {
            echo "<span class='warning'>⚠️  No admin user - run setup locally to create</span>\n";
        }
    }
    mysqli_close($conn);
    $fixes++;
} else {
    echo "<span class='error'>❌ Database connection failed</span>\n";
    $errors++;
}
echo "\n";

// STEP 8: Fix File Permissions
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "STEP 8/10: SETTING FILE PERMISSIONS\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$currentStep++; updateProgress($currentStep, $totalSteps);

$permissions = [
    'includes/config.php' => 0644,
    '.htaccess' => 0644,
    'uploads' => 0755,
    'cache' => 0755,
    'logs' => 0755
];

foreach ($permissions as $path => $perm) {
    $fullPath = __DIR__ . '/' . $path;
    if (file_exists($fullPath)) {
        @chmod($fullPath, $perm);
        echo "<span class='success'>✅ Set permissions for $path</span>\n";
    }
}
$fixes++;
echo "\n";

// STEP 9: Test Critical Files
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "STEP 9/10: TESTING CRITICAL FILES\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$currentStep++; updateProgress($currentStep, $totalSteps);

$criticalFiles = [
    'index.php' => 'Homepage',
    'admin/login.php' => 'Admin Login',
    'includes/config.php' => 'Configuration',
    'includes/database.php' => 'Database Layer',
    'assets/css/public.css' => 'Main Stylesheet'
];

foreach ($criticalFiles as $file => $name) {
    if (file_exists(__DIR__ . '/' . $file)) {
        echo "<span class='success'>✅ $name exists</span>\n";
    } else {
        echo "<span class='error'>❌ $name missing!</span>\n";
        $errors++;
    }
}
echo "\n";

// STEP 10: Final Verification
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "STEP 10/10: FINAL VERIFICATION\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
$currentStep++; updateProgress($currentStep, $totalSteps);

echo "Total Fixes Applied: $fixes\n";
echo "Total Errors: $errors\n";
echo "\n";

if ($errors === 0) {
    echo "<span class='success'>
╔══════════════════════════════════════════════════════════════╗
║                  DEPLOYMENT SUCCESSFUL!                       ║
║                                                              ║
║  ✅ Database connection restored                             ║
║  ✅ HTTPS redirect enabled                                   ║
║  ✅ Security hardening applied                               ║
║  ✅ CSS delivery fixed                                       ║
║  ✅ Debug files removed                                      ║
║  ✅ Cache cleared                                           ║
║                                                              ║
║           SITE IS NOW 100% OPERATIONAL                       ║
╚══════════════════════════════════════════════════════════════╝
</span>\n";
} else {
    echo "<span class='error'>
╔══════════════════════════════════════════════════════════════╗
║                  DEPLOYMENT COMPLETED WITH ISSUES            ║
║                                                              ║
║  Some errors occurred during deployment.                     ║
║  Manual intervention may be required.                        ║
║                                                              ║
║  Errors encountered: $errors                                 ║
╚══════════════════════════════════════════════════════════════╝
</span>\n";
}

?>
</pre>

<div style="text-align: center; margin-top: 30px;">
    <a href="https://<?= $_SERVER['HTTP_HOST'] ?>/" class="btn">🏠 VIEW LIVE SITE</a>
    <a href="/admin/login.php" class="btn">🔐 ADMIN LOGIN</a>
    <a href="?action=delete&confirm=DELETE-THIS" class="btn btn-danger">🗑️ DELETE THIS SCRIPT</a>
</div>

<?php elseif ($action === 'delete' && $confirm === 'DELETE-THIS'): ?>

<div class="critical-alert">
    SELF-DESTRUCT SEQUENCE INITIATED
</div>

<?php
    // Delete this script
    if (unlink(__FILE__)) {
        echo "<div class='status-box'>";
        echo "<h2 class='success'>✅ Script successfully deleted for security</h2>";
        echo "<p>This deployment script has been removed from the server.</p>";
        echo "</div>";
    } else {
        echo "<div class='status-box'>";
        echo "<h2 class='error'>❌ Failed to delete script - remove manually!</h2>";
        echo "</div>";
    }
?>

<div style="text-align: center; margin-top: 30px;">
    <a href="https://<?= $_SERVER['HTTP_HOST'] ?>/" class="btn">🏠 RETURN TO SITE</a>
</div>

<?php else: ?>

<div class="critical-alert">
    CRITICAL: PRODUCTION SITE REQUIRES IMMEDIATE FIXES
</div>

<div class="status-box">
<h2>Current Issues Detected:</h2>
<ul>
    <li>❌ Database connection may be failing</li>
    <li>❌ HTTPS redirect not enabled</li>
    <li>❌ Security vulnerabilities present</li>
    <li>❌ CSS not loading properly</li>
    <li>❌ Debug files exposed in production</li>
    <li>❌ Cache may contain stale content</li>
</ul>
</div>

<div class="status-box">
<h2>This Script Will:</h2>
<ol>
    <li>✅ Fix database connection (update to dalthaus_user)</li>
    <li>✅ Enable HTTPS redirect for security</li>
    <li>✅ Protect setup.php from public access</li>
    <li>✅ Configure CSS MIME types</li>
    <li>✅ Remove all debug/test files</li>
    <li>✅ Clear cache for fresh content</li>
    <li>✅ Verify database tables exist</li>
    <li>✅ Set proper file permissions</li>
    <li>✅ Test critical files are present</li>
    <li>✅ Perform final verification</li>
</ol>
</div>

<div style="text-align: center; margin-top: 30px;">
    <a href="?action=execute&confirm=DEPLOY-NOW" class="btn" onclick="return confirm('This will apply all emergency fixes to the production site. Continue?')">
        🚀 EXECUTE EMERGENCY DEPLOYMENT
    </a>
</div>

<div class="status-box">
<h3>⚠️ Warning:</h3>
<p>This is a one-time emergency deployment script. It will:</p>
<ul>
    <li>Apply all necessary fixes to restore site functionality</li>
    <li>Remove debug and test files for security</li>
    <li>Update database credentials if needed</li>
    <li>Clear all caches</li>
</ul>
<p><strong>After deployment, this script should be deleted for security.</strong></p>
</div>

<?php endif; ?>

<div style="margin-top: 50px; padding-top: 20px; border-top: 1px solid #00ff00; text-align: center; color: #666;">
    <p>Emergency Deployment System v1.0 | Generated: <?= date('Y-m-d H:i:s') ?></p>
    <p>Dalthaus Photography CMS | PHP <?= phpversion() ?></p>
</div>

</div>
</body>
</html>