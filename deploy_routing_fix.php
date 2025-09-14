<?php
// Deploy routing fixes to production server
header('Content-Type: text/plain');

$agentUrl = 'https://dalthaus.net/agent.php';
$apiKey = 'your-secure-api-key-here'; // Same key as in agent.php

echo "=== Deploying Routing Fixes to Production ===\n\n";

// Pull latest changes from Git
echo "1. Pulling latest changes from Git...\n";
$response = file_get_contents($agentUrl, false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\nX-API-Key: $apiKey\n",
        'content' => json_encode([
            'action' => 'git_pull'
        ])
    ]
]));

$result = json_decode($response, true);
if ($result['status'] === 'success') {
    echo "✓ Git pull successful!\n";
    echo "Output: " . implode("\n", $result['output']) . "\n\n";
} else {
    echo "✗ Git pull failed: " . $result['message'] . "\n\n";
}

// Clear any caches
echo "2. Clearing opcache...\n";
$response = file_get_contents($agentUrl, false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\nX-API-Key: $apiKey\n",
        'content' => json_encode([
            'action' => 'exec',
            'command' => 'php -r "if(function_exists(\'opcache_reset\')) { opcache_reset(); echo \'OPcache cleared\'; } else { echo \'OPcache not available\'; }"'
        ])
    ]
]));

$result = json_decode($response, true);
if ($result['status'] === 'success') {
    echo "✓ " . implode("\n", $result['output']) . "\n\n";
}

// Test the routes
echo "3. Testing routes on production...\n";
$testUrls = [
    '/' => 'Homepage',
    '/admin' => 'Admin Dashboard (redirect)',
    '/admin/login' => 'Admin Login',
    '/articles' => 'Articles List',
    '/photobooks' => 'Photobooks List'
];

foreach ($testUrls as $url => $description) {
    $testUrl = 'https://dalthaus.net' . $url;
    $headers = get_headers($testUrl, 1);
    $statusCode = substr($headers[0], 9, 3);
    
    echo sprintf(
        "  %-20s %-30s %s\n",
        $url,
        $description,
        $statusCode === '404' ? '✗ 404' : ($statusCode === '200' ? '✓ 200' : '⚠ ' . $statusCode)
    );
}

echo "\n✅ Deployment complete!\n";
echo "\nYou can now test the site at https://dalthaus.net/\n";
echo "Admin login: https://dalthaus.net/admin/login\n";
?>