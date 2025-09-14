<?php
// Test without session
header("Content-Type: text/plain");
echo "No session test\n";
require_once __DIR__ . "/vendor/autoload.php";
$config = require __DIR__ . "/config/config.php";
echo "Config loaded\n";
$db = CMS\Utils\Database::getInstance($config["database"]);
echo "Database connected\n";
?>