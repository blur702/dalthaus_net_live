<?php
/**
 * Simple POST test to verify form submission is working
 */

session_start();

echo "<h1>POST Test</h1>";

// Show request method
echo "<p>Request Method: <strong>" . $_SERVER['REQUEST_METHOD'] . "</strong></p>";

// Show POST data if any
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>✓ POST Request Received!</h2>";
    echo "<p>POST Data:</p>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    echo "<p>Session Data:</p>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
    
    // Test redirect
    if (isset($_POST['test_redirect'])) {
        echo "<p>Testing redirect...</p>";
        if (headers_sent($file, $line)) {
            echo "<p style='color: red;'>Headers already sent at $file:$line</p>";
        } else {
            echo "<p style='color: green;'>Headers not sent, redirect would work</p>";
            // Uncomment to test actual redirect:
            // header("Location: /admin/dashboard");
            // exit;
        }
    }
} else {
    echo "<h2>GET Request - Submit the form below to test POST</h2>";
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>POST Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; }
        form { background: #f9f9f9; padding: 20px; border-radius: 5px; margin-top: 20px; }
        input, button { padding: 10px; margin: 5px; }
    </style>
</head>
<body>
    <form method="POST" action="/test_post.php">
        <h3>Test Form</h3>
        <input type="text" name="test_field" value="test_value" />
        <input type="hidden" name="test_redirect" value="1" />
        <button type="submit">Submit POST Request</button>
    </form>
    
    <hr>
    
    <form method="POST" action="/admin/login">
        <h3>Test Login Form (goes to /admin/login)</h3>
        <input type="text" name="username" value="test" />
        <input type="password" name="password" value="test" />
        <input type="hidden" name="_token" value="test_token" />
        <button type="submit">Submit to /admin/login</button>
    </form>
    
    <hr>
    
    <form method="POST" action="/admin/login?debug=1">
        <h3>Test Login Form with Debug (goes to /admin/login?debug=1)</h3>
        <input type="text" name="username" value="test" />
        <input type="password" name="password" value="test" />
        <input type="hidden" name="_token" value="test_token" />
        <button type="submit">Submit to /admin/login?debug=1</button>
    </form>
</body>
</html>