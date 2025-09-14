<?php
// Comprehensive redirect loop diagnosis
echo "=== COMPREHENSIVE REDIRECT LOOP DIAGNOSIS ===\n\n";

$agentUrl = 'https://dalthaus.net/agent.php';
$key = 'dalthaus_agent_key_2025';

// Create comprehensive diagnostic script
$diagnostic = '<?php
header("Content-Type: text/plain");
echo "=== REDIRECT LOOP DIAGNOSTIC ===\n\n";

// 1. Check Auth controller login method
echo "1. AUTH CONTROLLER LOGIN METHOD CHECK:\n";
$authFile = __DIR__ . "/src/Controllers/Admin/Auth.php";
$authContent = file_get_contents($authFile);

// Check what login() does when already authenticated
if (preg_match(\'/public function login\\(\\)[^{]*{([^}]+)}/s\', $authContent, $matches)) {
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
    if (preg_match(\'/\\\'/admin/login\\\'[^;]+/\', $routes, $match)) {
        echo "   Login route: " . trim($match[0]) . "\n";
    }
    if (preg_match(\'/\\\'/admin/authenticate\\\'[^;]+/\', $routes, $match)) {
        echo "   Authenticate route: " . trim($match[0]) . "\n";
    }
}

// 4. Check if Auth utility check() method works
echo "\n4. AUTH UTILITY CHECK:\n";
require_once __DIR__ . "/vendor/autoload.php";
$config = require __DIR__ . "/config/config.php";

try {
    $db = CMS\\Utils\\Database::getInstance($config["database"]);
    $auth = new CMS\\Utils\\Auth($db, $config["security"]);
    
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
?>';

file_put_contents('diagnose_redirect.php', $diagnostic);

// Push to GitHub
exec('git add diagnose_redirect.php && git commit -m "Add redirect loop diagnostic" && git push origin main 2>&1', $output);
echo "1. Created and pushed diagnostic script\n";

// Pull on server
sleep(2);
$pullUrl = $agentUrl . '?action=git_pull&key=' . $key;
@file_get_contents($pullUrl);
echo "2. Pulled on server\n";

// Run diagnostic
echo "\n3. Running diagnostic on server...\n";
$response = @file_get_contents('https://dalthaus.net/diagnose_redirect.php');
if ($response) {
    echo $response;
}

// Also test with curl to see actual redirects
echo "\n4. Testing actual redirect chain with curl...\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://dalthaus.net/admin/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/cookies.txt');

$redirects = [];
$url = 'https://dalthaus.net/admin/login';
$count = 0;

while ($count < 10) {
    curl_setopt($ch, CURLOPT_URL, $url);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    echo "   Request #" . ($count + 1) . " to $url: $httpCode\n";
    
    if ($httpCode == 302 || $httpCode == 301) {
        if (preg_match('/Location: (.+)/i', $response, $matches)) {
            $newUrl = trim($matches[1]);
            if (!str_starts_with($newUrl, 'http')) {
                $newUrl = 'https://dalthaus.net' . $newUrl;
            }
            echo "     → Redirects to: $newUrl\n";
            
            if ($url === $newUrl) {
                echo "     ❌ SELF-REDIRECT DETECTED!\n";
                break;
            }
            
            if (in_array($newUrl, $redirects)) {
                echo "     ❌ REDIRECT LOOP DETECTED!\n";
                echo "     Loop: " . implode(' → ', $redirects) . " → $newUrl\n";
                break;
            }
            
            $redirects[] = $url;
            $url = $newUrl;
        } else {
            break;
        }
    } else {
        break;
    }
    
    $count++;
}

curl_close($ch);

echo "\n✅ Diagnostic complete\n";
?>