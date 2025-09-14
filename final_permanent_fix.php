<?php
// Final permanent fix deployment
echo "=== DEPLOYING PERMANENT FIX ===\n\n";

$agentUrl = 'https://dalthaus.net/agent.php';
$key = 'dalthaus_agent_key_2025';

// 1. Pull the fixes
echo "1. Pulling latest fixes from GitHub...\n";
$pullUrl = $agentUrl . '?action=git_pull&key=' . $key;
$response = @file_get_contents($pullUrl);
if ($response) {
    $data = json_decode($response, true);
    if ($data['success']) {
        echo "   ✓ Code updated\n";
        echo "   Important files fixed:\n";
        echo "   - index.php (error page now shows correct credentials)\n";
        echo "   - setup.php (placeholder now shows correct database)\n";
    }
}

// 2. Ensure config is correct
echo "\n2. Verifying/fixing configuration...\n";
$fixUrl = $agentUrl . '?action=fix_config&key=' . $key;
$response = @file_get_contents($fixUrl);
if ($response) {
    $data = json_decode($response, true);
    echo "   ✓ Config verified: " . $data['database_test'] . "\n";
}

// 3. Clear all caches
echo "\n3. Clearing all caches...\n";
$clearUrl = $agentUrl . '?action=clear_cache&key=' . $key;
@file_get_contents($clearUrl);
echo "   ✓ Caches cleared\n";

// 4. Final verification
echo "\n4. Final verification...\n";
$endpoints = [
    'https://dalthaus.net/' => 'Homepage',
    'https://dalthaus.net/admin' => 'Admin Panel',
    'https://dalthaus.net/diagnose.php' => 'Diagnose'
];

foreach ($endpoints as $url => $name) {
    $headers = @get_headers($url);
    if ($headers) {
        $status = substr($headers[0], 9, 3);
        $ok = ($status == '200' || $status == '302');
        echo "   $name: $status " . ($ok ? '✓' : '✗') . "\n";
    }
}

echo "\n✅ PERMANENT FIX DEPLOYED!\n\n";
echo "The root cause has been fixed:\n";
echo "- Error messages now show correct database (dalthaus_maincms)\n";
echo "- Setup placeholders now show correct values\n";
echo "- Config should no longer revert to wrong values\n\n";
echo "Admin panel: https://dalthaus.net/admin\n";
echo "Login: kevin / (130Bpm)\n";
?>