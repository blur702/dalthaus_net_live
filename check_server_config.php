<?php
$configPath = __DIR__ . "/config/config.php";
$prodConfigPath = __DIR__ . "/config/config.production.php";

echo "Config files present:\n";
if (file_exists($configPath)) echo "  - config.php exists\n";
if (file_exists($prodConfigPath)) echo "  - config.production.php exists\n";

echo "\nLoading config.php:\n";
if (file_exists($configPath)) {
    $config = require $configPath;
    echo "  Database: " . $config["database"]["dbname"] . "\n";
    echo "  Username: " . $config["database"]["username"] . "\n";
}

echo "\nEnvironment variables:\n";
echo "  DB_NAME: " . (getenv("DB_NAME") ?: "not set") . "\n";
echo "  DB_USER: " . (getenv("DB_USER") ?: "not set") . "\n";

if (file_exists($prodConfigPath) && !getenv("DB_NAME")) {
    echo "\nWARNING: Production config exists but env vars not set!\n";
    echo "This will cause fallback to default values.\n";
}
?>