<?php
// Debug script to identify 500 error
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<pre>";
echo "=== DEBUG INFORMATION ===\n\n";

// Check PHP version
echo "PHP Version: " . PHP_VERSION . "\n\n";

// Try to load config
echo "1. Loading config...\n";
try {
    $config = require __DIR__ . '/config/config.php';
    echo "✓ Config loaded successfully\n";
    echo "  Database: " . $config['database']['dbname'] . "\n\n";
} catch (Exception $e) {
    echo "✗ Config error: " . $e->getMessage() . "\n\n";
}

// Check autoloader
echo "2. Checking autoloader...\n";
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    echo "✓ Autoloader loaded\n\n";
} else {
    echo "✗ Autoloader not found!\n\n";
}

// Test class loading
echo "3. Testing class loading...\n";
$classes = [
    'CMS\Router' => 'Router',
    'CMS\Utils\Database' => 'Database',
    'CMS\Controllers\BaseController' => 'BaseController',
    'CMS\Models\BaseModel' => 'BaseModel'
];

foreach ($classes as $class => $name) {
    if (class_exists($class)) {
        echo "✓ $name class found\n";
    } else {
        echo "✗ $name class NOT found\n";
    }
}
echo "\n";

// Test database connection
echo "4. Testing database...\n";
try {
    use CMS\Utils\Database;
    $db = Database::getInstance($config['database']);
    echo "✓ Database connected\n\n";
} catch (Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n\n";
}

// Try to run the main index.php logic
echo "5. Testing main application...\n";
try {
    $_SERVER['REQUEST_URI'] = '/';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    
    ob_start();
    include __DIR__ . '/index.php';
    $output = ob_get_clean();
    
    echo "✓ Application loaded\n";
    echo "  Output length: " . strlen($output) . " bytes\n";
} catch (Exception $e) {
    echo "✗ Application error: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . "\n";
    echo "  Line: " . $e->getLine() . "\n";
    echo "  Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
?>