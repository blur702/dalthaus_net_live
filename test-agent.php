<?php
/**
 * Test script for remote file agent
 */

$agent_url = 'https://dalthaus.net/remote-file-agent.php';
$token = 'agent-' . date('Ymd');

echo "<h1>Remote File Agent Test</h1>\n";
echo "<pre>\n";
echo "Token: $token\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Test 1: Get server info
echo "1. Testing server info...\n";
$ch = curl_init($agent_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'action' => 'info',
    'token' => $token
]);
$response = curl_exec($ch);
$info = json_decode($response, true);
if ($info['success']) {
    echo "✅ Server info retrieved\n";
    echo "   PHP Version: " . $info['server']['php_version'] . "\n";
    echo "   Server: " . $info['server']['server_software'] . "\n\n";
} else {
    echo "❌ Failed to get server info\n\n";
}

// Test 2: Write a test file
echo "2. Testing write operation...\n";
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'action' => 'write',
    'path' => 'test-agent-file.txt',
    'content' => 'Test content from agent test script at ' . date('Y-m-d H:i:s'),
    'token' => $token
]);
$response = curl_exec($ch);
$result = json_decode($response, true);
if ($result['success']) {
    echo "✅ Successfully wrote " . $result['bytes'] . " bytes\n\n";
} else {
    echo "❌ Write failed: " . ($result['error'] ?? 'Unknown error') . "\n\n";
}

// Test 3: Read the file back
echo "3. Testing read operation...\n";
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'action' => 'read',
    'path' => 'test-agent-file.txt',
    'token' => $token
]);
$response = curl_exec($ch);
$result = json_decode($response, true);
if ($result['success']) {
    echo "✅ Successfully read file\n";
    echo "   Content: " . substr($result['content'], 0, 50) . "...\n\n";
} else {
    echo "❌ Read failed: " . ($result['error'] ?? 'Unknown error') . "\n\n";
}

// Test 4: List files
echo "4. Testing list operation...\n";
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'action' => 'list',
    'path' => '.',
    'token' => $token
]);
$response = curl_exec($ch);
$result = json_decode($response, true);
if ($result['success']) {
    echo "✅ Directory listing successful\n";
    echo "   Found " . count($result['files']) . " items\n";
    $found = false;
    foreach ($result['files'] as $file) {
        if ($file['name'] === 'test-agent-file.txt') {
            $found = true;
            break;
        }
    }
    echo "   Test file found: " . ($found ? 'Yes' : 'No') . "\n\n";
} else {
    echo "❌ List failed: " . ($result['error'] ?? 'Unknown error') . "\n\n";
}

// Test 5: Delete the test file
echo "5. Testing delete operation...\n";
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'action' => 'delete',
    'path' => 'test-agent-file.txt',
    'token' => $token
]);
$response = curl_exec($ch);
$result = json_decode($response, true);
if ($result['success']) {
    echo "✅ Successfully deleted test file\n\n";
} else {
    echo "❌ Delete failed: " . ($result['error'] ?? 'Unknown error') . "\n\n";
}

// Test 6: Verify deletion
echo "6. Verifying deletion...\n";
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'action' => 'exists',
    'path' => 'test-agent-file.txt',
    'token' => $token
]);
$response = curl_exec($ch);
$result = json_decode($response, true);
if ($result['success'] && !$result['exists']) {
    echo "✅ File successfully deleted\n\n";
} else {
    echo "❌ File still exists or check failed\n\n";
}

curl_close($ch);

echo "\n=== TEST COMPLETE ===\n";
echo "</pre>";

// Provide access URL
echo "<h2>Direct Access</h2>";
echo "<p>You can access the agent directly at:</p>";
echo "<code>https://dalthaus.net/remote-file-agent.php?action=info&token=$token</code>";
?>