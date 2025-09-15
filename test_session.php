<?php
/**
 * Session debugging tool
 */

session_start();

echo "<h1>Session Debug</h1>";

// Show session info
echo "<h2>Session Information</h2>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Session Name: " . session_name() . "\n";
echo "Session Save Path: " . session_save_path() . "\n";
echo "Session Cookie Params:\n";
print_r(session_get_cookie_params());
echo "</pre>";

// Show session data
echo "<h2>Session Data</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Check authentication status
echo "<h2>Authentication Check</h2>";
$isAuthenticated = isset($_SESSION['user_id']) && 
                   isset($_SESSION['logged_in']) && 
                   $_SESSION['logged_in'] === true;

echo "<p>Is Authenticated: " . ($isAuthenticated ? "YES ✓" : "NO ✗") . "</p>";

if ($isAuthenticated) {
    echo "<p>User ID: " . $_SESSION['user_id'] . "</p>";
    echo "<p>Username: " . ($_SESSION['username'] ?? 'not set') . "</p>";
} else {
    echo "<p>Missing requirements:</p>";
    echo "<ul>";
    if (!isset($_SESSION['user_id'])) echo "<li>user_id not set</li>";
    if (!isset($_SESSION['logged_in'])) echo "<li>logged_in not set</li>";
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] !== true) {
        echo "<li>logged_in is not true (value: " . var_export($_SESSION['logged_in'], true) . ")</li>";
    }
    echo "</ul>";
}

// Test session persistence
if (isset($_POST['test_set'])) {
    $_SESSION['test_value'] = time();
    echo "<p style='color: green;'>✓ Set test_value to " . $_SESSION['test_value'] . "</p>";
}

echo "<h2>Test Session Persistence</h2>";
if (isset($_SESSION['test_value'])) {
    echo "<p>test_value exists: " . $_SESSION['test_value'] . "</p>";
} else {
    echo "<p>test_value not set</p>";
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Session Debug</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
        h2 { color: #333; border-bottom: 2px solid #ccc; padding-bottom: 10px; }
    </style>
</head>
<body>
    <form method="POST">
        <button type="submit" name="test_set" value="1">Set Test Session Value</button>
    </form>
    
    <hr>
    
    <p>
        <a href="/admin/login">Go to Login</a> | 
        <a href="/admin/dashboard">Try Dashboard</a> | 
        <a href="test_session.php">Refresh</a>
    </p>
</body>
</html>