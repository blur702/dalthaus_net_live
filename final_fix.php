<?php
// Final fix - properly set PDO options with constants
header('Content-Type: text/plain');

$configFile = __DIR__ . '/config/config.php';

// Read config as text
$configContent = file_get_contents($configFile);

// Fix the PDO options section - replace numeric keys with proper constants
$configContent = preg_replace(
    "/'options' => \s*array \(\s*3 => 2,\s*19 => 2,\s*20 => false,\s*\)/",
    "'options' => [\n        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,\n        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,\n        PDO::ATTR_EMULATE_PREPARES => false,\n        PDO::MYSQL_ATTR_INIT_COMMAND => \"SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci\"\n    ]",
    $configContent
);

// Also fix any lingering session issues - remove the duplicate 'dbname' in session
$configContent = str_replace("'dbname' => 'cms_session',", "'name' => 'cms_session',", $configContent);

// Backup
$backupFile = $configFile . '.backup.' . date('YmdHis');
copy($configFile, $backupFile);
echo "Backup created: " . basename($backupFile) . "\n";

// Write fixed config
file_put_contents($configFile, $configContent);
echo "Config fixed!\n\n";

// Test it works
try {
    $config = require $configFile;
    echo "✓ Config loads successfully\n";
    echo "✓ Database: " . $config['database']['dbname'] . "\n";
    
    // Test database connection
    $dsn = "mysql:host={$config['database']['host']};dbname={$config['database']['dbname']};charset={$config['database']['charset']}";
    $pdo = new PDO($dsn, $config['database']['username'], $config['database']['password'], $config['database']['options']);
    echo "✓ Database connection successful!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n✅ Final fix complete! The site should work now.\n";
?>