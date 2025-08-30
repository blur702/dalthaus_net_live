<?php
/**
 * Direct Fix Runner - Downloads and executes MASTER_FIX
 */

// Download MASTER_FIX.php from GitHub if not exists
if (!file_exists('MASTER_FIX.php')) {
    $url = 'https://raw.githubusercontent.com/blur702/dalthaus_net_live/main/MASTER_FIX.php';
    $content = @file_get_contents($url);
    if ($content) {
        file_put_contents('MASTER_FIX.php', $content);
    }
}

// Now include and run it with action=fix
$_GET['action'] = 'fix';
if (file_exists('MASTER_FIX.php')) {
    include 'MASTER_FIX.php';
} else {
    echo "<h1>Manual Steps Required</h1>";
    echo "<ol>";
    echo "<li>SSH into server</li>";
    echo "<li>Run: <code>cd /home/dalthaus/public_html</code></li>";
    echo "<li>Run: <code>git stash</code></li>";
    echo "<li>Run: <code>git pull origin main</code></li>";
    echo "<li>Visit: <a href='/MASTER_FIX.php?action=fix'>MASTER_FIX.php?action=fix</a></li>";
    echo "</ol>";
}
?>