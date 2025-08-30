#!/usr/bin/env php
<?php
/**
 * EMERGENCY FIX #4: VERIFY ALL FIXES
 * Priority: P0 - VERIFICATION
 * Test that all emergency fixes are working
 */

echo "\n=== EMERGENCY FIX VERIFICATION ===\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

$issues = [];
$warnings = [];
$successes = [];

// Test 1: Database Connection
echo "1. Testing Database Connection...\n";
require_once __DIR__ . '/includes/config.php';

$testConn = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($testConn) {
    $successes[] = "✓ Database connection successful";
    echo "   ✓ Connected to database\n";
    
    // Check if tables exist
    $result = mysqli_query($testConn, "SHOW TABLES");
    $tableCount = mysqli_num_rows($result);
    echo "   ✓ Found $tableCount tables in database\n";
    
    // Check for admin user
    $adminCheck = mysqli_query($testConn, "SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
    if ($adminCheck) {
        $row = mysqli_fetch_assoc($adminCheck);
        if ($row['count'] > 0) {
            echo "   ✓ Admin user exists\n";
            $successes[] = "✓ Admin user found in database";
        } else {
            $warnings[] = "⚠ No admin user found - run setup.php locally to create one";
            echo "   ⚠ No admin user found\n";
        }
    }
    
    mysqli_close($testConn);
} else {
    $issues[] = "✗ DATABASE CONNECTION FAILED: " . mysqli_connect_error();
    echo "   ✗ Database connection failed\n";
}

// Test 2: .htaccess Security
echo "\n2. Checking .htaccess Security...\n";
$htaccess = file_get_contents(__DIR__ . '/.htaccess');

// Check setup.php protection
if (strpos($htaccess, '<Files "setup.php">') !== false && 
    strpos($htaccess, '# <Files "setup.php">') === false) {
    $successes[] = "✓ setup.php is protected in .htaccess";
    echo "   ✓ setup.php protection is enabled\n";
} else {
    $issues[] = "✗ setup.php is NOT protected - security risk!";
    echo "   ✗ setup.php protection is NOT enabled\n";
}

// Check HTTPS redirect
if (strpos($htaccess, 'RewriteCond %{HTTPS} !=on') !== false && 
    strpos($htaccess, '# RewriteCond %{HTTPS} !=on') === false) {
    $successes[] = "✓ HTTPS redirect is enabled";
    echo "   ✓ HTTPS redirect is enabled\n";
} else {
    $warnings[] = "⚠ HTTPS redirect is not enabled - consider enabling for production";
    echo "   ⚠ HTTPS redirect is not enabled\n";
}

// Check debug file protection
if (strpos($htaccess, 'Block access to debug, test, and deployment files') !== false) {
    $successes[] = "✓ Debug/test file protection is enabled";
    echo "   ✓ Debug/test file protection is enabled\n";
} else {
    $warnings[] = "⚠ Debug file protection rules not found";
    echo "   ⚠ Debug file protection rules not found\n";
}

// Test 3: Check for remaining debug files
echo "\n3. Checking for Remaining Debug Files...\n";
$debugPatterns = [
    'debug*.php',
    'test*.php',
    '*deploy*.php',
    'remote*.php',
    'EMERGENCY*.php', // Our emergency files
];

$remainingDebugFiles = [];
foreach ($debugPatterns as $pattern) {
    $files = glob(__DIR__ . '/' . $pattern);
    foreach ($files as $file) {
        $basename = basename($file);
        // Skip our emergency fix files and legitimate setup.php
        if (strpos($basename, 'EMERGENCY_FIX') === 0 || $basename === 'setup.php') {
            continue;
        }
        $remainingDebugFiles[] = $basename;
    }
}

if (empty($remainingDebugFiles)) {
    $successes[] = "✓ No debug/test files found in web root";
    echo "   ✓ No debug/test files found\n";
} else {
    $issues[] = "✗ Found " . count($remainingDebugFiles) . " debug/test files still present";
    echo "   ✗ Found debug/test files:\n";
    foreach ($remainingDebugFiles as $file) {
        echo "      - $file\n";
    }
}

// Test 4: Check file permissions
echo "\n4. Checking Critical File Permissions...\n";
$criticalFiles = [
    'includes/config.php' => '0644',
    '.htaccess' => '0644',
    'admin' => '0755',
    'includes' => '0755',
    'uploads' => '0755',
    'cache' => '0755',
];

foreach ($criticalFiles as $file => $expectedPerms) {
    $fullPath = __DIR__ . '/' . $file;
    if (file_exists($fullPath)) {
        $perms = substr(sprintf('%o', fileperms($fullPath)), -4);
        if ($perms === $expectedPerms) {
            echo "   ✓ $file has correct permissions ($perms)\n";
        } else {
            $warnings[] = "⚠ $file has permissions $perms (expected $expectedPerms)";
            echo "   ⚠ $file has permissions $perms (expected $expectedPerms)\n";
        }
    }
}

// Test 5: Check if site is accessible
echo "\n5. Testing Site Accessibility...\n";
// This would need to be done via HTTP request in production
// For now, check if index.php exists and is readable
if (file_exists(__DIR__ . '/index.php') && is_readable(__DIR__ . '/index.php')) {
    $successes[] = "✓ index.php exists and is readable";
    echo "   ✓ index.php exists and is readable\n";
} else {
    $issues[] = "✗ index.php is missing or not readable";
    echo "   ✗ index.php is missing or not readable\n";
}

// Test 6: Check environment settings
echo "\n6. Checking Environment Settings...\n";
if (ENV === 'production') {
    $successes[] = "✓ Environment is set to production";
    echo "   ✓ Environment is set to production\n";
} else {
    $warnings[] = "⚠ Environment is set to '" . ENV . "' (should be 'production')";
    echo "   ⚠ Environment is set to '" . ENV . "'\n";
}

// Generate Summary Report
echo "\n" . str_repeat("=", 60) . "\n";
echo "EMERGENCY FIX VERIFICATION REPORT\n";
echo str_repeat("=", 60) . "\n\n";

if (!empty($issues)) {
    echo "CRITICAL ISSUES (" . count($issues) . "):\n";
    echo str_repeat("-", 40) . "\n";
    foreach ($issues as $issue) {
        echo "$issue\n";
    }
    echo "\n";
}

if (!empty($warnings)) {
    echo "WARNINGS (" . count($warnings) . "):\n";
    echo str_repeat("-", 40) . "\n";
    foreach ($warnings as $warning) {
        echo "$warning\n";
    }
    echo "\n";
}

if (!empty($successes)) {
    echo "SUCCESSFUL FIXES (" . count($successes) . "):\n";
    echo str_repeat("-", 40) . "\n";
    foreach ($successes as $success) {
        echo "$success\n";
    }
    echo "\n";
}

// Final Status
echo str_repeat("=", 60) . "\n";
if (empty($issues)) {
    echo "STATUS: ✓ ALL CRITICAL ISSUES RESOLVED\n";
    echo "\nThe site should now be operational and secure.\n";
    echo "\nREMAINING ACTIONS:\n";
    echo "1. Test the site in a browser: https://dalthaus.net\n";
    echo "2. Verify admin login works: https://dalthaus.net/admin/\n";
    echo "3. Delete emergency fix files after confirmation:\n";
    echo "   rm EMERGENCY_FIX_*.php\n";
    echo "4. Delete backup directories if everything works:\n";
    echo "   rm -rf backup_debug_files_*\n";
    if (!empty($warnings)) {
        echo "5. Address the warnings above when possible\n";
    }
} else {
    echo "STATUS: ✗ CRITICAL ISSUES REMAIN\n";
    echo "\nThe site may not be fully operational.\n";
    echo "Review the critical issues above and address them immediately.\n";
    echo "\nIf database connection fails:\n";
    echo "1. Verify the actual database credentials on the server\n";
    echo "2. Update /includes/config.php with correct DB_USER and DB_PASS\n";
    echo "3. Ensure the database 'dalthaus_cms' exists\n";
}

echo str_repeat("=", 60) . "\n\n";