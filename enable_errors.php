<?php
// Enable ALL error reporting for debugging
error_reporting(E_ALL);
ini_set("display_errors", "1");
ini_set("display_startup_errors", "1");
ini_set("log_errors", "1");
ini_set("error_log", __DIR__ . "/logs/php_errors.log");

// Also set via .htaccess for persistence
$htaccess = __DIR__ . "/.htaccess";
$htaccessContent = file_get_contents($htaccess);

// Add PHP error directives if not present
if (strpos($htaccessContent, "php_flag display_errors") === false) {
    $errorDirectives = "\n# DEBUG: Verbose error reporting\n";
    $errorDirectives .= "php_flag display_errors On\n";
    $errorDirectives .= "php_flag display_startup_errors On\n";
    $errorDirectives .= "php_value error_reporting 32767\n";
    $errorDirectives .= "php_flag log_errors On\n";
    $errorDirectives .= "php_value error_log " . __DIR__ . "/logs/php_errors.log\n";
    
    // Add before the closing IfModule tag
    $htaccessContent = str_replace(
        "</IfModule>\n\n# Handle maintenance",
        $errorDirectives . "</IfModule>\n\n# Handle maintenance",
        $htaccessContent
    );
    
    file_put_contents($htaccess, $htaccessContent);
    echo "Added error directives to .htaccess\n";
}

// Create a trace script to debug the redirect
$traceScript = '<?php
// Trace execution to find redirect loop
error_reporting(E_ALL);
ini_set("display_errors", "1");

// Log every step
function debug_log($msg) {
    $timestamp = date("Y-m-d H:i:s");
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
    $caller = isset($trace[1]) ? $trace[1]["file"] . ":" . $trace[1]["line"] : "unknown";
    error_log("[$timestamp] $msg [Called from: $caller]");
    echo "<!-- DEBUG: $msg -->\n";
}

// Override header function to track redirects
$original_header = "header";
function tracked_header($header, $replace = true, $http_response_code = null) {
    debug_log("REDIRECT: $header");
    // Log stack trace
    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    foreach ($trace as $i => $call) {
        if (isset($call["file"])) {
            debug_log("  Stack[$i]: " . $call["file"] . ":" . $call["line"] . " " . ($call["function"] ?? ""));
        }
    }
    
    // Call original header
    if ($http_response_code !== null) {
        header($header, $replace, $http_response_code);
    } else {
        header($header, $replace);
    }
}

// Start output buffering to catch all output
ob_start();

debug_log("=== REQUEST START ===");
debug_log("URL: " . ($_SERVER["REQUEST_URI"] ?? "unknown"));
debug_log("Method: " . ($_SERVER["REQUEST_METHOD"] ?? "unknown"));

// Session info
session_start();
debug_log("Session ID: " . session_id());
debug_log("Session data: " . json_encode($_SESSION));

// Include the main index file
try {
    debug_log("Including index.php");
    require __DIR__ . "/index.php";
} catch (Exception $e) {
    debug_log("EXCEPTION: " . $e->getMessage());
    debug_log("Stack trace: " . $e->getTraceAsString());
    throw $e;
}

debug_log("=== REQUEST END ===");

// Flush output
ob_end_flush();
?>';

file_put_contents(__DIR__ . "/debug_trace.php", $traceScript);
echo "Created debug_trace.php\n";

// Create a simple test that bypasses everything
$simpleTest = '<?php
// Simple test to isolate the issue
error_reporting(E_ALL);
ini_set("display_errors", "1");

echo "<h1>Simple Test</h1>\n";
echo "<p>If you see this, the server is working.</p>\n";

// Test session
session_start();
echo "<h2>Session Test</h2>\n";
echo "<pre>";
print_r($_SESSION);
echo "</pre>\n";

// Test routing
echo "<h2>Route Test</h2>\n";
$uri = $_SERVER["REQUEST_URI"] ?? "";
echo "Current URI: $uri<br>\n";

if ($uri === "/admin" || $uri === "/admin/") {
    echo "Would redirect to /admin/login<br>\n";
}

if ($uri === "/admin/login" || $uri === "/admin/login/") {
    echo "Would show login form<br>\n";
    
    // Check if any session vars would cause redirect
    if (isset($_SESSION["user_id"])) {
        echo "WARNING: Session has user_id, might redirect!<br>\n";
    }
    if (isset($_SESSION["logged_in"])) {
        echo "WARNING: Session has logged_in, might redirect!<br>\n";
    }
}

// Test database
echo "<h2>Database Test</h2>\n";
try {
    require_once __DIR__ . "/vendor/autoload.php";
    $config = require __DIR__ . "/config/config.php";
    $db = CMS\Utils\Database::getInstance($config["database"]);
    echo "Database connected!<br>\n";
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage() . "<br>\n";
}

echo "<h2>Actions</h2>\n";
echo '<a href="/admin">Go to /admin</a><br>';
echo '<a href="/admin/login">Go to /admin/login</a><br>';
echo '<a href="?clear_session=1">Clear Session</a><br>';

if (isset($_GET["clear_session"])) {
    session_destroy();
    echo "<p>Session cleared!</p>\n";
}
?>';

file_put_contents(__DIR__ . "/simple_test.php", $simpleTest);
echo "Created simple_test.php\n";

echo "\n✅ Verbose error reporting enabled\n";
echo "Access these URLs to debug:\n";
echo "- https://dalthaus.net/debug_trace.php\n";
echo "- https://dalthaus.net/simple_test.php\n";
?>