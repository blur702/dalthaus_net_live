<?php
// Pull latest changes on the server via agent
echo "=== Pulling Latest Changes via Agent ===\n\n";

$agentUrl = 'https://dalthaus.net/agent.php';
$key = 'dalthaus_agent_key_2025';

// 1. Check current status first
echo "1. Checking git status before pull...\n";
$statusUrl = $agentUrl . '?action=git_status&key=' . $key;
$statusResponse = @file_get_contents($statusUrl);

if ($statusResponse) {
    $statusData = json_decode($statusResponse, true);
    if ($statusData && $statusData['success']) {
        if (empty($statusData['output'])) {
            echo "   Working directory clean\n";
        } else {
            echo "   Modified/untracked files:\n";
            foreach ($statusData['output'] as $line) {
                echo "     $line\n";
            }
        }
    }
}

// 2. Pull latest changes
echo "\n2. Pulling latest changes from GitHub...\n";
$pullUrl = $agentUrl . '?action=git_pull&key=' . $key;
$pullResponse = @file_get_contents($pullUrl);

if ($pullResponse) {
    $pullData = json_decode($pullResponse, true);
    if ($pullData && $pullData['success']) {
        echo "   ✓ Git pull successful!\n";
        echo "   Output:\n";
        foreach ($pullData['output'] as $line) {
            echo "     $line\n";
        }
    } else {
        echo "   ✗ Git pull failed\n";
        if (isset($pullData['output'])) {
            foreach ($pullData['output'] as $line) {
                echo "     $line\n";
            }
        }
    }
} else {
    echo "   ✗ Could not connect to agent\n";
}

// 3. Show recent commits
echo "\n3. Recent commits on server:\n";
$logUrl = $agentUrl . '?action=git_log&key=' . $key;
$logResponse = @file_get_contents($logUrl);

if ($logResponse) {
    $logData = json_decode($logResponse, true);
    if ($logData && $logData['success']) {
        $count = 0;
        foreach ($logData['output'] as $line) {
            echo "   $line\n";
            if (++$count >= 5) break;
        }
    }
}

echo "\n✅ Operation complete!\n";
?>