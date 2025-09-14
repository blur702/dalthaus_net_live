<?php
// Complete config fix - adds all missing configuration keys
header('Content-Type: text/plain');

$configFile = __DIR__ . '/config/config.php';

// Load existing config
$config = require $configFile;

echo "Fixing configuration...\n\n";

// Add missing routing config
if (!isset($config['routing'])) {
    $config['routing'] = [
        'default_controller' => 'Home',
        'default_action' => 'index',
        'admin_prefix' => 'admin',
        'url_suffix' => '',
        'case_sensitive' => false
    ];
    echo "✓ Added routing configuration\n";
}

// Fix session config structure (it uses 'security' for session settings in index.php)
if (!isset($config['security']['session_lifetime'])) {
    $config['security']['session_lifetime'] = 3600; // 1 hour
    echo "✓ Added session_lifetime\n";
}

if (!isset($config['security']['session_name'])) {
    $config['security']['session_name'] = 'cms_session';
    echo "✓ Added session_name\n";
}

if (!isset($config['security']['secure_cookies'])) {
    $config['security']['secure_cookies'] = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    echo "✓ Added secure_cookies\n";
}

if (!isset($config['security']['cookie_httponly'])) {
    $config['security']['cookie_httponly'] = true;
    echo "✓ Added cookie_httponly\n";
}

if (!isset($config['security']['cookie_samesite'])) {
    $config['security']['cookie_samesite'] = 'Strict';
    echo "✓ Added cookie_samesite\n";
}

// Add missing view config
if (!isset($config['views'])) {
    $config['views'] = [
        'cache_enabled' => false,
        'cache_path' => __DIR__ . '/../cache/views/',
        'default_layout' => 'default',
        'admin_layout' => 'admin'
    ];
    echo "✓ Added views configuration\n";
}

// Add missing error config
if (!isset($config['errors'])) {
    $config['errors'] = [
        'display_errors' => false,
        'log_errors' => true,
        'error_log_path' => __DIR__ . '/../logs/error.log',
        'exception_handler' => true
    ];
    echo "✓ Added errors configuration\n";
}

// Generate new config file
$configContent = "<?php\n\n";
$configContent .= "/**\n";
$configContent .= " * CMS Configuration\n";
$configContent .= " * Fixed by fix_config_complete.php on " . date('Y-m-d H:i:s') . "\n";
$configContent .= " */\n\n";
$configContent .= "return " . var_export($config, true) . ";\n";

// Backup old config
$backupFile = $configFile . '.backup.' . date('YmdHis');
copy($configFile, $backupFile);
echo "\n✓ Backup created: " . basename($backupFile) . "\n";

// Write new config
file_put_contents($configFile, $configContent);
echo "✓ Config file updated\n\n";

// Verify
echo "Verification:\n";
echo "- Database: " . $config['database']['dbname'] . "\n";
echo "- Routing: " . ($config['routing']['default_controller'] ?? 'NOT SET') . "\n";
echo "- Session: " . ($config['security']['session_name'] ?? 'NOT SET') . "\n";

echo "\n✅ Configuration completely fixed! The site should now work.\n";
?>