<?php
// Bypass index.php to test direct routing
error_reporting(E_ALL);
ini_set("display_errors", "1");

echo "<!DOCTYPE html><html><body>";
echo "<h1>Index Bypass Test</h1>";

// Check what would happen in index.php
echo "<h2>Exception Handler Test</h2>";
try {
    // Load config
    $config = require __DIR__ . "/config/config.php";
    echo "Config loaded<br>";
    
    // Test session
    session_start();
    echo "Session started: " . session_id() . "<br>";
    
    // Test database  
    require_once __DIR__ . "/vendor/autoload.php";
    $db = CMS\Utils\Database::getInstance($config["database"]);
    echo "Database connected<br>";
    
    // Test router initialization
    $router = new CMS\Utils\Router($config["routing"]);
    echo "Router created<br>";
    
    // Load routes
    $routes = require __DIR__ . "/config/routes.php";
    $routes($router);
    echo "Routes loaded<br>";
    
    // Check current URL
    $uri = $_SERVER["REQUEST_URI"] ?? "/";
    echo "Current URI: $uri<br>";
    
    // Test route matching without dispatching
    echo "Testing route matching...<br>";
    
} catch (Exception $e) {
    echo "<h3 style=\"color: red;\">EXCEPTION CAUGHT:</h3>";
    echo "<strong>Message:</strong> " . $e->getMessage() . "<br>";
    echo "<strong>File:</strong> " . $e->getFile() . ":" . $e->getLine() . "<br>";
    echo "<strong>Stack Trace:</strong><pre>" . $e->getTraceAsString() . "</pre>";
}

echo "</body></html>";
?>