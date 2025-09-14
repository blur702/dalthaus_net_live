<?php
// Direct attempt to call agent with different methods
echo "=== Attempting Direct Agent Communication ===\n\n";

// Method 1: Using curl if available
if (function_exists('curl_init')) {
    echo "Method 1: Using CURL...\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://dalthaus.net/agent.php');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'action' => 'git_pull',
        'key' => 'dalthaus_agent_key_2025'
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-API-Key: dalthaus_agent_key_2025'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    echo "HTTP Code: $httpCode\n";
    if ($error) {
        echo "CURL Error: $error\n";
    }
    if ($response) {
        echo "Response: $response\n";
        $data = json_decode($response, true);
        if ($data && isset($data['success']) && $data['success']) {
            echo "✓ Git pull successful via agent!\n";
            if (isset($data['output'])) {
                echo "Output:\n";
                print_r($data['output']);
            }
        }
    } else {
        echo "No response received\n";
    }
} else {
    echo "CURL not available\n";
}

// Method 2: Try with different authentication header
echo "\n\nMethod 2: Using file_get_contents with API key header...\n";
$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\nX-API-Key: dalthaus_agent_key_2025\r\n",
        'content' => json_encode(['action' => 'git_pull']),
        'timeout' => 30,
        'ignore_errors' => true
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false
    ]
]);

$response = @file_get_contents('https://dalthaus.net/agent.php', false, $context);
if ($response) {
    echo "Response received: $response\n";
} else {
    echo "No response\n";
}

// Method 3: Try exec with wget
echo "\n\nMethod 3: Using wget command...\n";
$wget_command = "wget -q -O - --post-data='" . json_encode(['action' => 'git_pull', 'key' => 'dalthaus_agent_key_2025']) . "' --header='Content-Type: application/json' 'https://dalthaus.net/agent.php' 2>&1";
$wget_output = shell_exec($wget_command);
if ($wget_output) {
    echo "Wget response: $wget_output\n";
} else {
    echo "No wget response\n";
}

echo "\n=== End of attempts ===\n";
?>