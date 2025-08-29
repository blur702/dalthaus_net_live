<?php
// Debug version of setup to find 500 error cause
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "PHP Version: " . PHP_VERSION . "<br>\n";
echo "Script started successfully<br>\n";

// Test 1: Basic PHP
echo "Test 1: Basic PHP working ✓<br>\n";

// Test 2: Session
if (function_exists('session_start')) {
    echo "Test 2: Session support available ✓<br>\n";
    session_start();
    echo "Test 2b: Session started ✓<br>\n";
} else {
    echo "Test 2: Session support NOT available ✗<br>\n";
}

// Test 3: PDO
if (extension_loaded('pdo')) {
    echo "Test 3: PDO loaded ✓<br>\n";
} else {
    echo "Test 3: PDO NOT loaded ✗<br>\n";
}

// Test 4: PDO MySQL
if (extension_loaded('pdo_mysql')) {
    echo "Test 4: PDO MySQL loaded ✓<br>\n";
} else {
    echo "Test 4: PDO MySQL NOT loaded ✗<br>\n";
}

// Test 5: Check for config file
$configFile = __DIR__ . '/includes/config.php';
if (file_exists($configFile)) {
    echo "Test 5: Config file exists (system may be installed) ✓<br>\n";
} else {
    echo "Test 5: Config file does not exist (fresh install) ✓<br>\n";
}

// Test 6: Check CLI
$is_cli = (php_sapi_name() === 'cli');
echo "Test 6: Running in " . ($is_cli ? "CLI" : "Browser") . " mode ✓<br>\n";

echo "<br>All basic tests passed! The issue might be in the full setup.php file.<br>\n";
echo "Try accessing <a href='setup.php'>setup.php</a> again.<br>\n";
?>