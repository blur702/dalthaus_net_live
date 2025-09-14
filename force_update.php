<?php
// Force update from git
header("Content-Type: text/plain");

echo "=== FORCE UPDATING FROM GIT ===\n\n";

// Reset local changes and pull
$commands = [
    "git reset --hard HEAD" => "Reset local changes",
    "git clean -fd" => "Clean untracked files", 
    "git pull origin main" => "Pull from GitHub"
];

foreach ($commands as $cmd => $desc) {
    echo "$desc...\n";
    exec("cd " . __DIR__ . " && $cmd 2>&1", $output, $returnCode);
    foreach ($output as $line) {
        echo "  $line\n";
    }
    $output = [];
}

// Clear opcache
if (function_exists("opcache_reset")) {
    opcache_reset();
    echo "\nOPcache cleared\n";
}

echo "\n✅ Force update complete\n";
?>