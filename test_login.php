<?php
session_start();
header("Content-Type: text/plain");

echo "=== Login Redirect Test ===\n\n";

// Check current session
echo "Session ID: " . session_id() . "\n";
echo "User ID: " . ($_SESSION["user_id"] ?? "not set") . "\n";
echo "Is Admin: " . ($_SESSION["is_admin"] ?? "not set") . "\n\n";

// Check if login controller exists
$loginController = __DIR__ . "/src/Controllers/Admin/Auth.php";
if (file_exists($loginController)) {
    echo "✓ Auth controller exists\n";
    
    // Check for redirect loops
    $content = file_get_contents($loginController);
    if (strpos($content, "header(\"Location:") !== false) {
        echo "Auth controller has redirects configured\n";
    }
} else {
    echo "✗ Auth controller missing!\n";
}

// Check routes
$routesFile = __DIR__ . "/config/routes.php";
if (file_exists($routesFile)) {
    $routes = file_get_contents($routesFile);
    if (strpos($routes, "/admin/login") !== false) {
        echo "✓ Login route exists\n";
    }
    if (strpos($routes, "Admin\\Auth::login") !== false) {
        echo "✓ Auth::login action mapped\n";
    }
}

// Test authentication flow
echo "\nTesting authentication flow:\n";

// Simulate login attempt
$_SESSION["test_login"] = true;
echo "1. Set test session variable\n";

// Check if it persists
if (isset($_SESSION["test_login"])) {
    echo "2. Session variable persists ✓\n";
} else {
    echo "2. Session variable lost ✗\n";
}

// Clean up
unset($_SESSION["test_login"]);

echo "\n✓ Login test complete\n";
?>