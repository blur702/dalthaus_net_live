<?php
// Simple agent pull request
$url = 'https://dalthaus.net/agent.php';

// Send POST request with the API key in the body
$data = json_encode([
    'action' => 'git_pull',
    'key' => 'dalthaus_agent_key_2025'
]);

$options = [
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\n",
        'content' => $data,
        'timeout' => 30,
        'ignore_errors' => true // Get response even on HTTP error codes
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false
    ]
];

$context = stream_context_create($options);
$response = @file_get_contents($url, false, $context);

// Check HTTP response headers
$http_response_header = $http_response_header ?? [];
$status_line = $http_response_header[0] ?? '';

echo "=== Agent Communication Test ===\n";
echo "Status: $status_line\n\n";

if ($response === false) {
    echo "Failed to connect to agent.\n";
    echo "HTTP Headers:\n";
    print_r($http_response_header);
} else {
    echo "Response received:\n";
    $decoded = json_decode($response, true);
    if ($decoded) {
        print_r($decoded);
    } else {
        echo "Raw response: $response\n";
    }
}

// Also try a simpler test
echo "\n\n=== Testing basic agent connectivity ===\n";
$test_response = @file_get_contents('https://dalthaus.net/agent.php?test=1');
if ($test_response !== false) {
    echo "Agent responded to test request:\n$test_response\n";
} else {
    echo "Agent did not respond to test request\n";
}
?>