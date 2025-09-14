<?php
// Check what's actually deployed on the server
echo "=== CHECKING DEPLOYED FILES ===\n\n";

$agentUrl = 'https://dalthaus.net/agent.php';
$key = 'dalthaus_agent_key_2025';

// Enhance agent to check file contents
$checkScript = '<?php
// Check deployed file contents
header("Content-Type: text/plain");

echo "=== CHECKING DEPLOYED AUTH CONTROLLER ===\n\n";

// Check Auth controller
$authFile = __DIR__ . "/src/Controllers/Admin/Auth.php";
if (file_exists($authFile)) {
    $content = file_get_contents($authFile);
    
    // Look for the login method
    if (preg_match(\'/public function login\\(\\)[^{]*{([^}]+\\n[^}]*)*}/s\', $content, $matches)) {
        echo "Login method found:\n";
        echo "-------------------\n";
        echo $matches[0];
        echo "\n-------------------\n\n";
        
        // Check what it does
        if (strpos($matches[0], \'$this->auth->check()\') !== false) {
            echo "⚠️ LOGIN METHOD STILL CHECKS AUTH!\n";
            echo "This will cause redirect loops.\n";
        } else {
            echo "✓ Login method does not check auth\n";
        }
    } else {
        echo "Could not find login method\n";
    }
} else {
    echo "Auth controller file not found!\n";
}

// Check routes
echo "\n=== CHECKING ROUTES ===\n\n";
$routesFile = __DIR__ . "/config/routes.php";
if (file_exists($routesFile)) {
    $routes = file_get_contents($routesFile);
    
    // Find login route
    if (preg_match(\'/->get\\([\\\'"]\/login[\\\'"][^)]+\\)/\', $routes, $match)) {
        echo "Login route: " . $match[0] . "\n";
    }
}

// Check if old files are cached
echo "\n=== CHECKING OPCACHE ===\n\n";
if (function_exists("opcache_get_status")) {
    $status = opcache_get_status(false);
    if ($status && isset($status["opcache_enabled"])) {
        echo "OPcache enabled: " . ($status["opcache_enabled"] ? "YES" : "NO") . "\n";
        
        if ($status["opcache_enabled"]) {
            // Reset it
            if (opcache_reset()) {
                echo "OPcache reset: SUCCESS\n";
            } else {
                echo "OPcache reset: FAILED\n";
            }
        }
    }
} else {
    echo "OPcache not available\n";
}

echo "\n✅ Check complete\n";
?>';

file_put_contents('check_deployed.php', $checkScript);

// Push to GitHub
exec('git add check_deployed.php && git commit -m "Add deployment check script" && git push origin main 2>&1', $output);
echo "1. Created and pushed check script\n";

// Pull on server
sleep(2);
$pullUrl = $agentUrl . '?action=git_pull&key=' . $key;
@file_get_contents($pullUrl);
echo "2. Pulled on server\n";

// Run check
echo "\n3. Checking deployed files...\n";
$response = @file_get_contents('https://dalthaus.net/check_deployed.php');
if ($response) {
    echo $response;
} else {
    echo "Could not run check\n";
}

echo "\n✅ Check complete\n";
?>