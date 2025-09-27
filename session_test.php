<?php
// Session test with CMS configuration - safe for production

// Load the same configuration as the main app
$config = require __DIR__ . '/config/config.php';

// Apply the exact same session configuration as index.php
session_set_cookie_params([
    'lifetime' => $config['security']['session_lifetime'],
    'path' => '/',
    'domain' => '',
    'secure' => $config['security']['secure_cookies'],
    'httponly' => $config['security']['cookie_httponly'],
    'samesite' => $config['security']['cookie_samesite']
]);

session_name($config['security']['session_name']);
session_start();

// Set a test value if not already set
if (!isset($_SESSION['test_value'])) {
    $_SESSION['test_value'] = 'Session working at ' . date('Y-m-d H:i:s');
    $status = 'NEW';
} else {
    $status = 'EXISTING';
}

echo "Session Status: $status\n";
echo "Session ID: " . session_id() . "\n";
echo "Session Name: " . session_name() . "\n";
echo "Test Value: " . ($_SESSION['test_value'] ?? 'NOT SET') . "\n";
echo "CMS Config - Secure Cookies: " . ($config['security']['secure_cookies'] ? 'true' : 'false') . "\n";
echo "CMS Config - HttpOnly: " . ($config['security']['cookie_httponly'] ? 'true' : 'false') . "\n";
echo "CMS Config - SameSite: " . $config['security']['cookie_samesite'] . "\n";
echo "Session Cookie Params:\n";
print_r(session_get_cookie_params());

// Clean up
if (isset($_GET['clean'])) {
    session_destroy();
    echo "\nSession destroyed\n";
}
?>