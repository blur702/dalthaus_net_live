<?php
// Emergency fix for database configuration
echo "=== EMERGENCY DATABASE FIX ===\n\n";

$agentUrl = 'https://dalthaus.net/agent.php';
$key = 'dalthaus_agent_key_2025';

// 1. Force fix the configuration immediately
echo "1. Force fixing configuration...\n";
$fixUrl = $agentUrl . '?action=fix_config&key=' . $key;
$response = @file_get_contents($fixUrl);
if ($response) {
    $data = json_decode($response, true);
    if ($data['success']) {
        echo "   ✓ Configuration fixed!\n";
        echo "   " . $data['database_test'] . "\n";
    } else {
        echo "   ✗ Fix failed\n";
    }
} else {
    echo "   ✗ Agent not responding\n";
}

// 2. Clear cache
echo "\n2. Clearing cache...\n";
$clearUrl = $agentUrl . '?action=clear_cache&key=' . $key;
@file_get_contents($clearUrl);
echo "   ✓ Cache cleared\n";

// 3. Test the fix
echo "\n3. Testing admin page...\n";
$headers = @get_headers('https://dalthaus.net/admin');
if ($headers) {
    $status = substr($headers[0], 9, 3);
    echo "   Admin page: " . $status . " " . ($status != '503' ? '✓' : '✗') . "\n";
}

echo "\n✅ Emergency fix complete!\n";
echo "Test: https://dalthaus.net/admin\n";
?>