<?php
/**
 * Test the exact authentication flow used by the real login
 */

session_start();

// Load config and autoloader  
require_once __DIR__ . '/vendor/autoload.php';

echo "<h1>Authentication Flow Test</h1>";

// Test if this is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>Processing Login (POST Request)</h2>";
    
    // Initialize everything the same way the real Auth controller does
    $config = require __DIR__ . '/config/config.php';
    $db = CMS\Utils\Database::getInstance($config['database']);
    
    // Create Auth utility exactly like Auth controller
    $auth = new CMS\Utils\Auth($db, $config['security']);
    
    // Create Request object like BaseController
    $request = new CMS\Utils\Request();
    
    echo "<h3>1. Request Method Check</h3>";
    $isPost = $request->isPost();
    echo "<p>Is POST: " . ($isPost ? "YES ✓" : "NO ✗") . "</p>";
    
    echo "<h3>2. CSRF Token Validation</h3>";
    $token = $request->post('_token');
    echo "<p>Token received: " . htmlspecialchars($token) . "</p>";
    echo "<p>Session token: " . ($_SESSION['_token'] ?? 'not set') . "</p>";
    
    $csrfValid = $auth->validateCsrfToken($token);
    echo "<p>CSRF Valid: " . ($csrfValid ? "YES ✓" : "NO ✗") . "</p>";
    
    if (!$csrfValid) {
        echo "<p style='color: red;'>CSRF validation failed! This is why login isn't working.</p>";
    }
    
    echo "<h3>3. Credential Extraction</h3>";
    $username = trim(htmlspecialchars($request->post('username', ''), ENT_QUOTES, 'UTF-8'));
    $password = $request->post('password', '');
    
    echo "<p>Username: " . htmlspecialchars($username) . "</p>";
    echo "<p>Password length: " . strlen($password) . " characters</p>";
    
    if (empty($username) || empty($password)) {
        echo "<p style='color: red;'>Username or password is empty!</p>";
    }
    
    echo "<h3>4. Authentication Attempt</h3>";
    if ($csrfValid && !empty($username) && !empty($password)) {
        $result = $auth->attempt($username, $password);
        echo "<p>Auth attempt result: " . ($result ? "SUCCESS ✓" : "FAILED ✗") . "</p>";
        
        if ($result) {
            echo "<p style='color: green;'>Login should have worked! Check session:</p>";
            echo "<pre>";
            print_r($_SESSION);
            echo "</pre>";
        }
    } else {
        echo "<p style='color: orange;'>Skipped authentication due to validation failures above.</p>";
    }
    
} else {
    echo "<p>Submit the form below to test the authentication flow:</p>";
}

// Generate a proper CSRF token
if (empty($_SESSION['_token'])) {
    $_SESSION['_token'] = bin2hex(random_bytes(32));
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Auth Flow Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
        h2, h3 { color: #333; border-bottom: 1px solid #ccc; padding-bottom: 5px; }
        form { background: #f9f9f9; padding: 20px; border-radius: 5px; margin-top: 20px; }
        input { padding: 8px; margin: 5px 0; width: 300px; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <form method="POST">
        <h3>Test Authentication Flow</h3>
        <div>
            <label>Username:</label><br>
            <input type="text" name="username" value="kevin" required>
        </div>
        <div>
            <label>Password:</label><br>
            <input type="password" name="password" value="(130Bpm)" required>
        </div>
        <input type="hidden" name="_token" value="<?= $_SESSION['_token'] ?>">
        <div style="margin-top: 10px;">
            <button type="submit">Test Authentication Flow</button>
        </div>
    </form>
    
    <hr>
    <p>Current session token: <code><?= $_SESSION['_token'] ?></code></p>
    <p><a href="/admin/login">Go to real login</a> | <a href="test_auth_flow.php">Reset test</a></p>
</body>
</html>