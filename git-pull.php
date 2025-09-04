<?php
/**
 * Simple Git Pull Script
 * Pulls latest changes from GitHub
 */

$token = 'agent-' . date('Ymd');

// Security check
if (($_GET['token'] ?? '') !== $token) {
    die("Invalid token. Use: ?token=$token");
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Git Pull</title>
    <style>
        body { font-family: monospace; margin: 20px; background: #f5f5f5; }
        pre { background: white; padding: 20px; border: 1px solid #ddd; }
        .success { color: green; }
        .error { color: red; }
        .button { padding: 10px 20px; background: #007bff; color: white; text-decoration: none; display: inline-block; margin: 10px; }
    </style>
</head>
<body>


<h1>Git Pull - Update from GitHub</h1>

<?php
$action = $_GET['action'] ?? '';

if ($action === 'pull'):
?>

<h2>Pulling Latest Changes...</h2>
<pre>
<?php
// Change to the repository directory
chdir(__DIR__);

// Execute git pull
echo "$ git fetch origin\n";
exec('git fetch origin 2>&1', $fetch_output, $fetch_return);
foreach ($fetch_output as $line) {
    echo htmlspecialchars($line) . "\n";
}

echo "\n$ git pull origin main\n";
exec('git pull origin main 2>&1', $pull_output, $pull_return);
foreach ($pull_output as $line) {
    echo htmlspecialchars($line) . "\n";
}

if ($pull_return === 0) {
    echo "\n<span class='success'>✅ Pull successful!</span>\n";
    
    // Show what changed
    if (strpos(implode(' ', $pull_output), 'Already up to date') === false) {
        echo "\n$ git diff --name-only HEAD@{1} HEAD\n";
        exec('git diff --name-only HEAD@{1} HEAD 2>&1', $diff_output);
        echo "Files changed:\n";
        foreach ($diff_output as $file) {
            echo "  - " . htmlspecialchars($file) . "\n";
        }
    }
} else {
    echo "\n<span class='error'>❌ Pull failed with return code: $pull_return</span>\n";
}

// Show current status
echo "\n$ git status --short\n";
exec('git status --short 2>&1', $status_output);
foreach ($status_output as $line) {
    echo htmlspecialchars($line) . "\n";
}

// Show recent commits
echo "\n$ git log --oneline -5\n";
exec('git log --oneline -5 2>&1', $log_output);
foreach ($log_output as $line) {
    echo htmlspecialchars($line) . "\n";
}
?>
</pre>

<p>
    <a href="?token=<?= $token ?>" class="button">Back</a>
    <a href="/" class="button">View Site</a>
    <a href="/safe-db-fix.php" class="button">Fix Database</a>
</p>

<?php else: ?>

<h2>Current Repository Status</h2>
<pre>
<?php
chdir(__DIR__);

// Show current branch
echo "$ git branch --show-current\n";
$branch = trim(shell_exec('git branch --show-current 2>&1'));
echo $branch . "\n";

// Show status
echo "\n$ git status --short\n";
$status = shell_exec('git status --short 2>&1');
echo htmlspecialchars($status ?: "Working tree clean\n");

// Show recent commits
echo "\n$ git log --oneline -5\n";
$log = shell_exec('git log --oneline -5 2>&1');
echo htmlspecialchars($log);

// Check for updates
echo "\n$ git fetch --dry-run\n";
$fetch_check = shell_exec('git fetch --dry-run 2>&1');
echo htmlspecialchars($fetch_check ?: "No updates available\n");
?>
</pre>

<p>
    <a href="?action=pull&token=<?= $token ?>" class="button" onclick="return confirm('Pull latest changes from GitHub?')">
        📥 Pull Latest Changes
    </a>
</p>

<h3>Other Tools:</h3>
<ul>
    <li><a href="/list-files.php">List Files</a></li>
    <li><a href="/safe-db-fix.php">Fix Database</a></li>
    <li><a href="/debug-production.php">Debug Tool</a></li>
    <li><a href="/admin/login.php">Admin Login</a></li>
</ul>

<?php endif; ?>

</body>
</html>