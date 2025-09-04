<?php
/**
 * Production Debug Script using Remote File Agent
 * Checks and fixes common issues on the server
 */

$token = 'agent-' . date('Ymd');
$agent_url = 'https://dalthaus.net/remote-file-agent.php';

// Function to call the agent
function callAgent($url, $token, $action, $params = []) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, array_merge(
        ['action' => $action, 'token' => $token],
        $params
    ));
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Production Debug & Fix</title>
    <style>
        body { font-family: monospace; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; }
        h1 { color: #333; border-bottom: 3px solid #3498db; padding-bottom: 10px; }
        h2 { color: #2c3e50; margin-top: 30px; }
        pre { background: #f8f8f8; padding: 15px; border-left: 4px solid #3498db; overflow-x: auto; }
        .success { color: #27ae60; font-weight: bold; }
        .error { color: #e74c3c; font-weight: bold; }
        .warning { color: #f39c12; font-weight: bold; }
        .fix-btn { background: #3498db; color: white; padding: 10px 20px; border: none; cursor: pointer; margin: 5px; }
        .fix-btn:hover { background: #2980b9; }
        .status-box { background: #ecf0f1; padding: 15px; margin: 15px 0; border-radius: 5px; }
    </style>
</head>
<body>

<div class="container">

<h1>🔍 Production Debug & Fix Tool</h1>

<?php
$action = $_GET['action'] ?? 'check';

if ($action === 'check'):
?>

<h2>1. Checking Git Repository</h2>
<div class="status-box">
<?php
// Check if .git exists
$git_check = callAgent($agent_url, $token, 'exists', ['path' => '.git']);
if ($git_check['success'] && $git_check['exists']) {
    echo "<span class='success'>✅ Git repository found</span><br>";
    
    // Try to read git config
    $git_config = callAgent($agent_url, $token, 'read', ['path' => '.git/config']);
    if ($git_config['success']) {
        echo "<span class='success'>✅ Git config readable</span><br>";
        if (strpos($git_config['content'], 'blur702/dalthaus_net_live') !== false) {
            echo "<span class='success'>✅ Correct repository configured</span><br>";
        }
    }
    
    // Check HEAD
    $git_head = callAgent($agent_url, $token, 'read', ['path' => '.git/HEAD']);
    if ($git_head['success']) {
        $branch = trim(str_replace('ref: refs/heads/', '', $git_head['content']));
        echo "<span class='success'>✅ Current branch: $branch</span><br>";
    }
} else {
    echo "<span class='error'>❌ Git repository not found!</span><br>";
    echo "<a href='?action=init-git' class='fix-btn'>Initialize Git Repository</a>";
}
?>
</div>

<h2>2. Checking Missing Files</h2>
<div class="status-box">
<?php
// Check for git agent files
$files_to_check = [
    'remote-git-agent.php' => 'Git agent for remote git operations',
    'test-git-agent.php' => 'Git agent test script',
    'test-agent.php' => 'File agent test script'
];

$missing_files = [];
foreach ($files_to_check as $file => $description) {
    $check = callAgent($agent_url, $token, 'exists', ['path' => $file]);
    if ($check['success'] && $check['exists']) {
        echo "<span class='success'>✅ $file exists</span><br>";
    } else {
        echo "<span class='warning'>⚠️ $file missing - $description</span><br>";
        $missing_files[] = $file;
    }
}

if (!empty($missing_files)) {
    echo "<br><a href='?action=pull-latest' class='fix-btn'>Pull Latest Files from GitHub</a>";
}
?>
</div>

<h2>3. Checking Database Configuration</h2>
<div class="status-box">
<?php
// Check config files
$config_check = callAgent($agent_url, $token, 'read', ['path' => 'includes/config.local.php']);
if ($config_check['success']) {
    $config = $config_check['content'];
    
    // Extract database settings
    preg_match("/define\('DB_HOST',\s*'([^']+)'\)/", $config, $host_match);
    preg_match("/define\('DB_USER',\s*'([^']+)'\)/", $config, $user_match);
    preg_match("/define\('DB_NAME',\s*'([^']+)'\)/", $config, $name_match);
    
    echo "Database Configuration:<br>";
    echo "Host: " . ($host_match[1] ?? 'unknown') . "<br>";
    echo "User: " . ($user_match[1] ?? 'unknown') . "<br>";
    echo "Database: " . ($name_match[1] ?? 'unknown') . "<br>";
    
    // Test connection with a simple PHP script
    $test_script = '<?php
    require_once "includes/config.local.php";
    $conn = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn) {
        echo "SUCCESS";
        mysqli_close($conn);
    } else {
        echo "FAILED: " . mysqli_connect_error();
    }
    ?>';
    
    // Write test script
    $write_result = callAgent($agent_url, $token, 'write', [
        'path' => 'test-db-connection.php',
        'content' => $test_script
    ]);
    
    if ($write_result['success']) {
        // Test the connection
        $db_test = file_get_contents('https://dalthaus.net/test-db-connection.php');
        if (strpos($db_test, 'SUCCESS') === 0) {
            echo "<br><span class='success'>✅ Database connection working</span>";
        } else {
            echo "<br><span class='error'>❌ Database connection failed: " . htmlspecialchars($db_test) . "</span>";
            echo "<br><a href='?action=fix-database' class='fix-btn'>Fix Database Configuration</a>";
        }
        
        // Clean up test file
        callAgent($agent_url, $token, 'delete', ['path' => 'test-db-connection.php']);
    }
} else {
    echo "<span class='warning'>⚠️ config.local.php not found, checking main config...</span><br>";
    
    $main_config = callAgent($agent_url, $token, 'read', ['path' => 'includes/config.php']);
    if ($main_config['success']) {
        echo "<span class='success'>✅ Main config.php found</span><br>";
    }
}
?>
</div>

<h2>4. Checking CSS and Assets</h2>
<div class="status-box">
<?php
$css_check = callAgent($agent_url, $token, 'exists', ['path' => 'assets/css/public.css']);
if ($css_check['success'] && $css_check['exists']) {
    echo "<span class='success'>✅ public.css exists</span><br>";
    
    // Check if CSS is accessible via HTTP
    $css_headers = get_headers('https://dalthaus.net/assets/css/public.css', 1);
    if ($css_headers && strpos($css_headers[0], '200') !== false) {
        echo "<span class='success'>✅ CSS is accessible via HTTP</span><br>";
        if (isset($css_headers['Content-Type']) && strpos($css_headers['Content-Type'], 'text/css') !== false) {
            echo "<span class='success'>✅ CSS has correct MIME type</span><br>";
        } else {
            echo "<span class='warning'>⚠️ CSS MIME type may be incorrect</span><br>";
        }
    } else {
        echo "<span class='error'>❌ CSS not accessible via HTTP</span><br>";
        echo "<a href='?action=fix-htaccess' class='fix-btn'>Fix .htaccess</a>";
    }
} else {
    echo "<span class='error'>❌ public.css not found!</span><br>";
}
?>
</div>

<h2>5. Quick Actions</h2>
<div class="status-box">
    <a href="?action=pull-latest" class="fix-btn">📥 Git Pull Latest</a>
    <a href="?action=clear-cache" class="fix-btn">🗑️ Clear Cache</a>
    <a href="?action=check-permissions" class="fix-btn">🔐 Check Permissions</a>
    <a href="?action=view-logs" class="fix-btn">📋 View Error Logs</a>
</div>

<?php elseif ($action === 'pull-latest'): ?>

<h2>Pulling Latest Changes from GitHub</h2>
<div class="status-box">
<?php
// Create a git pull script
$git_script = '<?php
chdir(__DIR__);
exec("git pull origin main 2>&1", $output, $return);
echo implode("\n", $output);
echo "\nReturn code: " . $return;
?>';

$write_result = callAgent($agent_url, $token, 'write', [
    'path' => 'git-pull-temp.php',
    'content' => $git_script
]);

if ($write_result['success']) {
    // Execute the git pull
    $result = file_get_contents('https://dalthaus.net/git-pull-temp.php');
    echo "<pre>" . htmlspecialchars($result) . "</pre>";
    
    // Clean up
    callAgent($agent_url, $token, 'delete', ['path' => 'git-pull-temp.php']);
    
    if (strpos($result, 'Already up to date') !== false || strpos($result, 'Fast-forward') !== false) {
        echo "<span class='success'>✅ Git pull completed successfully</span>";
    } else if (strpos($result, 'error') !== false || strpos($result, 'fatal') !== false) {
        echo "<span class='error'>❌ Git pull failed. Manual intervention may be required.</span>";
    }
}
?>
<br><br>
<a href="?action=check" class="fix-btn">← Back to Check</a>
</div>

<?php elseif ($action === 'clear-cache'): ?>

<h2>Clearing Cache</h2>
<div class="status-box">
<?php
// List cache files
$cache_list = callAgent($agent_url, $token, 'list', ['path' => 'cache']);
if ($cache_list['success']) {
    $cleared = 0;
    foreach ($cache_list['files'] as $file) {
        if ($file['name'] !== 'index.html' && $file['type'] === 'file') {
            $delete_result = callAgent($agent_url, $token, 'delete', ['path' => 'cache/' . $file['name']]);
            if ($delete_result['success']) {
                $cleared++;
            }
        }
    }
    echo "<span class='success'>✅ Cleared $cleared cache files</span>";
} else {
    echo "<span class='warning'>⚠️ Could not access cache directory</span>";
}
?>
<br><br>
<a href="?action=check" class="fix-btn">← Back to Check</a>
</div>

<?php elseif ($action === 'view-logs'): ?>

<h2>Recent Error Logs</h2>
<div class="status-box">
<?php
$log_content = callAgent($agent_url, $token, 'read', ['path' => 'logs/php_errors.log']);
if ($log_content['success']) {
    $lines = explode("\n", $log_content['content']);
    $recent_lines = array_slice($lines, -50); // Last 50 lines
    echo "<pre>" . htmlspecialchars(implode("\n", $recent_lines)) . "</pre>";
} else {
    echo "<span class='warning'>⚠️ No error log found or empty</span>";
}
?>
<br><br>
<a href="?action=check" class="fix-btn">← Back to Check</a>
</div>

<?php elseif ($action === 'check-permissions'): ?>

<h2>Checking File Permissions</h2>
<div class="status-box">
<?php
$paths_to_check = [
    'cache' => '755',
    'uploads' => '755',
    'logs' => '755',
    '.htaccess' => '644',
    'includes/config.php' => '644'
];

foreach ($paths_to_check as $path => $expected) {
    $exists = callAgent($agent_url, $token, 'exists', ['path' => $path]);
    if ($exists['success'] && $exists['exists']) {
        echo "✅ $path exists<br>";
    } else {
        echo "❌ $path not found<br>";
    }
}
?>
<br>
<a href="?action=check" class="fix-btn">← Back to Check</a>
</div>

<?php endif; ?>

<hr style="margin-top: 50px;">
<p style="color: #666; text-align: center;">
    Debug Tool | Token: <?= $token ?> | 
    <a href="https://dalthaus.net/" target="_blank">View Site</a> | 
    <a href="https://dalthaus.net/admin/" target="_blank">Admin Panel</a>
</p>

</div>
</body>
</html>