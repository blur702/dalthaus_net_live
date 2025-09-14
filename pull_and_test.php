<?php
// Pull and test
echo "=== Pulling and Testing ===\n\n";

$agentUrl = 'https://dalthaus.net/agent.php';
$key = 'dalthaus_agent_key_2025';

// 1. Pull
echo "1. Pulling changes...\n";
$pullUrl = $agentUrl . '?action=git_pull&key=' . $key;
$response = @file_get_contents($pullUrl);
if ($response) {
    $data = json_decode($response, true);
    if ($data['success']) {
        echo "   ✓ Pulled successfully\n";
    }
}

// 2. Test simple_db_test.php
echo "\n2. Testing database connection...\n";
$testUrl = 'https://dalthaus.net/simple_db_test.php';
$response = @file_get_contents($testUrl);
if ($response) {
    echo $response;
} else {
    echo "   Could not access test script\n";
}

echo "\n3. Checking admin page...\n";
$headers = @get_headers('https://dalthaus.net/admin');
if ($headers) {
    $status = substr($headers[0], 9, 3);
    echo "   Admin status: $status\n";
}
?>