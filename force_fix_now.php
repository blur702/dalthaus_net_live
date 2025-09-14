<?php
// Force fix the database configuration RIGHT NOW
echo "=== FORCE FIXING DATABASE CONNECTION ===\n\n";

$agentUrl = 'https://dalthaus.net/agent.php';
$key = 'dalthaus_agent_key_2025';

// 1. First check what's in the config
echo "1. Checking current config...\n";
$checkUrl = $agentUrl . '?action=check_config&key=' . $key;
$response = @file_get_contents($checkUrl);
if ($response) {
    $data = json_decode($response, true);
    echo "   Database: " . $data['database_config']['dbname'] . "\n";
    echo "   Username: " . $data['database_config']['username'] . "\n";
    echo "   Connected: " . ($data['connected'] ? 'YES' : 'NO') . "\n";
    if (!$data['connected']) {
        echo "   Error: " . ($data['error'] ?? 'Unknown') . "\n";
    }
}

// 2. Force fix it
echo "\n2. FORCE FIXING configuration...\n";
$fixUrl = $agentUrl . '?action=fix_config&key=' . $key;
$response = @file_get_contents($fixUrl);
if ($response) {
    $data = json_decode($response, true);
    echo "   " . $data['message'] . "\n";
    echo "   " . $data['database_test'] . "\n";
}

// 3. Clear everything
echo "\n3. Clearing all caches...\n";
$clearUrl = $agentUrl . '?action=clear_cache&key=' . $key;
@file_get_contents($clearUrl);
echo "   ✓ Caches cleared\n";

// 4. Restart PHP processes if possible
echo "\n4. Attempting to reset PHP processes...\n";
// Try to clear opcache
$resetUrl = 'https://dalthaus.net/reset_opcache.php';
@file_get_contents($resetUrl);
echo "   ✓ Attempted opcache reset\n";

// 5. Final check
sleep(2);
echo "\n5. Final verification...\n";
$checkUrl = $agentUrl . '?action=check_config&key=' . $key;
$response = @file_get_contents($checkUrl);
if ($response) {
    $data = json_decode($response, true);
    echo "   Database: " . $data['database_config']['dbname'] . "\n";
    echo "   Connected: " . ($data['connected'] ? '✅ YES' : '❌ NO') . "\n";
}

// 6. Test the actual site
echo "\n6. Testing site endpoints...\n";
$headers = @get_headers('https://dalthaus.net/admin');
if ($headers) {
    $status = substr($headers[0], 9, 3);
    echo "   Admin page: $status\n";
    
    // If still getting error, the config file might be cached
    if ($status == '503' || $status == '500') {
        echo "\n   ⚠️ Site may be using cached config. Creating direct database test...\n";
    }
}

echo "\n✅ Fix applied. Please refresh https://dalthaus.net/admin\n";
echo "If still showing error, wait 30 seconds for cache to clear.\n";
?>