<?php
/**
 * Test what happens when the login form is submitted
 */

session_start();

echo "<h1>Form Submission Test</h1>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>Form was submitted via POST!</h2>";
    echo "<p><strong>POST Data received:</strong></p>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    echo "<p><strong>Session before processing:</strong></p>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
    
    // Test the exact same authentication process
    require_once __DIR__ . '/vendor/autoload.php';
    $config = require __DIR__ . '/config/config.php';
    $db = CMS\Utils\Database::getInstance($config['database']);
    $auth = new CMS\Utils\Auth($db, $config['security']);
    $request = new CMS\Utils\Request();
    
    $token = $request->post('_token');
    $username = $request->post('username');
    $password = $request->post('password');
    
    echo "<h3>Processing Authentication:</h3>";
    echo "<p>CSRF Token: " . htmlspecialchars($token) . "</p>";
    echo "<p>Username: " . htmlspecialchars($username) . "</p>";
    echo "<p>Password Length: " . strlen($password) . "</p>";
    
    $csrfValid = $auth->validateCsrfToken($token);
    echo "<p>CSRF Valid: " . ($csrfValid ? "YES" : "NO") . "</p>";
    
    if ($csrfValid && !empty($username) && !empty($password)) {
        $result = $auth->attempt($username, $password);
        echo "<p>Authentication Result: " . ($result ? "SUCCESS" : "FAILED") . "</p>";
        
        if ($result) {
            echo "<p><strong>Session after authentication:</strong></p>";
            echo "<pre>";
            print_r($_SESSION);
            echo "</pre>";
            
            echo "<h3>Testing Different Redirect Methods:</h3>";
            
            // Method 1: Immediate JavaScript redirect
            echo '<div style="border: 1px solid green; padding: 10px; margin: 10px 0;">';
            echo '<h4>Method 1: Immediate JavaScript</h4>';
            echo '<script>console.log("Testing immediate redirect..."); setTimeout(() => { window.location.href = "/admin/dashboard"; }, 2000);</script>';
            echo '<p>JavaScript redirect will trigger in 2 seconds...</p>';
            echo '</div>';
            
            // Method 2: Meta refresh
            echo '<div style="border: 1px solid blue; padding: 10px; margin: 10px 0;">';
            echo '<h4>Method 2: Meta Refresh (5 seconds)</h4>';
            echo '<meta http-equiv="refresh" content="5;url=/admin/dashboard">';
            echo '<p>Meta refresh will trigger in 5 seconds...</p>';
            echo '</div>';
            
            // Method 3: Manual link
            echo '<div style="border: 1px solid orange; padding: 10px; margin: 10px 0;">';
            echo '<h4>Method 3: Manual Link</h4>';
            echo '<p><a href="/admin/dashboard" style="font-size: 18px; color: blue;">Click here to go to Dashboard</a></p>';
            echo '</div>';
        }
    }
} else {
    echo "<p>No POST data received. Use the form below to test:</p>";
}

// Generate CSRF token
if (empty($_SESSION['_token'])) {
    $_SESSION['_token'] = bin2hex(random_bytes(32));
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Form Submit Test</title>
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
    <?php if ($_SERVER['REQUEST_METHOD'] !== 'POST'): ?>
    <form method="POST" action="/test_form_submit.php">
        <h3>Test Login Form Submission</h3>
        <p>This will test what happens when you submit the login form with the exact same process.</p>
        
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
            <button type="submit">Submit Login Form</button>
        </div>
    </form>
    <?php endif; ?>
    
    <p><a href="/admin/login">Try Real Login</a> | <a href="/admin/dashboard">Try Dashboard Direct</a></p>
</body>
</html>