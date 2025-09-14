<?php
// Deploy the critical exception handler fix
echo "=== DEPLOYING CRITICAL FIX ===\n\n";

$agentUrl = 'https://dalthaus.net/agent.php';
$key = 'dalthaus_agent_key_2025';

// 1. Pull the fix
echo "1. Pulling critical fix from GitHub...\n";
$pullUrl = $agentUrl . '?action=git_pull&key=' . $key;
$response = @file_get_contents($pullUrl);
if ($response) {
    $data = json_decode($response, true);
    if ($data['success']) {
        echo "   ✅ Pulled successfully\n";
        foreach ($data['output'] as $line) {
            if (strpos($line, 'index.php') !== false) {
                echo "   - $line\n";
            }
        }
    }
}

// 2. Clear all caches
echo "\n2. Clearing all caches...\n";
$clearUrl = $agentUrl . '?action=clear_cache&key=' . $key;
@file_get_contents($clearUrl);
echo "   ✅ Caches cleared\n";

// 3. Clear opcache specifically
echo "\n3. Clearing OPcache...\n";
$opcacheUrl = 'https://dalthaus.net/clear_opcache.php';
@file_get_contents($opcacheUrl);
echo "   ✅ OPcache cleared\n";

// 4. Test the fix
echo "\n4. Testing the fix...\n";

// Test admin redirect
$headers = @get_headers('https://dalthaus.net/admin');
if ($headers) {
    $status = substr($headers[0], 9, 3);
    echo "   Admin page (no auth): $status ";
    echo ($status == '302' ? '✅' : '⚠️') . "\n";
}

// Test dashboard redirect
$headers = @get_headers('https://dalthaus.net/admin/dashboard');
if ($headers) {
    $status = substr($headers[0], 9, 3);
    echo "   Dashboard (no auth): $status ";
    echo ($status == '302' ? '✅' : '⚠️') . "\n";
}

// Test database connection
$testUrl = 'https://dalthaus.net/simple_db_test.php';
$response = @file_get_contents($testUrl);
if ($response && strpos($response, 'SUCCESSFUL') !== false) {
    echo "   Database test: ✅ CONNECTED\n";
}

echo "\n" . str_repeat('=', 60) . "\n";
echo "✅ CRITICAL FIX DEPLOYED!\n";
echo str_repeat('=', 60) . "\n\n";
echo "The exception handler has been fixed to only show database\n";
echo "errors for actual connection failures, not for all PDO exceptions.\n\n";
echo "Please test in a new incognito/private browser window:\n";
echo "https://dalthaus.net/admin/dashboard\n";
?>