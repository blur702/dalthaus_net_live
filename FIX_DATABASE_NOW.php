<?php
/**
 * IMMEDIATE DATABASE FIX
 * Fixes the config.local.php issue and database credentials
 */

// Prevent any output before we start
ob_start();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Emergency Database Fix</title>
    <style>
        body { font-family: monospace; background: #000; color: #0f0; padding: 20px; }
        pre { background: #111; padding: 20px; border: 1px solid #0f0; }
        .success { color: #0f0; }
        .error { color: #f00; }
        .warning { color: #fa0; }
        .btn { display: inline-block; padding: 15px 30px; background: #0f0; color: #000; text-decoration: none; margin: 10px; font-weight: bold; }
    </style>
</head>
<body>
<h1>🔧 EMERGENCY DATABASE FIX</h1>

<?php
$action = $_GET['action'] ?? 'show';

if ($action === 'fix'):
?>
<pre>
FIXING DATABASE CONFIGURATION...
================================================

<?php
// Clear any previous output
ob_clean();
flush();

$fixes = 0;
$errors = 0;

// Step 1: Check what config files exist
echo "1. Checking configuration files...\n";
$configFile = __DIR__ . '/includes/config.php';
$configLocalFile = __DIR__ . '/includes/config.local.php';

if (file_exists($configLocalFile)) {
    echo "   Found config.local.php - this is overriding main config!\n";
    
    // Read the local config to get current values
    $localConfig = file_get_contents($configLocalFile);
    
    // Check what user is defined
    if (preg_match("/define\('DB_USER',\s*'([^']+)'\)/", $localConfig, $matches)) {
        $currentUser = $matches[1];
        echo "   Current DB_USER in config.local.php: $currentUser\n";
    }
    
    // Try to connect with kevin user
    echo "\n2. Testing database connections...\n";
    
    $testUsers = [
        'kevin' => '(130Bpm)',
        'dalthaus_user' => '(130Bpm)',
        'dalthaus' => '(130Bpm)'
    ];
    
    $workingUser = null;
    $workingPass = null;
    
    foreach ($testUsers as $user => $pass) {
        echo "   Testing user: $user ... ";
        $conn = @mysqli_connect('localhost', $user, $pass, 'dalthaus_cms');
        if ($conn) {
            echo "<span class='success'>✅ SUCCESS!</span>\n";
            $workingUser = $user;
            $workingPass = $pass;
            mysqli_close($conn);
            break;
        } else {
            echo "<span class='error'>❌ Failed</span>\n";
        }
    }
    
    if ($workingUser) {
        echo "\n3. Updating config.local.php with working credentials...\n";
        
        // Update the local config with working credentials
        $localConfig = preg_replace(
            "/define\('DB_USER',\s*'[^']+'\)/",
            "define('DB_USER', '$workingUser')",
            $localConfig
        );
        
        $localConfig = preg_replace(
            "/define\('DB_PASS',\s*'[^']+'\)/",
            "define('DB_PASS', '$workingPass')",
            $localConfig
        );
        
        // Make sure DB_HOST is localhost
        $localConfig = preg_replace(
            "/define\('DB_HOST',\s*'[^']+'\)/",
            "define('DB_HOST', 'localhost')",
            $localConfig
        );
        
        file_put_contents($configLocalFile, $localConfig);
        echo "   <span class='success'>✅ Updated config.local.php with user: $workingUser</span>\n";
        $fixes++;
        
        // Also update main config.php as backup
        echo "\n4. Updating main config.php as backup...\n";
        if (file_exists($configFile)) {
            $mainConfig = file_get_contents($configFile);
            
            $mainConfig = preg_replace(
                "/define\('DB_USER',\s*'[^']+'\)/",
                "define('DB_USER', '$workingUser')",
                $mainConfig
            );
            
            $mainConfig = preg_replace(
                "/define\('DB_PASS',\s*'[^']+'\)/",
                "define('DB_PASS', '$workingPass')",
                $mainConfig
            );
            
            $mainConfig = preg_replace(
                "/define\('DB_HOST',\s*'[^']+'\)/",
                "define('DB_HOST', 'localhost')",
                $mainConfig
            );
            
            file_put_contents($configFile, $mainConfig);
            echo "   <span class='success'>✅ Updated main config.php</span>\n";
            $fixes++;
        }
        
        // Test final connection
        echo "\n5. Verifying final connection...\n";
        require_once $configLocalFile;
        $finalConn = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($finalConn) {
            echo "   <span class='success'>✅ Database connection WORKING!</span>\n";
            
            // Check tables
            $result = mysqli_query($finalConn, "SHOW TABLES");
            $tableCount = mysqli_num_rows($result);
            echo "   <span class='success'>✅ Found $tableCount tables in database</span>\n";
            
            mysqli_close($finalConn);
        } else {
            echo "   <span class='error'>❌ Connection still failing!</span>\n";
            $errors++;
        }
        
    } else {
        echo "\n<span class='error'>❌ Could not find working database credentials!</span>\n";
        echo "Manual intervention required. Try these credentials:\n";
        echo "   Username: kevin\n";
        echo "   Password: (130Bpm)\n";
        echo "   Database: dalthaus_cms\n";
        $errors++;
    }
    
} else {
    echo "   <span class='warning'>⚠️ No config.local.php found</span>\n";
    
    // Just update main config
    if (file_exists($configFile)) {
        echo "\n2. Updating main config.php...\n";
        $mainConfig = file_get_contents($configFile);
        
        // Test connection first
        $conn = @mysqli_connect('localhost', 'kevin', '(130Bpm)', 'dalthaus_cms');
        if ($conn) {
            mysqli_close($conn);
            
            $mainConfig = preg_replace(
                "/define\('DB_USER',\s*'[^']+'\)/",
                "define('DB_USER', 'kevin')",
                $mainConfig
            );
            
            $mainConfig = preg_replace(
                "/define\('DB_HOST',\s*'[^']+'\)/",
                "define('DB_HOST', 'localhost')",
                $mainConfig
            );
            
            file_put_contents($configFile, $mainConfig);
            echo "   <span class='success'>✅ Updated to use 'kevin' user</span>\n";
            $fixes++;
        }
    }
}

echo "\n================================================\n";
echo "RESULTS:\n";
echo "Fixes Applied: $fixes\n";
echo "Errors: $errors\n";

if ($errors === 0) {
    echo "\n<span class='success'>✅ DATABASE CONFIGURATION FIXED!</span>\n";
    echo "\nThe site should now have working database connection.\n";
} else {
    echo "\n<span class='error'>⚠️ Some issues remain. Check database user permissions.</span>\n";
}
?>
</pre>

<div style="text-align: center; margin-top: 30px;">
    <a href="/" class="btn">🏠 View Site</a>
    <a href="/admin/login.php" class="btn">🔐 Admin Login</a>
    <a href="/EXECUTE_NOW_PRODUCTION.php" class="btn">🚀 Run Full Deployment</a>
</div>

<?php else: ?>

<pre>
This script will fix the database configuration issue.

Problem detected:
- config.local.php is overriding main config
- Database user 'dalthaus_user' doesn't exist
- Need to find and use correct credentials

This script will:
1. ✅ Check for config.local.php
2. ✅ Test different database users
3. ✅ Update config with working credentials
4. ✅ Verify database connection
5. ✅ Fix both config.php and config.local.php
</pre>

<div style="text-align: center; margin-top: 30px;">
    <a href="?action=fix" class="btn">🔧 FIX DATABASE NOW</a>
</div>

<?php endif; ?>

</body>
</html>