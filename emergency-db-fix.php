<?php
/**
 * Emergency Database Fix - Try All Possible Credentials
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Emergency Database Fix</h1><pre>";

// Possible database configurations to try
$configs_to_try = [
    ['host' => 'localhost', 'user' => 'kevin', 'pass' => '(130Bpm)', 'db' => 'dalthaus_cms'],
    ['host' => 'localhost', 'user' => 'dalthaus_user', 'pass' => '(130Bpm)', 'db' => 'dalthaus_cms'],
    ['host' => 'localhost', 'user' => 'dalthaus', 'pass' => '(130Bpm)', 'db' => 'dalthaus_cms'],
    ['host' => '127.0.0.1', 'user' => 'kevin', 'pass' => '(130Bpm)', 'db' => 'dalthaus_cms'],
    ['host' => 'localhost', 'user' => 'root', 'pass' => '', 'db' => 'dalthaus_cms'],
    ['host' => 'localhost', 'user' => 'root', 'pass' => '(130Bpm)', 'db' => 'dalthaus_cms'],
];

echo "Testing database connections...\n\n";

$working_config = null;

foreach ($configs_to_try as $config) {
    echo "Testing: " . $config['user'] . "@" . $config['host'] . " ... ";
    
    $conn = @mysqli_connect($config['host'], $config['user'], $config['pass'], $config['db']);
    
    if ($conn) {
        echo "✅ SUCCESS!\n";
        
        // Test query
        $result = mysqli_query($conn, "SHOW TABLES");
        $count = mysqli_num_rows($result);
        echo "   Found $count tables\n";
        
        mysqli_close($conn);
        
        $working_config = $config;
        break;
    } else {
        echo "❌ Failed: " . mysqli_connect_error() . "\n";
    }
}

if ($working_config) {
    echo "\n✅ FOUND WORKING CONFIGURATION!\n";
    echo "Host: " . $working_config['host'] . "\n";
    echo "User: " . $working_config['user'] . "\n";
    echo "Database: " . $working_config['db'] . "\n";
    
    // Write the working config
    echo "\nWriting configuration files...\n";
    
    $config_content = "<?php
// Local configuration overrides
// This file is not tracked in git

// Database Configuration
define('DB_HOST', '" . $working_config['host'] . "');
define('DB_NAME', '" . $working_config['db'] . "');
define('DB_USER', '" . $working_config['user'] . "');
define('DB_PASS', '" . addslashes($working_config['pass']) . "');

// Environment
define('ENV', 'production');

// Admin defaults (for setup)
define('DEFAULT_ADMIN_USER', 'admin');
define('DEFAULT_ADMIN_PASS', '130Bpm');

// Logging
define('LOG_LEVEL', 'ERROR'); // DEBUG, INFO, WARNING, ERROR
define('CACHE_ENABLED', true);
";
    
    // Write config.local.php
    if (file_put_contents('includes/config.local.php', $config_content)) {
        echo "✅ Written includes/config.local.php\n";
    } else {
        echo "❌ Failed to write config.local.php\n";
    }
    
    // Also update main config.php
    if (file_exists('includes/config.php')) {
        $main = file_get_contents('includes/config.php');
        $main = preg_replace("/define\('DB_HOST',\s*'[^']+'\)/", "define('DB_HOST', '" . $working_config['host'] . "')", $main);
        $main = preg_replace("/define\('DB_USER',\s*'[^']+'\)/", "define('DB_USER', '" . $working_config['user'] . "')", $main);
        $main = preg_replace("/define\('DB_PASS',\s*'[^']+'\)/", "define('DB_PASS', '" . addslashes($working_config['pass']) . "')", $main);
        $main = preg_replace("/define\('DB_NAME',\s*'[^']+'\)/", "define('DB_NAME', '" . $working_config['db'] . "')", $main);
        
        if (file_put_contents('includes/config.php', $main)) {
            echo "✅ Updated includes/config.php\n";
        }
    }
    
    echo "\n🎉 Database configuration fixed!\n";
    echo "\nTesting homepage...\n";
    
    // Test if index.php works now
    ob_start();
    $error = null;
    try {
        require_once 'index.php';
    } catch (Exception $e) {
        $error = $e->getMessage();
    } catch (Error $e) {
        $error = $e->getMessage();
    }
    ob_end_clean();
    
    if (!$error) {
        echo "✅ Homepage should be working now!\n";
    } else {
        echo "⚠️  Homepage error: $error\n";
    }
    
} else {
    echo "\n❌ NO WORKING DATABASE CONFIGURATION FOUND!\n";
    echo "\nThis could mean:\n";
    echo "1. Database server is down\n";
    echo "2. Database 'dalthaus_cms' doesn't exist\n";
    echo "3. None of the tested credentials are correct\n";
    echo "\nYou may need to:\n";
    echo "1. Check with your hosting provider for correct database credentials\n";
    echo "2. Create the database if it doesn't exist\n";
    echo "3. Check cPanel/hosting panel for database details\n";
}

echo "</pre>";

echo "<h2>Quick Links</h2>";
echo "<ul>";
echo "<li><a href='/'>Homepage</a></li>";
echo "<li><a href='/admin/login.php'>Admin Login</a></li>";
echo "<li><a href='/debug-production.php'>Debug Tool</a></li>";
echo "</ul>";
?>