<?php
header("Content-Type: text/plain");
echo "=== REDIRECT LOOP DIAGNOSTIC ===\n\n";

// 1. Check Auth controller login method
echo "1. AUTH CONTROLLER LOGIN METHOD CHECK:\n";
$authFile = __DIR__ . "/src/Controllers/Admin/Auth.php";
$authContent = file_get_contents($authFile);

// Check what login() does when already authenticated
if (preg_match('/public function login\(\)[^{]*{([^}]+)}/s', $authContent, $matches)) {
    $loginMethod = $matches[1];
    if (strpos($loginMethod, "auth->check()") !== false && strpos($loginMethod, "redirect") !== false) {
        echo "   ⚠️ Login method redirects when authenticated\n";
        if (strpos($loginMethod, "/admin/dashboard") !== false) {
            echo "   → Redirects to: /admin/dashboard\n";
        }
    }
}

// 2. Test actual session behavior
echo "\n2. SESSION BEHAVIOR TEST:\n";
session_start();
echo "   Session ID: " . session_id() . "\n";
echo "   Session save path: " . session_save_path() . "\n";
echo "   Session data: " . json_encode($_SESSION) . "\n";

// 3. Check routes configuration
echo "\n3. ROUTES CONFIGURATION:\n";
$routesFile = __DIR__ . "/config/routes.php";
if (file_exists($routesFile)) {
    $routes = file_get_contents($routesFile);
    
    // Check login routes
    if (preg_match('/\'/admin/login\'[^;]+/', $routes, $match)) {
        echo "   Login route: " . trim($match[0]) . "\n";
    }
    if (preg_match('/\'/admin/authenticate\'[^;]+/', $routes, $match)) {
        echo "   Authenticate route: " . trim($match[0]) . "\n";
    }
}

// 4. Check if Auth utility check() method works
echo "\n4. AUTH UTILITY CHECK:\n";
require_once __DIR__ . "/vendor/autoload.php";
$config = require __DIR__ . "/config/config.php";

try {
    $db = CMS\Utils\Database::getInstance($config["database"]);
    $auth = new CMS\Utils\Auth($db, $config["security"]);
    
    // Test check method
    $isLoggedIn = $auth->check();
    echo "   Auth->check() returns: " . ($isLoggedIn ? "true" : "false") . "\n";
    
    // Check what session variables are needed
    echo "   Required session vars:\n";
    echo "     - logged_in: " . (isset($_SESSION["logged_in"]) ? $_SESSION["logged_in"] : "not set") . "\n";
    echo "     - user_id: " . (isset($_SESSION["user_id"]) ? $_SESSION["user_id"] : "not set") . "\n";
    echo "     - is_admin: " . (isset($_SESSION["is_admin"]) ? $_SESSION["is_admin"] : "not set") . "\n";
    
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

// 5. Simulate the redirect flow
echo "\n5. REDIRECT FLOW SIMULATION:\n";
$path = $_SERVER["REQUEST_URI"] ?? "/admin/login";
echo "   Current path: $path\n";

// Simulate what happens at /admin/login
if ($path === "/admin/login") {
    echo "   → At login page\n";
    
    // Check if auth->check() would return true
    if (isset($_SESSION["logged_in"]) && $_SESSION["logged_in"] === true) {
        echo "   → Would redirect to /admin/dashboard (AUTH SAYS LOGGED IN)\n";
    } else {
        echo "   → Would show login form (NOT LOGGED IN)\n";
    }
}

// Simulate what happens at /admin/dashboard
if ($path === "/admin/dashboard") {
    echo "   → At dashboard\n";
    
    // Check BaseController requireAuth
    $authenticated = isset($_SESSION["user_id"]) && 
                    isset($_SESSION["logged_in"]) && 
                    $_SESSION["logged_in"] === true;
    
    if (!$authenticated) {
        echo "   → Would redirect to /admin/login (NOT AUTHENTICATED)\n";
    } else {
        echo "   → Would show dashboard (AUTHENTICATED)\n";
    }
}

echo "\n6. POTENTIAL ISSUES FOUND:\n";
$issues = [];

// Check for session mismatch
if (isset($_SESSION["logged_in"]) && !isset($_SESSION["user_id"])) {
    $issues[] = "Session has logged_in but missing user_id";
}
if (isset($_SESSION["user_id"]) && !isset($_SESSION["logged_in"])) {
    $issues[] = "Session has user_id but missing logged_in flag";
}

// Check for infinite loop condition
if (isset($_SESSION["logged_in"]) && $_SESSION["logged_in"] === true) {
    $authCheck = isset($_SESSION["user_id"]) && isset($_SESSION["logged_in"]) && $_SESSION["logged_in"] === true;
    if (!$authCheck) {
        $issues[] = "Auth->check() says logged in but BaseController would reject";
    }
}

if (empty($issues)) {
    echo "   ✓ No obvious issues found\n";
} else {
    foreach ($issues as $issue) {
        echo "   ⚠️ $issue\n";
    }
}

// Clean up
session_destroy();
echo "\n✅ Diagnostic complete\n";
?>