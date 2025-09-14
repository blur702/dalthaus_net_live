<?php
// Test route generation
header('Content-Type: text/plain');

require_once __DIR__ . '/vendor/autoload.php';

$config = require __DIR__ . '/config/config.php';

// Create router
$router = new CMS\Utils\Router($config['routing']);

// Load routes
$routes = require __DIR__ . '/config/routes.php';
$routes($router);

// Use reflection to check the routes
$reflection = new ReflectionClass($router);
$routesProperty = $reflection->getProperty('routes');
$routesProperty->setAccessible(true);
$allRoutes = $routesProperty->getValue($router);

echo "=== All Registered Routes ===\n\n";
foreach ($allRoutes as $route) {
    echo sprintf(
        "%-6s %-30s => %s::%s\n",
        $route['method'],
        $route['pattern'],
        $route['controller'],
        $route['action']
    );
}

echo "\n=== Testing Controller Classes ===\n\n";
$testControllers = [
    'Public\\Home',
    'Admin\\Dashboard',
    'Admin\\Auth'
];

foreach ($testControllers as $controller) {
    $fullClass = 'CMS\\Controllers\\' . $controller;
    echo $fullClass . ': ' . (class_exists($fullClass) ? '✓ EXISTS' : '✗ NOT FOUND') . "\n";
}

echo "\n✅ Route testing complete!\n";
?>