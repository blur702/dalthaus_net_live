<?php
// Emergency config fix - can be run via web
header('Content-Type: text/plain');

$configFile = __DIR__ . '/config/config.php';

if (!file_exists($configFile)) {
    die("Config file not found!");
}

// Read the config file as text
$configContent = file_get_contents($configFile);

// Fix the database array keys using string replacement
$configContent = str_replace("'name' =>", "'dbname' =>", $configContent);
$configContent = str_replace("'user' =>", "'username' =>", $configContent);
$configContent = str_replace("'pass' =>", "'password' =>", $configContent);

// Add PDO options if not present
if (strpos($configContent, "'options'") === false) {
    // Find the charset line and add options after it
    $configContent = str_replace(
        "'charset' => 'utf8mb4',\n  ),",
        "'charset' => 'utf8mb4',\n    'options' => \n    array (\n      " . PDO::ATTR_ERRMODE . " => " . PDO::ERRMODE_EXCEPTION . ",\n      " . PDO::ATTR_DEFAULT_FETCH_MODE . " => " . PDO::FETCH_ASSOC . ",\n      " . PDO::ATTR_EMULATE_PREPARES . " => false,\n    ),\n  ),",
        $configContent
    );
}

// Backup the old config
$backupFile = $configFile . '.backup.' . date('YmdHis');
copy($configFile, $backupFile);
echo "Backup created: " . basename($backupFile) . "\n";

// Write the fixed config
file_put_contents($configFile, $configContent);
echo "Config file fixed!\n\n";

// Verify the fix
$config = require $configFile;
echo "Verification:\n";
echo "- dbname: " . ($config['database']['dbname'] ?? 'NOT FOUND') . "\n";
echo "- username: " . ($config['database']['username'] ?? 'NOT FOUND') . "\n";
echo "- password: " . (isset($config['database']['password']) ? '***hidden***' : 'NOT FOUND') . "\n";

echo "\nConfig fixed successfully! The site should now work.\n";
?>