<?php
// Diagnose 503 error on admin page
echo "=== Diagnosing 503 Error ===\n\n";

$agentUrl = 'https://dalthaus.net/agent.php';
$key = 'dalthaus_agent_key_2025';

// 1. Check error logs
echo "1. Checking error logs (last 20 lines)...\n";
$errorUrl = $agentUrl . '?action=error_log&key=' . $key . '&lines=20';
$response = @file_get_contents($errorUrl);
if ($response) {
    $data = json_decode($response, true);
    if ($data['success'] && !empty($data['log'])) {
        foreach ($data['log'] as $line) {
            echo "   $line\n";
        }
    } else {
        echo "   No errors in log\n";
    }
}

// 2. Check if maintenance mode is enabled
echo "\n2. Checking maintenance mode setting...\n";
$checkUrl = 'https://dalthaus.net/diagnose.php';
$response = @file_get_contents($checkUrl);
if ($response && strpos($response, 'Maintenance Mode:') !== false) {
    if (strpos($response, 'Not in maintenance mode') !== false) {
        echo "   ✓ Not in maintenance mode\n";
    } else {
        echo "   ⚠ Maintenance mode may be enabled\n";
    }
}

// 3. Check system info
echo "\n3. Checking system status...\n";
$sysUrl = $agentUrl . '?action=system_info&key=' . $key;
$response = @file_get_contents($sysUrl);
if ($response) {
    $data = json_decode($response, true);
    if ($data['success']) {
        echo "   PHP: " . $data['info']['php_version'] . "\n";
        echo "   Memory: " . number_format($data['info']['memory_usage'] / 1024 / 1024, 2) . " MB\n";
        echo "   Free disk: " . number_format($data['info']['disk_free'] / 1024 / 1024 / 1024, 2) . " GB\n";
    }
}

// 4. Test different endpoints
echo "\n4. Testing different endpoints...\n";
$endpoints = [
    '/' => 'Homepage',
    '/diagnose.php' => 'Diagnose',
    '/admin' => 'Admin',
    '/admin/login' => 'Admin Login'
];

foreach ($endpoints as $path => $name) {
    $url = 'https://dalthaus.net' . $path;
    $headers = @get_headers($url);
    $status = $headers ? substr($headers[0], 9, 3) : 'ERR';
    echo "   $name ($path): $status\n";
}

// 5. Check if it's a session issue
echo "\n5. Testing without session...\n";
$testUrl = 'https://dalthaus.net/test_nosession.php';
$testScript = '<?php
// Test without session
header("Content-Type: text/plain");
echo "No session test\n";
require_once __DIR__ . "/vendor/autoload.php";
$config = require __DIR__ . "/config/config.php";
echo "Config loaded\n";
$db = CMS\Utils\Database::getInstance($config["database"]);
echo "Database connected\n";
?>';

// Create test script locally
file_put_contents('test_nosession.php', $testScript);
echo "   Created test_nosession.php - needs to be deployed\n";

echo "\n=== Diagnosis Complete ===\n";
echo "\nThe 503 error is likely due to:\n";
echo "1. Settings model failing to load maintenance_mode setting\n";
echo "2. Database query error in Settings::getBool()\n";
echo "3. Session initialization issue\n";
?>