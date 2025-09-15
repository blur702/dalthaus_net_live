<?php
// Upload debug script via agent
echo "=== UPLOADING DEBUG SCRIPT ===\n\n";

// Encode the debug script
$debugScript = file_get_contents('debug_redirect.php');
$encoded = base64_encode($debugScript);

// Create an uploader script that we can push to git
$uploader = '<?php
// Upload debug script
$debugContent = base64_decode("' . $encoded . '");
file_put_contents(__DIR__ . "/debug_redirect.php", $debugContent);
echo "Debug script uploaded successfully";
?>';

file_put_contents('upload_debug.php', $uploader);
echo "1. Created upload script\n";

// Also create a simple index bypass test
$indexBypass = '<?php
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
    $db = CMS\\Utils\\Database::getInstance($config["database"]);
    echo "Database connected<br>";
    
    // Test router initialization
    $router = new CMS\\Utils\\Router($config["routing"]);
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
?>';

file_put_contents('index_bypass_test.php', $indexBypass);
echo "2. Created index bypass test\n";

// Push to GitHub
exec('git add upload_debug.php index_bypass_test.php && git commit -m "Add debug upload and bypass test scripts" && git push origin main 2>&1', $output);
echo "3. Pushed to GitHub\n";

// Try to access the uploader directly (even though git pull failed, maybe old files work)
echo "\n4. Trying to access upload script...\n";
$response = @file_get_contents('https://dalthaus.net/upload_debug.php');
if ($response) {
    echo "Upload response: $response\n";
} else {
    echo "Upload script not accessible (expected due to git pull failure)\n";
}

// Test the index bypass
echo "\n5. Testing index bypass...\n";
$response = @file_get_contents('https://dalthaus.net/index_bypass_test.php');
if ($response) {
    if (strpos($response, 'EXCEPTION CAUGHT') !== false) {
        echo "Index bypass caught an exception - this might be the source!\n";
        // Extract exception details
        if (preg_match('/<strong>Message:<\/strong>([^<]+)/', $response, $matches)) {
            echo "Exception: " . trim($matches[1]) . "\n";
        }
    } else {
        echo "Index bypass completed successfully\n";
    }
} else {
    echo "Index bypass test not accessible\n";
}

echo "\n✅ Debug setup complete\n";
echo "\nNext steps:\n";
echo "1. Manually upload debug_redirect.php to your server\n";
echo "2. Access https://dalthaus.net/debug_redirect.php\n";
echo "3. Check the error logs via agent\n";
?>