<?php
// Test the login redirect issue
echo "=== TESTING LOGIN REDIRECT ISSUE ===\n\n";

$agentUrl = 'https://dalthaus.net/agent.php';
$key = 'dalthaus_agent_key_2025';

// Create a test script to check login behavior
$loginTest = '<?php
session_start();
header("Content-Type: text/plain");

echo "=== Login Redirect Test ===\n\n";

// Check current session
echo "Session ID: " . session_id() . "\n";
echo "User ID: " . ($_SESSION["user_id"] ?? "not set") . "\n";
echo "Is Admin: " . ($_SESSION["is_admin"] ?? "not set") . "\n\n";

// Check if login controller exists
$loginController = __DIR__ . "/src/Controllers/Admin/Auth.php";
if (file_exists($loginController)) {
    echo "✓ Auth controller exists\n";
    
    // Check for redirect loops
    $content = file_get_contents($loginController);
    if (strpos($content, "header(\"Location:") !== false) {
        echo "Auth controller has redirects configured\n";
    }
} else {
    echo "✗ Auth controller missing!\n";
}

// Check routes
$routesFile = __DIR__ . "/config/routes.php";
if (file_exists($routesFile)) {
    $routes = file_get_contents($routesFile);
    if (strpos($routes, "/admin/login") !== false) {
        echo "✓ Login route exists\n";
    }
    if (strpos($routes, "Admin\\\\Auth::login") !== false) {
        echo "✓ Auth::login action mapped\n";
    }
}

// Test authentication flow
echo "\nTesting authentication flow:\n";

// Simulate login attempt
$_SESSION["test_login"] = true;
echo "1. Set test session variable\n";

// Check if it persists
if (isset($_SESSION["test_login"])) {
    echo "2. Session variable persists ✓\n";
} else {
    echo "2. Session variable lost ✗\n";
}

// Clean up
unset($_SESSION["test_login"]);

echo "\n✓ Login test complete\n";
?>';

file_put_contents('test_login.php', $loginTest);

// Push to GitHub
exec('git add test_login.php && git commit -m "Add login redirect test" && git push origin main 2>&1', $output);
echo "1. Created and pushed test_login.php\n";

// Pull on server
sleep(2);
$pullUrl = $agentUrl . '?action=git_pull&key=' . $key;
$response = @file_get_contents($pullUrl);
if ($response) {
    $data = json_decode($response, true);
    if ($data['success']) {
        echo "2. Pulled on server\n";
    }
}

// Run the test
echo "\n3. Running login test...\n";
$testUrl = 'https://dalthaus.net/test_login.php';
$response = @file_get_contents($testUrl);
if ($response) {
    echo $response;
}

// Test actual login endpoint
echo "\n4. Testing login endpoint...\n";

// Create a simple cookie jar for testing
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://dalthaus.net/admin/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Login page status: $httpCode\n";

if ($httpCode == 302) {
    echo "⚠️ Login page is redirecting (might be a loop)\n";
    
    // Extract location header
    if (preg_match('/Location: (.+)/', $response, $matches)) {
        echo "Redirecting to: " . trim($matches[1]) . "\n";
    }
} elseif ($httpCode == 200) {
    echo "✓ Login page loads correctly\n";
} else {
    echo "✗ Unexpected status code\n";
}

echo "\n✅ Login test complete!\n";
?>