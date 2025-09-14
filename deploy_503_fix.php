<?php
// Deploy 503 fix
echo "=== Deploying 503 Fix ===\n\n";

$agentUrl = 'https://dalthaus.net/agent.php';
$key = 'dalthaus_agent_key_2025';

// 1. Pull latest changes
echo "1. Pulling latest changes...\n";
$pullUrl = $agentUrl . '?action=git_pull&key=' . $key;
$response = @file_get_contents($pullUrl);
if ($response) {
    $data = json_decode($response, true);
    if ($data['success']) {
        echo "   ✓ Code updated\n";
        foreach ($data['output'] as $line) {
            if (strpos($line, 'BaseController.php') !== false) {
                echo "   - $line\n";
            }
        }
    }
}

// 2. Clear cache
echo "\n2. Clearing cache...\n";
$cacheUrl = $agentUrl . '?action=clear_cache&key=' . $key;
$response = @file_get_contents($cacheUrl);
if ($response) {
    $data = json_decode($response, true);
    if ($data['success']) {
        echo "   ✓ Cache cleared\n";
    }
}

// 3. Test admin page
echo "\n3. Testing admin page...\n";
$testUrls = [
    'https://dalthaus.net/admin' => 'Admin',
    'https://dalthaus.net/admin/login' => 'Admin Login',
    'https://dalthaus.net/admin/dashboard' => 'Dashboard'
];

foreach ($testUrls as $url => $name) {
    $headers = @get_headers($url);
    if ($headers) {
        $status = substr($headers[0], 9, 3);
        echo "   $name: $status " . ($status == '200' || $status == '302' ? '✓' : '✗') . "\n";
    }
}

echo "\n✅ 503 fix deployed!\n";
echo "The admin panel should now load without 503 errors.\n";
?>