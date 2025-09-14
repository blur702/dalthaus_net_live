<?php
// Trace what happens at /admin/login
header("Content-Type: text/plain");

$path = $_SERVER["REQUEST_URI"] ?? "";
echo "Current path: $path\n\n";

// Start session to check state
session_start();
echo "Session state:\n";
echo "  ID: " . session_id() . "\n";
echo "  user_id: " . ($_SESSION["user_id"] ?? "not set") . "\n";
echo "  logged_in: " . ($_SESSION["logged_in"] ?? "not set") . "\n";
echo "  is_admin: " . ($_SESSION["is_admin"] ?? "not set") . "\n\n";

// Load the Auth controller to see what it would do
require_once __DIR__ . "/vendor/autoload.php";
$config = require __DIR__ . "/config/config.php";

try {
    echo "Testing Auth controller logic:\n";
    
    // Check what the Auth controller login method would do
    $authFile = __DIR__ . "/src/Controllers/Admin/Auth.php";
    $authContent = file_get_contents($authFile);
    
    // Extract the login method logic
    if (preg_match('/public function login\(\)[^{]*{([^}]+\n[^}]*)*}/s', $authContent, $matches)) {
        echo "  Login method found\n";
        
        // Check for redirect conditions
        if (strpos($matches[0], '$_SESSION["user_id"]') !== false) {
            echo "  - Checks $_SESSION[\"user_id\"]\n";
        }
        if (strpos($matches[0], '$_SESSION["logged_in"]') !== false) {
            echo "  - Checks $_SESSION[\"logged_in\"]\n";
        }
        if (strpos($matches[0], '$this->auth->check()') !== false) {
            echo "  - Calls $this->auth->check()\n";
        }
        if (strpos($matches[0], 'redirect("/admin/dashboard")') !== false) {
            echo "  - Would redirect to /admin/dashboard if authenticated\n";
        }
    }
    
    // Actually test the conditions
    echo "\nActual checks:\n";
    
    $db = CMS\Utils\Database::getInstance($config["database"]);
    
    // Check session-based auth
    $sessionAuth = isset($_SESSION["user_id"]) && 
                  isset($_SESSION["logged_in"]) && 
                  $_SESSION["logged_in"] === true &&
                  isset($_SESSION["is_admin"]) &&
                  $_SESSION["is_admin"] === true;
    
    echo "  Session-based auth: " . ($sessionAuth ? "TRUE - WOULD REDIRECT!" : "false") . "\n";
    
    // Check Auth utility
    $auth = new CMS\Utils\Auth($db, $config["security"]);
    $authCheck = $auth->check();
    echo "  Auth->check(): " . ($authCheck ? "TRUE - WOULD REDIRECT!" : "false") . "\n";
    
    if ($sessionAuth || $authCheck) {
        echo "\n⚠️ LOGIN PAGE WOULD REDIRECT TO DASHBOARD!\n";
        echo "This causes a loop if dashboard redirects back to login.\n";
    } else {
        echo "\n✓ Login page would show normally (no redirect)\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Check for stale session cookie
echo "\nChecking for stale session cookie:\n";
if (isset($_COOKIE[session_name()])) {
    echo "  Cookie exists: " . session_name() . " = " . substr($_COOKIE[session_name()], 0, 20) . "...\n";
    
    // Check if session file exists
    $sessionFile = session_save_path() . "/sess_" . $_COOKIE[session_name()];
    if (file_exists($sessionFile)) {
        echo "  Session file exists\n";
        $sessionData = file_get_contents($sessionFile);
        if (empty($sessionData)) {
            echo "  ⚠️ Session file is EMPTY (stale session)\n";
        } else {
            echo "  Session file has data: " . strlen($sessionData) . " bytes\n";
        }
    } else {
        echo "  ⚠️ Session file MISSING (orphaned cookie)\n";
    }
}

echo "\n✅ Trace complete\n";
?>