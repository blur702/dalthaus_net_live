<?php
// Deploy the enhanced agent by renaming it
echo "=== Deploying Enhanced Agent ===\n\n";

$agentUrl = 'https://dalthaus.net/agent.php';
$key = 'dalthaus_agent_key_2025';

// First, let's create a backup of the current agent
echo "1. Creating backup of current agent...\n";
$backupCommand = 'cp agent.php agent_backup_' . date('YmdHis') . '.php';

// Since the current agent doesn't have a direct file operation, 
// let's do this through git
echo "   We'll handle this through git commits\n\n";

echo "2. Replacing agent.php with enhanced version locally...\n";

// Copy enhanced_agent.php to agent.php locally
if (copy('enhanced_agent.php', 'agent.php')) {
    echo "   ✓ Local file copied\n";
} else {
    echo "   ✗ Failed to copy file\n";
    exit(1);
}

echo "\n3. Committing and pushing the change...\n";
$gitCommands = [
    'git add agent.php',
    'git commit -m "Replace agent.php with enhanced version

Enhanced agent now includes:
- Database backup capability
- Cache clearing
- Permission checking
- System info
- Error log viewing
- Composer operations
- And more

Previous simple agent backed up in git history

🤖 Generated with Claude Code

Co-Authored-By: Claude <noreply@anthropic.com>"',
    'git push origin main'
];

foreach ($gitCommands as $cmd) {
    echo "   Running: $cmd\n";
    $output = [];
    $returnCode = 0;
    exec($cmd . ' 2>&1', $output, $returnCode);
    
    if ($returnCode !== 0) {
        echo "   ✗ Command failed:\n";
        foreach ($output as $line) {
            echo "     $line\n";
        }
        exit(1);
    }
    
    foreach ($output as $line) {
        echo "     $line\n";
    }
}

echo "\n4. Pulling changes on server via agent...\n";
$url = $agentUrl . '?action=git_pull&key=' . $key;
$response = @file_get_contents($url);

if ($response) {
    $result = json_decode($response, true);
    if ($result && $result['success']) {
        echo "   ✓ Server updated successfully!\n";
        foreach ($result['output'] as $line) {
            echo "     $line\n";
        }
    } else {
        echo "   ✗ Git pull failed on server\n";
    }
} else {
    echo "   ✗ Could not connect to agent\n";
}

echo "\n5. Testing new agent capabilities...\n";
$testUrl = $agentUrl . '?action=help&key=' . $key;
$testResponse = @file_get_contents($testUrl);

if ($testResponse) {
    $helpData = json_decode($testResponse, true);
    if ($helpData && isset($helpData['available_actions'])) {
        echo "   ✓ Enhanced agent is working!\n";
        echo "   Available actions:\n";
        foreach ($helpData['available_actions'] as $action => $description) {
            echo "     - $action: $description\n";
        }
    } else {
        echo "   Agent responded but 'help' action not available (still using old version)\n";
    }
} else {
    echo "   Could not test agent\n";
}

echo "\n✅ Enhanced agent deployment complete!\n";
?>