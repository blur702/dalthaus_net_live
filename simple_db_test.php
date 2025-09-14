<?php
// Ultra simple database test
header('Content-Type: text/plain');

echo "=== Simple Database Test ===\n\n";

// Check if config exists
$configFile = __DIR__ . '/config/config.php';
if (!file_exists($configFile)) {
    die("ERROR: Config file not found!\n");
}

// Load config
$config = require $configFile;
echo "Config loaded\n";
echo "Database: " . $config['database']['dbname'] . "\n";
echo "Username: " . $config['database']['username'] . "\n\n";

// Direct connection test with hardcoded values
echo "Testing with hardcoded values:\n";
try {
    $pdo = new PDO(
        "mysql:host=localhost;dbname=dalthaus_maincms;charset=utf8mb4",
        "dalthaus_maincms",
        "f4!,Wpds=w6*=~+1",
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]
    );
    echo "✅ Connection SUCCESSFUL!\n";
    
    // Test a simple query
    $stmt = $pdo->query("SELECT 1");
    echo "✅ Query test passed!\n";
    
} catch (PDOException $e) {
    echo "❌ Connection FAILED!\n";
    echo "Error: " . $e->getMessage() . "\n";
}
?>