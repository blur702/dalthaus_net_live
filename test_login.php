<?php
/**
 * Login Diagnostic Script
 * Run this to test the login flow and identify issues
 */

// Start session
session_start();

// Load config and autoloader
require_once __DIR__ . '/vendor/autoload.php';
$config = require __DIR__ . '/config/config.php';

// Initialize database
$db = CMS\Utils\Database::getInstance($config['database']);

// Initialize Auth utility
$auth = new CMS\Utils\Auth($db, $config['security']);

// Display current session info
echo "<h2>Current Session Status</h2>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Session Name: " . session_name() . "\n";
echo "Session Data:\n";
print_r($_SESSION);
echo "</pre>";

// Check if already logged in
echo "<h2>Authentication Status</h2>";
if (isset($_SESSION['user_id']) && isset($_SESSION['logged_in'])) {
    echo "<p style='color: green;'>✓ User is logged in</p>";
    echo "<pre>";
    echo "User ID: " . $_SESSION['user_id'] . "\n";
    echo "Username: " . ($_SESSION['username'] ?? 'not set') . "\n";
    echo "Is Admin: " . ($_SESSION['is_admin'] ?? 'not set') . "\n";
    echo "</pre>";
} else {
    echo "<p style='color: red;'>✗ User is NOT logged in</p>";
}

// Test authentication if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>Login Attempt Results</h2>";
    
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    echo "<p>Attempting login for username: <strong>" . htmlspecialchars($username) . "</strong></p>";
    
    // Test database connection
    echo "<h3>1. Database Connection Test</h3>";
    try {
        $testQuery = $db->fetchRow("SELECT 1 as test");
        echo "<p style='color: green;'>✓ Database connection successful</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
        exit;
    }
    
    // Test user lookup
    echo "<h3>2. User Lookup Test</h3>";
    $user = $db->fetchRow(
        'SELECT user_id, username, email, password_hash FROM users WHERE username = ? OR email = ?',
        [$username, $username]
    );
    
    if ($user) {
        echo "<p style='color: green;'>✓ User found in database</p>";
        echo "<pre>User ID: " . $user['user_id'] . "\nUsername: " . $user['username'] . "</pre>";
        
        // Test password verification
        echo "<h3>3. Password Verification Test</h3>";
        if (password_verify($password, $user['password_hash'])) {
            echo "<p style='color: green;'>✓ Password is correct</p>";
            
            // Test session creation
            echo "<h3>4. Session Creation Test</h3>";
            $attemptResult = $auth->attempt($username, $password);
            
            if ($attemptResult) {
                echo "<p style='color: green;'>✓ Authentication successful</p>";
                echo "<p>Session should now contain:</p>";
                echo "<pre>";
                print_r($_SESSION);
                echo "</pre>";
                
                // Test redirect
                echo "<h3>5. Redirect Test</h3>";
                echo "<p>Testing if headers have been sent...</p>";
                if (headers_sent($file, $line)) {
                    echo "<p style='color: red;'>✗ Headers already sent in $file at line $line</p>";
                    echo "<p>Cannot use PHP header redirect. Would need JavaScript redirect.</p>";
                } else {
                    echo "<p style='color: green;'>✓ Headers not sent, redirect should work</p>";
                }
                
                echo "<hr>";
                echo "<p><strong>Login successful!</strong> You should now be able to:</p>";
                echo "<ul>";
                echo "<li><a href='/admin/dashboard'>Go to Dashboard</a></li>";
                echo "<li><a href='/admin'>Go to Admin</a></li>";
                echo "</ul>";
                
            } else {
                echo "<p style='color: red;'>✗ Authentication failed (auth->attempt returned false)</p>";
            }
        } else {
            echo "<p style='color: red;'>✗ Password is incorrect</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ User not found in database</p>";
    }
}

// Display login form
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login Diagnostic Tool</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        h2 { color: #333; border-bottom: 2px solid #ccc; padding-bottom: 10px; }
        h3 { color: #666; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
        form { background: #f9f9f9; padding: 20px; border-radius: 5px; margin-top: 20px; }
        input { padding: 8px; margin: 5px 0; width: 300px; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #0056b3; }
        hr { margin: 20px 0; }
    </style>
</head>
<body>
    <h1>Login Diagnostic Tool</h1>
    
    <?php if ($_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
    <form method="POST">
        <h2>Test Login</h2>
        <div>
            <label>Username:</label><br>
            <input type="text" name="username" value="kevin" required>
        </div>
        <div>
            <label>Password:</label><br>
            <input type="password" name="password" placeholder="Enter password" required>
        </div>
        <div style="margin-top: 10px;">
            <button type="submit">Test Login</button>
        </div>
    </form>
    <?php endif; ?>
    
    <div style="margin-top: 20px;">
        <a href="/admin/login">Go to actual login page</a> | 
        <a href="test_login.php">Reset test</a>
    </div>
</body>
</html>