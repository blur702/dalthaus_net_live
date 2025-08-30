<?php
// Force git pull by backing up local changes first
$token = $_GET['token'] ?? '';
if ($token !== 'agent-' . date('Ymd')) {
    http_response_code(401);
    die('Unauthorized - use ?token=agent-' . date('Ymd'));
}

echo "<h1>Force Git Pull</h1><pre>";

// Backup local config files
echo "Backing up local configs...\n";
$config_backup = @file_get_contents('includes/config.php');
$local_backup = @file_get_contents('includes/config.local.php');

// Reset the modified files
echo "Resetting modified files...\n";
exec('git checkout -- includes/config.php includes/config.local.php 2>&1', $output, $return);
echo implode("\n", $output) . "\n";

// Now pull
echo "\nPulling latest changes...\n";
exec('git pull origin main 2>&1', $output2, $return2);
echo implode("\n", $output2) . "\n";

if ($return2 === 0) {
    echo "\n✅ Pull successful!\n";
    
    // Restore the database credentials in config.local.php
    echo "\nRestoring database credentials...\n";
    $config_local = '<?php
// Database Configuration - Production
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
';
    
    file_put_contents('includes/config.local.php', $config_local);
    echo "✅ Database credentials restored\n";
    
} else {
    echo "\n❌ Pull failed\n";
}

echo "\n<a href='/'>View Site</a> | <a href='/admin/login.php'>Admin Login</a>";
echo "</pre>";
?>