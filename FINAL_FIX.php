<?php
/**
 * Final Fix - Uses discovered credentials and fixes PHP issues
 */

// Prevent any existing configs from loading
define('CONFIG_LOADED', true);

// Set the correct database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'dalthaus_photocms');
define('DB_USER', 'dalthaus_photocms');
define('DB_PASS', 'f-I*GSo^Urt*k*&#');
define('ENV', 'production');
define('DEFAULT_ADMIN_USER', 'admin');
define('DEFAULT_ADMIN_PASS', '130Bpm');
define('LOG_LEVEL', 'ERROR');
define('CACHE_ENABLED', true);

echo "<h1>Final Fix - Using Discovered Credentials</h1><pre>";

// Test the connection
echo "Testing database connection...\n";
$conn = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn) {
    echo "✅ Database connection successful!\n";
    
    // Check tables
    $result = mysqli_query($conn, "SHOW TABLES");
    $count = mysqli_num_rows($result);
    echo "✅ Found $count tables\n\n";
    
    mysqli_close($conn);
    
    // Write a clean config.local.php that prevents double-loading
    echo "Writing clean configuration...\n";
    
    $config = '<?php
// Prevent double-loading
if (defined("CONFIG_LOADED")) return;
define("CONFIG_LOADED", true);

// Database Configuration - VERIFIED WORKING
define("DB_HOST", "localhost");
define("DB_NAME", "dalthaus_photocms");
define("DB_USER", "dalthaus_photocms");
define("DB_PASS", "f-I*GSo^Urt*k*&#");

// Environment
define("ENV", "production");

// Admin defaults
define("DEFAULT_ADMIN_USER", "admin");
define("DEFAULT_ADMIN_PASS", "130Bpm");

// Settings
define("LOG_LEVEL", "ERROR");
define("CACHE_ENABLED", true);

// Prevent session warnings
@ini_set("session.use_cookies", 1);
@ini_set("session.use_only_cookies", 1);
';
    
    if (file_put_contents('includes/config.local.php', $config)) {
        echo "✅ Written includes/config.local.php\n";
    }
    
    // Also fix the main config.php to prevent double-defines
    $main_config = @file_get_contents('includes/config.php');
    if ($main_config && strpos($main_config, 'CONFIG_LOADED') === false) {
        // Add guard at the top
        $main_config = '<?php
// Prevent double-loading
if (defined("CONFIG_LOADED")) return;
define("CONFIG_LOADED", true);
' . substr($main_config, 5); // Remove <?php
        
        file_put_contents('includes/config.php', $main_config);
        echo "✅ Updated includes/config.php with guard\n";
    }
    
    // Clear any cache
    $cache_files = glob('cache/*.cache');
    foreach ($cache_files as $file) {
        @unlink($file);
    }
    echo "✅ Cleared cache\n";
    
    // Create a simple test index
    $test_index = '<?php
require_once "includes/config.local.php";
require_once "includes/database.php";
require_once "includes/functions.php";

// Test database
$db = Database::getInstance();
$pdo = $db->getConnection();

if ($pdo) {
    echo "<h1>Database Connected!</h1>";
    echo "<p>The site is working. <a href=\"/admin/login.php\">Admin Login</a></p>";
} else {
    echo "<h1>Database Error</h1>";
}
';
    
    file_put_contents('test-final.php', $test_index);
    echo "\n✅ Created test-final.php\n";
    
    echo "\n🎉 SITE SHOULD BE FIXED!\n\n";
    echo "Test these:\n";
    echo "- <a href='/test-final.php'>Test Page</a>\n";
    echo "- <a href='/'>Homepage</a>\n";
    echo "- <a href='/admin/login.php'>Admin Login</a>\n";
    
} else {
    echo "❌ Database connection failed\n";
    echo "Error: " . mysqli_connect_error() . "\n";
}

echo "</pre>";
?>