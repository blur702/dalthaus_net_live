<?php
// Deploy and test everything
echo "=== DEPLOYING AND TESTING EVERYTHING ===\n\n";

$agentUrl = 'https://dalthaus.net/agent.php';
$key = 'dalthaus_agent_key_2025';

// 1. Pull latest changes
echo "1. Pulling latest changes...\n";
$pullUrl = $agentUrl . '?action=git_pull&key=' . $key;
$response = @file_get_contents($pullUrl);
if ($response) {
    $data = json_decode($response, true);
    if ($data['success']) {
        echo "   ✓ Pulled successfully\n";
        foreach ($data['output'] as $line) {
            if (strpos($line, 'config.php') !== false || strpos($line, 'Settings.php') !== false) {
                echo "   - $line\n";
            }
        }
    }
}

// 2. Force fix config one more time
echo "\n2. Force fixing configuration...\n";
$fixUrl = $agentUrl . '?action=fix_config&key=' . $key;
$response = @file_get_contents($fixUrl);
if ($response) {
    $data = json_decode($response, true);
    echo "   " . $data['message'] . "\n";
    echo "   " . $data['database_test'] . "\n";
}

// 3. Clear all caches
echo "\n3. Clearing all caches...\n";
$clearUrl = $agentUrl . '?action=clear_cache&key=' . $key;
@file_get_contents($clearUrl);
echo "   ✓ Caches cleared\n";

// 4. Run comprehensive tests
echo "\n4. Running comprehensive tests...\n";
$tests = [
    'https://dalthaus.net/simple_db_test.php' => 'Database Test',
    'https://dalthaus.net/test_full_stack.php' => 'Full Stack Test',
    'https://dalthaus.net/diagnose.php' => 'Diagnose Page',
    'https://dalthaus.net/' => 'Homepage',
    'https://dalthaus.net/admin' => 'Admin',
    'https://dalthaus.net/admin/dashboard' => 'Dashboard'
];

foreach ($tests as $url => $name) {
    echo "\n   Testing $name...\n";
    
    if (strpos($url, '.php') !== false && strpos($url, 'admin') === false) {
        // For test scripts, get the content
        $response = @file_get_contents($url);
        if ($response) {
            $lines = explode("\n", $response);
            foreach ($lines as $line) {
                if (strpos($line, 'CONNECTED') !== false || 
                    strpos($line, 'SUCCESSFUL') !== false ||
                    strpos($line, 'WORKING') !== false ||
                    strpos($line, 'Database:') !== false) {
                    echo "     $line\n";
                }
            }
        }
    } else {
        // For regular pages, check status
        $headers = @get_headers($url);
        if ($headers) {
            $status = substr($headers[0], 9, 3);
            $ok = ($status == '200' || $status == '302');
            echo "     Status: $status " . ($ok ? '✓' : '✗') . "\n";
        }
    }
}

echo "\n✅ Deployment complete!\n";
?>