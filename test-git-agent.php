<?php
/**
 * Test script for remote git agent
 */

$agent_url = 'https://dalthaus.net/remote-git-agent.php';
$token = 'agent-' . date('Ymd');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Git Agent Test</title>
    <style>
        body { font-family: monospace; margin: 20px; }
        pre { background: #f4f4f4; padding: 15px; border: 1px solid #ddd; overflow-x: auto; }
        .success { color: green; }
        .error { color: red; }
        .command { background: #333; color: #0f0; padding: 5px; margin: 10px 0; }
        h2 { border-bottom: 2px solid #333; padding-bottom: 5px; }
    </style>
</head>
<body>

<h1>Remote Git Agent Test</h1>

<?php
function testGitCommand($url, $token, $action, $params = []) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    
    $postData = array_merge(['action' => $action, 'token' => $token], $params);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    $result = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['success' => false, 'error' => 'Invalid JSON response', 'raw' => $response];
    }
    
    return $result;
}

// Test 1: Git Info
echo "<h2>1. Git Repository Info</h2>";
echo "<div class='command'>git info</div>";
$result = testGitCommand($agent_url, $token, 'info');
if ($result['success']) {
    echo "<span class='success'>✅ Repository info retrieved</span>";
    echo "<pre>";
    echo "Current Branch: " . $result['info']['current_branch'] . "\n";
    echo "Last Commit: " . $result['info']['last_commit'] . "\n";
    echo "Working Directory: " . $result['info']['working_directory'] . "\n";
    echo "\nRemotes:\n";
    foreach ($result['info']['remotes'] as $remote) {
        echo "  " . $remote . "\n";
    }
    if (!empty($result['info']['status'])) {
        echo "\nModified Files:\n";
        foreach ($result['info']['status'] as $file) {
            echo "  " . $file . "\n";
        }
    }
    echo "</pre>";
} else {
    echo "<span class='error'>❌ Failed: " . ($result['error'] ?? 'Unknown error') . "</span>";
}

// Test 2: Git Status
echo "<h2>2. Git Status</h2>";
echo "<div class='command'>git status --short</div>";
$result = testGitCommand($agent_url, $token, 'status');
if ($result['success']) {
    echo "<span class='success'>✅ Status retrieved</span>";
    echo "<pre>" . htmlspecialchars($result['output'] ?: 'Working tree clean') . "</pre>";
} else {
    echo "<span class='error'>❌ Failed: " . ($result['error'] ?? 'Unknown error') . "</span>";
}

// Test 3: Git Log
echo "<h2>3. Recent Commits</h2>";
echo "<div class='command'>git log --oneline -n 5</div>";
$result = testGitCommand($agent_url, $token, 'log', ['limit' => '5']);
if ($result['success']) {
    echo "<span class='success'>✅ Log retrieved</span>";
    echo "<pre>";
    foreach ($result['commits'] as $commit) {
        echo $commit['hash'] . " " . $commit['message'] . "\n";
    }
    echo "</pre>";
} else {
    echo "<span class='error'>❌ Failed: " . ($result['error'] ?? 'Unknown error') . "</span>";
}

// Test 4: Git Branches
echo "<h2>4. Branches</h2>";
echo "<div class='command'>git branch -a</div>";
$result = testGitCommand($agent_url, $token, 'branch');
if ($result['success']) {
    echo "<span class='success'>✅ Branches retrieved</span>";
    echo "<pre>";
    foreach ($result['branches'] as $branch) {
        echo ($branch['current'] ? '* ' : '  ') . $branch['name'] . "\n";
    }
    echo "</pre>";
} else {
    echo "<span class='error'>❌ Failed: " . ($result['error'] ?? 'Unknown error') . "</span>";
}

// Test 5: Custom Git Command
echo "<h2>5. Custom Git Command Test</h2>";
echo "<div class='command'>git describe --always --tags</div>";
$result = testGitCommand($agent_url, $token, 'git', [
    'command' => 'describe',
    'args' => '--always --tags'
]);
if ($result['success']) {
    echo "<span class='success'>✅ Command executed</span>";
    echo "<pre>" . htmlspecialchars($result['output']) . "</pre>";
} else {
    echo "<span class='error'>❌ Failed: " . ($result['error'] ?? 'Unknown error') . "</span>";
}

// Interactive section
?>

<h2>6. Try Your Own Git Commands</h2>
<form method="POST">
    <label>Git Command: 
        <select name="test_command">
            <option value="status">status</option>
            <option value="log">log</option>
            <option value="diff">diff</option>
            <option value="branch">branch</option>
            <option value="remote">remote -v</option>
            <option value="fetch">fetch --dry-run</option>
        </select>
    </label>
    <button type="submit">Execute</button>
</form>

<?php
if (isset($_POST['test_command'])) {
    $cmd = $_POST['test_command'];
    echo "<h3>Result of: git $cmd</h3>";
    echo "<div class='command'>git $cmd</div>";
    
    if (strpos($cmd, ' ') !== false) {
        list($command, $args) = explode(' ', $cmd, 2);
        $result = testGitCommand($agent_url, $token, 'git', [
            'command' => $command,
            'args' => $args
        ]);
    } else {
        $result = testGitCommand($agent_url, $token, $cmd);
    }
    
    if ($result['success']) {
        echo "<span class='success'>✅ Success</span>";
        echo "<pre>" . htmlspecialchars($result['output'] ?? json_encode($result, JSON_PRETTY_PRINT)) . "</pre>";
    } else {
        echo "<span class='error'>❌ Failed: " . ($result['error'] ?? 'Unknown error') . "</span>";
    }
}
?>

<h2>Available Actions</h2>
<ul>
    <li><code>status</code> - Get repository status</li>
    <li><code>pull</code> - Pull changes from remote</li>
    <li><code>push</code> - Push changes to remote</li>
    <li><code>commit</code> - Commit changes</li>
    <li><code>diff</code> - Show differences</li>
    <li><code>log</code> - Show commit history</li>
    <li><code>branch</code> - List branches</li>
    <li><code>checkout</code> - Switch branches</li>
    <li><code>stash</code> - Stash changes</li>
    <li><code>reset</code> - Reset to previous state</li>
    <li><code>info</code> - Get repository information</li>
    <li><code>git</code> - Execute custom git command</li>
</ul>

<h2>Direct API Access</h2>
<p>Access the agent directly:</p>
<code>curl -X POST <?= $agent_url ?> -d "action=info&token=<?= $token ?>"</code>

</body>
</html>