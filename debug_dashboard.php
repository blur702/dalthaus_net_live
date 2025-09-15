<?php
/**
 * Debug what happens when accessing dashboard
 */

session_start();

echo "<h1>Dashboard Access Debug</h1>";

// Load dependencies
require_once __DIR__ . '/vendor/autoload.php';
$config = require __DIR__ . '/config/config.php';

echo "<h2>Session Status</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Authentication Checks</h2>";

// Test BaseController isAuthenticated method
echo "<h3>BaseController::isAuthenticated() Logic</h3>";
$userIdSet = isset($_SESSION['user_id']);
$loggedInSet = isset($_SESSION['logged_in']);
$loggedInValue = $_SESSION['logged_in'] ?? 'not set';
$loggedInNotEmpty = !empty($_SESSION['logged_in']);

echo "<p>user_id set: " . ($userIdSet ? "YES" : "NO") . "</p>";
echo "<p>logged_in set: " . ($loggedInSet ? "YES" : "NO") . "</p>";
echo "<p>logged_in value: " . var_export($loggedInValue, true) . "</p>";
echo "<p>logged_in !empty(): " . ($loggedInNotEmpty ? "YES" : "NO") . "</p>";

$isAuthenticated = $userIdSet && $loggedInSet && $loggedInNotEmpty;
echo "<p><strong>Final isAuthenticated result: " . ($isAuthenticated ? "YES ✓" : "NO ✗") . "</strong></p>";

// Test Auth class check method
echo "<h3>Auth::check() Method</h3>";
try {
    $db = CMS\Utils\Database::getInstance($config['database']);
    $auth = new CMS\Utils\Auth($db, $config['security']);
    $authCheckResult = $auth->check();
    echo "<p>Auth::check() result: " . ($authCheckResult ? "YES ✓" : "NO ✗") . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>Auth::check() error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h2>Dashboard Controller Requirements</h2>";
echo "<p>The Dashboard controller runs:</p>";
echo "<ol>";
echo "<li>BaseController::isAuthenticated() - " . ($isAuthenticated ? "PASS" : "FAIL") . "</li>";
echo "<li>Calls requireAuth() which redirects to login if not authenticated</li>";
echo "</ol>";

if (!$isAuthenticated) {
    echo "<div style='background: #ffebee; border: 1px solid #f44336; padding: 15px; border-radius: 5px;'>";
    echo "<h3 style='color: #c62828;'>Authentication Failure Detected!</h3>";
    echo "<p>The Dashboard controller will redirect to login because isAuthenticated() returns false.</p>";
    echo "<p>This is why you're being redirected back to the login page.</p>";
    
    if (!$loggedInNotEmpty) {
        echo "<p><strong>Issue:</strong> logged_in value '" . var_export($loggedInValue, true) . "' fails the !empty() check.</p>";
        
        if ($loggedInValue === 1) {
            echo "<p><strong>Root Cause:</strong> logged_in is integer 1, not boolean true or string, and might be failing the !empty() check in some contexts.</p>";
        }
    }
    echo "</div>";
} else {
    echo "<div style='background: #e8f5e8; border: 1px solid #4caf50; padding: 15px; border-radius: 5px;'>";
    echo "<h3 style='color: #2e7d32;'>Authentication Should Work</h3>";
    echo "<p>All authentication checks pass. Dashboard access should work.</p>";
    echo "</div>";
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Debug</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
        h2, h3 { color: #333; border-bottom: 1px solid #ccc; padding-bottom: 5px; }
    </style>
</head>
<body>
    <p><a href="/admin/login">Login Page</a> | <a href="/admin/dashboard">Try Dashboard</a> | <a href="debug_dashboard.php">Refresh Debug</a></p>
</body>
</html>