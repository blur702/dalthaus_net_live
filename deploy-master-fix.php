<?php
/**
 * Deploy MASTER_FIX.php directly
 */

$token = 'agent-' . date('Ymd');
$agent_url = 'https://dalthaus.net/remote-file-agent.php';

// The MASTER_FIX.php content
$master_fix_content = file_get_contents(__DIR__ . '/MASTER_FIX.php');

// Call the agent to write the file
$ch = curl_init($agent_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'action' => 'write',
    'path' => 'MASTER_FIX.php',
    'content' => $master_fix_content,
    'token' => $token
]);

$response = curl_exec($ch);
$result = json_decode($response, true);
curl_close($ch);

if ($result['success']) {
    echo "✅ Successfully deployed MASTER_FIX.php to server<br>";
    echo "Bytes written: " . $result['bytes'] . "<br><br>";
    echo "<h2>Next Step:</h2>";
    echo "<a href='https://dalthaus.net/MASTER_FIX.php' style='padding:10px 20px; background:#0f0; color:#000; text-decoration:none; font-weight:bold;'>→ Run MASTER FIX on Production</a>";
} else {
    echo "❌ Failed to deploy: " . ($result['error'] ?? 'Unknown error');
}
?>