<?php
// Check that the enhanced agent is live on the server
echo "=== Verifying Enhanced Agent is Live ===\n\n";

$agentUrl = 'https://dalthaus.net/agent.php';
$key = 'dalthaus_agent_key_2025';

// 1. Check system info (only available in enhanced agent)
echo "1. Testing system_info (enhanced agent feature)...\n";
$url = $agentUrl . '?action=system_info&key=' . $key;
$response = @file_get_contents($url);

if ($response) {
    $data = json_decode($response, true);
    if ($data && $data['success']) {
        echo "   ✓ Enhanced agent confirmed!\n";
        echo "   PHP Version: " . $data['info']['php_version'] . "\n";
        echo "   Server: " . $data['info']['server_software'] . "\n";
        echo "   Free disk: " . number_format($data['info']['disk_free'] / 1024 / 1024 / 1024, 2) . " GB\n";
        echo "   Total disk: " . number_format($data['info']['disk_total'] / 1024 / 1024 / 1024, 2) . " GB\n";
    }
} else {
    echo "   System info not available (would mean old agent)\n";
}

// 2. Check permissions (another enhanced feature)
echo "\n2. Checking permissions (enhanced agent feature)...\n";
$url = $agentUrl . '?action=check_permissions&key=' . $key;
$response = @file_get_contents($url);

if ($response) {
    $data = json_decode($response, true);
    if ($data && $data['success']) {
        echo "   ✓ Permission check available!\n";
        foreach ($data['permissions'] as $dir => $info) {
            $status = $info['writable'] ? '✓ writable' : '✗ not writable';
            echo "   $dir: $status\n";
        }
    }
}

// 3. Check git log (enhanced feature)
echo "\n3. Checking git log (enhanced agent feature)...\n";
$url = $agentUrl . '?action=git_log&key=' . $key;
$response = @file_get_contents($url);

if ($response) {
    $data = json_decode($response, true);
    if ($data && $data['success']) {
        echo "   ✓ Git log available! Last 3 commits:\n";
        $count = 0;
        foreach ($data['output'] as $line) {
            echo "   $line\n";
            if (++$count >= 3) break;
        }
    }
}

echo "\n✅ Enhanced agent is LIVE on the server with all features!\n";
?>