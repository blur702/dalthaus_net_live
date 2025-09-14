<?php
// Fix config file structure
$configFile = __DIR__ . '/config/config.php';

// Load existing config
$config = require $configFile;

// Fix database array keys if needed
if (isset($config['database']['name'])) {
    $config['database']['dbname'] = $config['database']['name'];
    unset($config['database']['name']);
}

if (isset($config['database']['user'])) {
    $config['database']['username'] = $config['database']['user'];
    unset($config['database']['user']);
}

if (isset($config['database']['pass'])) {
    $config['database']['password'] = $config['database']['pass'];
    unset($config['database']['pass']);
}

// Add PDO options if missing
if (!isset($config['database']['options'])) {
    $config['database']['options'] = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
    ];
}

// Generate new config file
$configContent = "<?php\n\n";
$configContent .= "/**\n";
$configContent .= " * CMS Configuration\n";
$configContent .= " * Fixed by fix_config.php on " . date('Y-m-d H:i:s') . "\n";
$configContent .= " */\n\n";
$configContent .= "return " . var_export($config, true) . ";\n";

// Backup old config
copy($configFile, $configFile . '.backup.' . date('YmdHis'));

// Write new config
file_put_contents($configFile, $configContent);

echo "Config file fixed successfully!\n";
echo "Database config:\n";
echo "- dbname: " . $config['database']['dbname'] . "\n";
echo "- username: " . $config['database']['username'] . "\n";
echo "- host: " . $config['database']['host'] . "\n";
?>