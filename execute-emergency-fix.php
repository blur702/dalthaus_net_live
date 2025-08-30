<?php
/**
 * Emergency Fix Execution
 * Forces git update and runs comprehensive fixes
 */

echo "<h1>🚨 Emergency Fix Execution</h1>";
echo "<pre>";

// Step 1: Force git update
echo "=== STEP 1: Forcing Git Update ===\n";
chdir(__DIR__);

echo "$ git stash --include-untracked\n";
$output = shell_exec('git stash --include-untracked 2>&1');
echo $output . "\n";

echo "$ git fetch origin\n";
$output = shell_exec('git fetch origin 2>&1');
echo $output . "\n";

echo "$ git reset --hard origin/main\n";
$output = shell_exec('git reset --hard origin/main 2>&1');
echo $output . "\n";

// Step 2: Check if MASTER_FIX.php exists
echo "\n=== STEP 2: Checking for MASTER_FIX.php ===\n";
if (file_exists('MASTER_FIX.php')) {
    echo "✅ MASTER_FIX.php found!\n";
    echo "</pre>";
    
    // Redirect to MASTER_FIX with action=fix
    echo "<h2>Redirecting to MASTER FIX...</h2>";
    echo "<meta http-equiv='refresh' content='2;url=/MASTER_FIX.php?action=fix'>";
    echo "<p>If not redirected, <a href='/MASTER_FIX.php?action=fix'>click here</a></p>";
} else {
    echo "❌ MASTER_FIX.php not found\n";
    echo "\n=== Creating MASTER_FIX.php directly ===\n";
    
    // Get it from GitHub raw
    $url = 'https://raw.githubusercontent.com/blur702/dalthaus_net_live/main/MASTER_FIX.php';
    $content = file_get_contents($url);
    
    if ($content) {
        file_put_contents('MASTER_FIX.php', $content);
        echo "✅ Downloaded and created MASTER_FIX.php\n";
        echo "</pre>";
        
        echo "<h2>File created! Running fixes...</h2>";
        echo "<meta http-equiv='refresh' content='2;url=/MASTER_FIX.php?action=fix'>";
        echo "<p>If not redirected, <a href='/MASTER_FIX.php?action=fix'>click here</a></p>";
    } else {
        echo "❌ Could not download from GitHub\n";
        echo "</pre>";
    }
}
?>