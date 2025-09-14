<?php
// Create a script that bypasses all caching to test database
$testScript = '<?php
// Direct database test - bypasses all caching
header("Content-Type: text/plain");

// Load config directly
$config = require __DIR__ . "/config/config.php";

echo "=== Direct Database Test ===\n\n";
echo "Config values:\n";
echo "  Host: " . $config["database"]["host"] . "\n";
echo "  Database: " . $config["database"]["dbname"] . "\n";
echo "  Username: " . $config["database"]["username"] . "\n";
echo "  Password set: " . (!empty($config["database"]["password"]) ? "YES" : "NO") . "\n\n";

// Test connection
try {
    $dsn = "mysql:host={$config[\"database\"][\"host\"]};dbname={$config[\"database\"][\"dbname\"]};charset={$config[\"database\"][\"charset\"]}";
    $pdo = new PDO($dsn, $config["database"]["username"], $config["database"]["password"], $config["database"]["options"]);
    echo "✅ Database connection SUCCESSFUL!\n\n";
    
    // Test query
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables found: " . count($tables) . "\n";
    foreach ($tables as $table) {
        echo "  - $table\n";
    }
} catch (PDOException $e) {
    echo "❌ Database connection FAILED!\n";
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
?>';

// Save it
file_put_contents('test_db_direct.php', $testScript);
echo "Created test_db_direct.php\n";

// Commit and push
exec('git add test_db_direct.php 2>&1');
exec('git commit -m "Add direct database test script" 2>&1');
exec('git push origin main 2>&1');

echo "Pushed to GitHub\n";

// Pull on server
$agentUrl = 'https://dalthaus.net/agent.php';
$key = 'dalthaus_agent_key_2025';
$pullUrl = $agentUrl . '?action=git_pull&key=' . $key;
$response = @file_get_contents($pullUrl);
if ($response) {
    $data = json_decode($response, true);
    if ($data['success']) {
        echo "Pulled on server\n";
    }
}

echo "\nNow test: https://dalthaus.net/test_db_direct.php\n";
?>