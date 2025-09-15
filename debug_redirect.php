<?php
// Direct debug script - upload this manually to debug redirects
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

echo "<!DOCTYPE html><html><head><title>Redirect Debug</title></head><body>";
echo "<h1>Redirect Loop Debug</h1>";

// Track all headers sent
if (!function_exists('debug_header')) {
    function debug_header($header, $replace = true, $response_code = 0) {
        echo "<div style='color: red; font-weight: bold;'>REDIRECT: $header</div>";
        // Show stack trace
        $trace = debug_backtrace();
        foreach ($trace as $i => $call) {
            if (isset($call['file'])) {
                echo "<div style='margin-left: 20px; font-size: 12px;'>$i: {$call['file']}:{$call['line']} {$call['function']}</div>";
            }
        }
        header($header, $replace, $response_code);
    }
}

echo "<h2>Current Request</h2>";
echo "<strong>URI:</strong> " . ($_SERVER['REQUEST_URI'] ?? 'unknown') . "<br>";
echo "<strong>Method:</strong> " . ($_SERVER['REQUEST_METHOD'] ?? 'unknown') . "<br>";

echo "<h2>Session State</h2>";
session_start();
echo "<strong>Session ID:</strong> " . session_id() . "<br>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Files Check</h2>";
$files = [
    'index.php' => __DIR__ . '/index.php',
    'Auth Controller' => __DIR__ . '/src/Controllers/Admin/Auth.php',
    'BaseController' => __DIR__ . '/src/Controllers/BaseController.php',
    'Router' => __DIR__ . '/src/Utils/Router.php',
    'Config' => __DIR__ . '/config/config.php'
];

foreach ($files as $name => $path) {
    if (file_exists($path)) {
        $mtime = date('Y-m-d H:i:s', filemtime($path));
        echo "<strong>$name:</strong> ✓ (modified: $mtime)<br>";
    } else {
        echo "<strong>$name:</strong> ❌ NOT FOUND<br>";
    }
}

echo "<h2>Auth Controller Check</h2>";
$authFile = __DIR__ . '/src/Controllers/Admin/Auth.php';
if (file_exists($authFile)) {
    $content = file_get_contents($authFile);
    
    // Check for redirect in login method
    if (preg_match('/public function login\(\)[^{]*{([^}]+(?:{[^}]*}[^}]*)*)}/', $content, $matches)) {
        echo "<strong>Login method found:</strong><br>";
        echo "<pre style='background: #f0f0f0; padding: 10px;'>" . htmlspecialchars($matches[0]) . "</pre>";
        
        if (strpos($matches[0], 'redirect') !== false) {
            echo "<div style='color: red;'>⚠️ Login method contains redirect!</div>";
        } else {
            echo "<div style='color: green;'>✓ Login method does not redirect</div>";
        }
    } else {
        echo "Could not parse login method";
    }
} else {
    echo "Auth controller not found!";
}

echo "<h2>Simulate Login Access</h2>";
$uri = $_SERVER['REQUEST_URI'] ?? '';

// Clear session for test
$_SESSION = [];

echo "Testing what happens when accessing /admin/login...<br>";

// Try to load the Auth controller
try {
    echo "Loading autoloader...<br>";
    require_once __DIR__ . '/vendor/autoload.php';
    
    echo "Loading config...<br>";
    $config = require __DIR__ . '/config/config.php';
    
    echo "Testing database...<br>";
    $db = CMS\Utils\Database::getInstance($config['database']);
    echo "✓ Database connected<br>";
    
    echo "Creating Auth utility...<br>";
    $auth = new CMS\Utils\Auth($db, $config['security']);
    
    echo "Testing auth->check()...<br>";
    $isLoggedIn = $auth->check();
    echo "Auth check result: " . ($isLoggedIn ? 'true (WOULD REDIRECT!)' : 'false') . "<br>";
    
    echo "Testing BaseController auth check...<br>";
    $baseAuth = isset($_SESSION['user_id']) && 
                isset($_SESSION['logged_in']) && 
                $_SESSION['logged_in'] === true;
    echo "BaseController auth: " . ($baseAuth ? 'true (WOULD REDIRECT!)' : 'false') . "<br>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>Error: " . $e->getMessage() . "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>Actions</h2>";
echo '<a href="?clear_session=1">Clear Session</a> | ';
echo '<a href="/admin">Test /admin</a> | ';
echo '<a href="/admin/login">Test /admin/login</a>';

if (isset($_GET['clear_session'])) {
    $_SESSION = [];
    session_destroy();
    echo "<div style='color: green;'>Session cleared!</div>";
}

echo "</body></html>";
?>