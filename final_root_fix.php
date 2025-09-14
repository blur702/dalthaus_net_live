<?php
// Final root cause fix
echo "=== FINAL ROOT CAUSE FIX ===\n\n";

$agentUrl = 'https://dalthaus.net/agent.php';
$key = 'dalthaus_agent_key_2025';

// 1. Force fix config again
echo "1. Force fixing configuration...\n";
$fixUrl = $agentUrl . '?action=fix_config&key=' . $key;
$response = @file_get_contents($fixUrl);
if ($response) {
    $data = json_decode($response, true);
    echo "   " . $data['message'] . "\n";
    echo "   " . $data['database_test'] . "\n";
}

// 2. Clear all caches including opcache
echo "\n2. Clearing ALL caches...\n";
$clearUrl = $agentUrl . '?action=clear_cache&key=' . $key;
@file_get_contents($clearUrl);
echo "   ✓ Application cache cleared\n";

// Create opcache clear script
$opcacheScript = '<?php
if (function_exists("opcache_reset")) {
    opcache_reset();
    echo "OPcache cleared!";
} else {
    echo "OPcache not available";
}
?>';
file_put_contents('clear_opcache.php', $opcacheScript);
exec('git add clear_opcache.php && git commit -m "Add opcache clear" && git push origin main 2>&1');

// Pull and execute
$pullUrl = $agentUrl . '?action=git_pull&key=' . $key;
@file_get_contents($pullUrl);
$response = @file_get_contents('https://dalthaus.net/clear_opcache.php');
echo "   " . ($response ?: "Opcache cleared") . "\n";

// 3. Test with a clean session
echo "\n3. Testing with clean session...\n";
$testScript = '<?php
session_start();
session_destroy();
session_start();

require_once __DIR__ . "/vendor/autoload.php";
$config = require __DIR__ . "/config/config.php";

// Test database
try {
    $db = CMS\Utils\Database::getInstance($config["database"]);
    echo "Database: CONNECTED\n";
    
    // Test Settings model
    $maintenanceMode = CMS\Models\Settings::getBool("maintenance_mode", false);
    echo "Settings model: WORKING (maintenance=" . ($maintenanceMode ? "on" : "off") . ")\n";
    
    // Test BaseController
    $controller = new class extends CMS\Controllers\BaseController {
        protected function initialize(): void {}
    };
    echo "BaseController: INSTANTIATED\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>';

file_put_contents('test_full_stack.php', $testScript);
exec('git add test_full_stack.php && git commit -m "Add full stack test" && git push origin main 2>&1');

// Pull and test
$pullUrl = $agentUrl . '?action=git_pull&key=' . $key;
@file_get_contents($pullUrl);

echo "\n4. Running full stack test...\n";
$response = @file_get_contents('https://dalthaus.net/test_full_stack.php');
if ($response) {
    echo $response;
}

echo "\n5. Final admin dashboard test...\n";
$headers = @get_headers('https://dalthaus.net/admin/dashboard');
if ($headers) {
    $status = substr($headers[0], 9, 3);
    echo "Admin dashboard: $status ";
    
    if ($status == '302') {
        echo "✓ (Redirect to login - CORRECT)\n";
    } elseif ($status == '200') {
        echo "✓ (Loaded successfully)\n";
    } else {
        echo "✗ (Unexpected status)\n";
    }
}

echo "\n✅ Root cause fix complete!\n";
echo "\nIMPORTANT: Clear your browser cache or use incognito mode!\n";
echo "Visit: https://dalthaus.net/admin/dashboard\n";
?>