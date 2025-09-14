<?php
// Simple test to see what's failing in index.php
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "=== Testing Index.php Components ===\n\n";

// 1. Test autoloader
echo "1. Loading autoloader...\n";
require_once __DIR__ . '/vendor/autoload.php';
echo "   ✓ Autoloader loaded\n\n";

// 2. Test config
echo "2. Loading config...\n";
$config = require __DIR__ . '/config/config.php';
echo "   ✓ Config loaded\n";
echo "   Database: " . $config['database']['dbname'] . "\n\n";

// 3. Test session
echo "3. Starting session...\n";
session_set_cookie_params([
    'lifetime' => $config['security']['session_lifetime'],
    'path' => '/',
    'domain' => '',
    'secure' => $config['security']['secure_cookies'],
    'httponly' => $config['security']['cookie_httponly'],
    'samesite' => $config['security']['cookie_samesite']
]);
session_name($config['security']['session_name']);
session_start();
echo "   ✓ Session started\n\n";

// 4. Test router
echo "4. Creating router...\n";
try {
    $router = new CMS\Utils\Router($config['routing']);
    echo "   ✓ Router created\n\n";
} catch (Exception $e) {
    echo "   ✗ Router error: " . $e->getMessage() . "\n\n";
}

// 5. Test routes
echo "5. Loading routes...\n";
try {
    $routes = require __DIR__ . '/config/routes.php';
    $routes($router);
    echo "   ✓ Routes loaded\n\n";
} catch (Exception $e) {
    echo "   ✗ Routes error: " . $e->getMessage() . "\n\n";
}

// 6. Test a controller
echo "6. Testing controller instantiation...\n";
try {
    $homeController = new CMS\Controllers\Public\Home();
    echo "   ✓ Home controller can be instantiated\n\n";
} catch (Exception $e) {
    echo "   ✗ Controller error: " . $e->getMessage() . "\n\n";
}

echo "=== All tests complete! ===\n";
echo "\nIf all tests pass, the issue might be in the dispatch process.\n";
?>