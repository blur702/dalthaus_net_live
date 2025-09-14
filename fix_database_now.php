<?php
// Fix database configuration using enhanced agent
echo "=== Fixing Database Configuration ===\n\n";

$agentUrl = 'https://dalthaus.net/agent.php';
$key = 'dalthaus_agent_key_2025';

// 1. Pull latest agent changes
echo "1. Pulling latest agent changes...\n";
$pullUrl = $agentUrl . '?action=git_pull&key=' . $key;
$response = @file_get_contents($pullUrl);
if ($response) {
    $data = json_decode($response, true);
    if ($data['success']) {
        echo "   ✓ Agent updated\n";
    }
}

// 2. Check current config
echo "\n2. Checking current config...\n";
$checkUrl = $agentUrl . '?action=check_config&key=' . $key;
$response = @file_get_contents($checkUrl);
if ($response) {
    $data = json_decode($response, true);
    echo "   Database: " . $data['database_config']['dbname'] . "\n";
    echo "   Username: " . $data['database_config']['username'] . "\n";
    echo "   Connected: " . ($data['connected'] ? 'YES' : 'NO') . "\n";
    if (!$data['connected']) {
        echo "   Error: " . $data['error'] . "\n";
    }
}

// 3. Fix the config
echo "\n3. Fixing configuration...\n";
$fixUrl = $agentUrl . '?action=fix_config&key=' . $key;
$response = @file_get_contents($fixUrl);
if ($response) {
    $data = json_decode($response, true);
    if ($data['success']) {
        echo "   ✓ Config fixed!\n";
        echo "   Backup: " . $data['backup_created'] . "\n";
        echo "   Database: " . $data['database_test'] . "\n";
        echo "   Connected: " . ($data['database_connected'] ? 'YES' : 'NO') . "\n";
    } else {
        echo "   ✗ Fix failed\n";
    }
} else {
    echo "   ✗ Agent not responding\n";
}

// 4. Clear cache
echo "\n4. Clearing cache...\n";
$cacheUrl = $agentUrl . '?action=clear_cache&key=' . $key;
$response = @file_get_contents($cacheUrl);
if ($response) {
    $data = json_decode($response, true);
    if ($data['success']) {
        echo "   ✓ Cache cleared\n";
    }
}

// 5. Test the admin page
echo "\n5. Testing admin page...\n";
$adminUrl = 'https://dalthaus.net/admin';
$headers = @get_headers($adminUrl);
if ($headers && strpos($headers[0], '200') !== false) {
    echo "   ✓ Admin page loads (200 OK)\n";
} else {
    echo "   Status: " . ($headers[0] ?? 'No response') . "\n";
}

echo "\n✅ Configuration fix complete!\n";
echo "Test the admin page at: https://dalthaus.net/admin\n";
?>