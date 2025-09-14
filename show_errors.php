<?php
// Temporary script to show errors
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

echo "<h1>Error Display Enabled</h1>";
echo "<pre>";

try {
    echo "Loading application...\n\n";
    
    // Set up the environment as if accessing the home page
    $_SERVER['REQUEST_URI'] = '/';
    $_SERVER['REQUEST_METHOD'] = 'GET';
    
    // Include the main index.php
    require_once __DIR__ . '/index.php';
    
} catch (Throwable $e) {
    echo "\n=== CAUGHT ERROR ===\n";
    echo "Type: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "\nStack Trace:\n";
    echo $e->getTraceAsString();
}

echo "</pre>";
?>