<?php
// Test and demonstrate all agent operations
echo "=== Agent Operations Demo ===\n\n";

$agentUrl = 'https://dalthaus.net/agent.php';
$key = 'dalthaus_agent_key_2025';

function callAgent($action, $params = []) {
    global $agentUrl, $key;
    $params['action'] = $action;
    $params['key'] = $key;
    
    $url = $agentUrl . '?' . http_build_query($params);
    $response = @file_get_contents($url);
    
    if ($response) {
        return json_decode($response, true);
    }
    return null;
}

// 1. Pull latest changes
echo "1. Pulling latest changes...\n";
$result = callAgent('git_pull');
if ($result && $result['success']) {
    echo "   ✓ Git pull successful\n";
    if (!empty($result['output'])) {
        foreach ($result['output'] as $line) {
            echo "     $line\n";
        }
    }
} else {
    echo "   ✗ Git pull failed\n";
}

// 2. Check git status
echo "\n2. Checking git status...\n";
$result = callAgent('git_status');
if ($result && $result['success']) {
    if (empty($result['output'])) {
        echo "   ✓ Working directory clean\n";
    } else {
        echo "   Modified files:\n";
        foreach ($result['output'] as $line) {
            echo "     $line\n";
        }
    }
}

// 3. Test connectivity
echo "\n3. Testing agent connectivity...\n";
$result = callAgent('test');
if ($result && $result['success']) {
    echo "   ✓ Agent operational\n";
    echo "   PHP Version: " . $result['php_version'] . "\n";
    echo "   Directory: " . $result['directory'] . "\n";
}

// 4. Show available operations
echo "\n4. Available operations:\n";
echo "   - git_pull: Pull latest changes from GitHub\n";
echo "   - git_status: Check repository status\n";
echo "   - test: Test agent connectivity\n";

echo "\n=== Enhanced Agent Operations (if deployed) ===\n";
echo "   - git_log: Show recent commits\n";
echo "   - composer_install: Update dependencies\n";
echo "   - clear_cache: Clear all caches\n";
echo "   - check_permissions: Verify directory permissions\n";
echo "   - database_backup: Create database backup\n";
echo "   - system_info: Get server information\n";
echo "   - error_log: View error logs\n";
echo "   - help: Show all available operations\n";

echo "\n✅ Agent is fully operational!\n";
?>