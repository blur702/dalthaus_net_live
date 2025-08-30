<?php
/**
 * List files on the server
 */

$token = 'agent-' . date('Ymd');
$agent_url = 'https://dalthaus.net/remote-file-agent.php';

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

echo "<h1>Files on Server</h1>";

// List root directory files
$result = callAgent($agent_url, $token, 'list', ['path' => '.']);

if ($result['success']) {
    echo "<h2>Root Directory Files:</h2>";
    echo "<pre>";
    
    $php_files = [];
    $directories = [];
    $other_files = [];
    
    foreach ($result['files'] as $file) {
        if ($file['type'] === 'directory') {
            $directories[] = $file['name'] . '/';
        } elseif (substr($file['name'], -4) === '.php') {
            $php_files[] = $file['name'];
        } else {
            $other_files[] = $file['name'];
        }
    }
    
    echo "PHP Files:\n";
    foreach ($php_files as $file) {
        echo "  - $file\n";
    }
    
    echo "\nDirectories:\n";
    foreach ($directories as $dir) {
        echo "  - $dir\n";
    }
    
    echo "\nOther Files:\n";
    foreach ($other_files as $file) {
        echo "  - $file\n";
    }
    
    echo "</pre>";
    
    // Check for specific files we expect
    echo "<h2>Checking Expected Files:</h2>";
    echo "<pre>";
    
    $expected_files = [
        'emergency-db-fix.php',
        'fix-database-config.php',
        'debug-production.php',
        'remote-file-agent.php',
        'remote-git-agent.php',
        'index.php',
        'setup.php'
    ];
    
    foreach ($expected_files as $file) {
        $exists = callAgent($agent_url, $token, 'exists', ['path' => $file]);
        if ($exists['success'] && $exists['exists']) {
            echo "✅ $file exists\n";
        } else {
            echo "❌ $file NOT FOUND\n";
        }
    }
    
    echo "</pre>";
} else {
    echo "<p>Error: " . ($result['error'] ?? 'Could not list files') . "</p>";
}

// Check git status
echo "<h2>Git Status:</h2>";
echo "<pre>";
system("cd /home/dalthaus/public_html && git status --short 2>&1");
echo "</pre>";

echo "<h2>Recent Git Log:</h2>";
echo "<pre>";
system("cd /home/dalthaus/public_html && git log --oneline -5 2>&1");
echo "</pre>";
?>