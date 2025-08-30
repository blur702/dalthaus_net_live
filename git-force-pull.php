<?php
/**
 * Force Git Pull - Handles conflicts
 */

$token = 'agent-' . date('Ymd');

if (($_GET['token'] ?? '') !== $token) {
    die("Invalid token. Use: ?token=$token");
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Force Git Pull</title>
    <style>
        body { font-family: monospace; margin: 20px; }
        pre { background: #f4f4f4; padding: 15px; border: 1px solid #ddd; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>

<h1>Force Git Pull - Override Local Changes</h1>

<pre>
<?php
chdir(__DIR__);

// Step 1: Stash local changes
echo "$ git stash\n";
exec('git stash 2>&1', $stash_output, $stash_return);
foreach ($stash_output as $line) {
    echo htmlspecialchars($line) . "\n";
}

// Step 2: Pull latest
echo "\n$ git pull origin main\n";
exec('git pull origin main 2>&1', $pull_output, $pull_return);
foreach ($pull_output as $line) {
    echo htmlspecialchars($line) . "\n";
}

if ($pull_return === 0) {
    echo "\n<span class='success'>✅ Pull successful!</span>\n";
    
    // Show what files were updated
    echo "\n$ git diff --name-only HEAD@{1} HEAD 2>/dev/null\n";
    exec('git diff --name-only HEAD@{1} HEAD 2>&1', $diff_output);
    if (!empty($diff_output)) {
        echo "Files updated:\n";
        foreach ($diff_output as $file) {
            echo "  - " . htmlspecialchars($file) . "\n";
            if ($file === 'MASTER_FIX.php') {
                echo "    <span class='success'>✅ MASTER_FIX.php is now available!</span>\n";
            }
        }
    }
} else {
    echo "\n<span class='error'>❌ Pull failed</span>\n";
    
    // Try harder - reset to origin
    echo "\n$ git fetch origin\n";
    exec('git fetch origin 2>&1', $fetch_output);
    foreach ($fetch_output as $line) {
        echo htmlspecialchars($line) . "\n";
    }
    
    echo "\n$ git reset --hard origin/main\n";
    exec('git reset --hard origin/main 2>&1', $reset_output);
    foreach ($reset_output as $line) {
        echo htmlspecialchars($line) . "\n";
    }
}

// Step 3: Check if MASTER_FIX.php exists
echo "\n$ ls -la MASTER_FIX.php\n";
$ls_output = shell_exec('ls -la MASTER_FIX.php 2>&1');
echo htmlspecialchars($ls_output);

if (file_exists('MASTER_FIX.php')) {
    echo "\n<span class='success'>✅ MASTER_FIX.php is ready to run!</span>\n";
    echo "\n<a href='/MASTER_FIX.php'>→ Run MASTER FIX</a>\n";
} else {
    echo "\n<span class='error'>❌ MASTER_FIX.php not found</span>\n";
}
?>
</pre>

</body>
</html>