<?php
/**
 * Deploy Fixes Script
 * Pulls latest changes from git and verifies deployment
 */

$token = 'agent-' . date('Ymd');
$url = 'https://dalthaus.net/remote-git-agent.php';

function makeRequest($url, $data) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['code' => $httpCode, 'response' => $response];
}

echo "Deploying fixes to https://dalthaus.net\n";
echo "Token: $token\n";
echo "==========================================\n";

// 1. Check current git status
echo "1. Checking current git status...\n";
$result = makeRequest($url, ['token' => $token, 'command' => 'status']);
echo "HTTP: {$result['code']}\n";
echo "Response: {$result['response']}\n\n";

// 2. Pull latest changes
echo "2. Pulling latest changes...\n";
$result = makeRequest($url, ['token' => $token, 'command' => 'pull']);
echo "HTTP: {$result['code']}\n"; 
echo "Response: {$result['response']}\n\n";

// 3. Show deployment status
echo "3. Final git status...\n";
$result = makeRequest($url, ['token' => $token, 'command' => 'status']);
echo "HTTP: {$result['code']}\n";
echo "Response: {$result['response']}\n\n";

echo "==========================================\n";
echo "Deployment complete!\n";
echo "Next steps:\n";
echo "1. Visit https://dalthaus.net/comprehensive-test.php to verify fixes\n";
echo "2. Visit https://dalthaus.net/ to check homepage\n";
echo "3. Visit https://dalthaus.net/admin/login.php to test admin access\n";
?>