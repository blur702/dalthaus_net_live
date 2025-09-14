<?php
// Simple script to trigger git pull via agent
$url = 'https://dalthaus.net/agent.php';
$data = [
    'action' => 'git_pull',
    'key' => 'dalthaus_agent_key_2025'
];

$options = [
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => json_encode($data),
        'timeout' => 30
    ]
];

$context = stream_context_create($options);
$result = @file_get_contents($url, false, $context);

if ($result === false) {
    echo "Failed to connect to agent. The agent might have errors.\n";
    echo "Please manually run on the server:\n";
    echo "cd /home/dalthaus/public_html/www\n";
    echo "git pull origin main\n";
} else {
    $response = json_decode($result, true);
    if ($response && $response['success']) {
        echo "Git pull successful!\n";
        print_r($response);
    } else {
        echo "Git pull failed:\n";
        print_r($response);
    }
}
?>