<?php
// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<pre>";
echo "=== PHP Error Log Test ===\n\n";

echo "1. PHP Version: " . phpversion() . "\n";
echo "2. Error reporting level: " . error_reporting() . "\n\n";

echo "3. Testing includes:\n";

// Test each include separately
$includes = [
    'includes/config.php',
    'includes/database.php',
    'includes/functions.php'
];

foreach ($includes as $file) {
    if (file_exists($file)) {
        echo "   - $file exists\n";
        try {
            require_once $file;
            echo "     ✓ Loaded successfully\n";
        } catch (Exception $e) {
            echo "     ✗ Error: " . $e->getMessage() . "\n";
        } catch (ParseError $e) {
            echo "     ✗ Parse Error: " . $e->getMessage() . "\n";
        } catch (Error $e) {
            echo "     ✗ Fatal Error: " . $e->getMessage() . "\n";
        }
    } else {
        echo "   - $file NOT FOUND\n";
    }
}

echo "\n4. Testing function availability:\n";
$functions = ['getSetting', 'getSettings', 'isAdmin', 'isMaintenanceMode'];
foreach ($functions as $func) {
    echo "   - $func: " . (function_exists($func) ? "✓ exists" : "✗ missing") . "\n";
}

echo "\n5. Database connection test:\n";
if (isset($pdo)) {
    echo "   ✓ PDO object exists\n";
} else {
    echo "   ✗ PDO object not found\n";
}

echo "</pre>";
?>