<?php
// Clear all possible caches
echo "=== Clearing All Caches ===\n\n";

$agentUrl = 'https://dalthaus.net/agent.php';
$key = 'dalthaus_agent_key_2025';

// 1. Clear application cache
echo "1. Clearing application cache...\n";
$clearUrl = $agentUrl . '?action=clear_cache&key=' . $key;
$response = @file_get_contents($clearUrl);
if ($response) {
    echo "   ✓ Cache cleared\n";
}

// 2. Create a cache buster for the admin
echo "\n2. Creating cache buster redirect...\n";
$cacheBuster = '<?php
// Cache buster redirect
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Location: /admin?cb=" . time());
exit;
?>';

file_put_contents('admin_refresh.php', $cacheBuster);
exec('git add admin_refresh.php && git commit -m "Add cache buster" && git push origin main 2>&1');

// Pull it
$pullUrl = $agentUrl . '?action=git_pull&key=' . $key;
@file_get_contents($pullUrl);
echo "   ✓ Cache buster deployed\n";

echo "\n3. Testing endpoints with cache bypass...\n";
$testUrls = [
    'https://dalthaus.net/admin?nocache=' . time() => 'Admin with cache bypass',
    'https://dalthaus.net/simple_db_test.php' => 'Database test'
];

foreach ($testUrls as $url => $name) {
    $headers = @get_headers($url);
    if ($headers) {
        $status = substr($headers[0], 9, 3);
        echo "   $name: $status\n";
    }
}

echo "\n✅ Try these URLs:\n";
echo "1. Clear browser cache (Ctrl+Shift+R or Cmd+Shift+R)\n";
echo "2. Visit: https://dalthaus.net/admin_refresh.php\n";
echo "3. Or try incognito/private mode: https://dalthaus.net/admin\n";
?>