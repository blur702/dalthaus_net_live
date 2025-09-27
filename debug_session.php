<?php
// Debug session configuration on production
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Session Configuration Debug</h1>";

// Check which config file exists and is being used
echo "<h3>Config File Check:</h3>";
$config_files = [
    'config/config.php' => file_exists(__DIR__ . '/config/config.php'),
    'config/config.production.php' => file_exists(__DIR__ . '/config/config.production.php')
];

foreach ($config_files as $file => $exists) {
    echo "<p>$file: " . ($exists ? '✅ EXISTS' : '❌ NOT FOUND') . "</p>";
}

// Load and display the actual config being used
try {
    $config = require __DIR__ . '/config/config.php';
    echo "<h3>Loaded Config Security Settings:</h3>";
    echo "<table border='1'>";
    echo "<tr><th>Setting</th><th>Value</th></tr>";
    foreach ($config['security'] as $key => $value) {
        echo "<tr><td>$key</td><td>" . (is_bool($value) ? ($value ? 'true' : 'false') : $value) . "</td></tr>";
    }
    echo "</table>";

    // Test session configuration before starting session
    echo "<h3>Session Configuration Test:</h3>";
    echo "<p>session_name() before setting: " . session_name() . "</p>";

    // Apply the configuration
    session_set_cookie_params([
        'lifetime' => $config['security']['session_lifetime'],
        'path' => '/',
        'domain' => '',
        'secure' => $config['security']['secure_cookies'],
        'httponly' => $config['security']['cookie_httponly'],
        'samesite' => $config['security']['cookie_samesite']
    ]);

    session_name($config['security']['session_name']);

    echo "<p>session_name() after setting: " . session_name() . "</p>";

    // Display cookie parameters
    $params = session_get_cookie_params();
    echo "<h3>Session Cookie Parameters:</h3>";
    echo "<table border='1'>";
    echo "<tr><th>Parameter</th><th>Value</th></tr>";
    foreach ($params as $key => $value) {
        echo "<tr><td>$key</td><td>" . (is_bool($value) ? ($value ? 'true' : 'false') : $value) . "</td></tr>";
    }
    echo "</table>";

} catch (Exception $e) {
    echo "<p>❌ Config loading error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<p><strong>Delete this file after debugging!</strong></p>";
?>