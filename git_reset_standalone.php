<?php
// Standalone git reset script - upload this manually
if (isset($_GET["key"]) && $_GET["key"] === "dalthaus_agent_key_2025") {
    header("Content-Type: text/plain");
    
    echo "=== FORCE GIT RESET AND PULL ===\n\n";
    
    // Show current status first
    echo "1. Current git status:\n";
    exec("git status --porcelain 2>&1", $statusOutput);
    foreach ($statusOutput as $line) {
        echo "   $line\n";
    }
    
    echo "\n2. Resetting to match GitHub...\n";
    
    // Execute git reset commands
    $commands = [
        "git reset --hard HEAD" => "Reset all changes",
        "git clean -fd" => "Remove untracked files", 
        "git pull origin main" => "Pull latest from GitHub"
    ];
    
    foreach ($commands as $cmd => $desc) {
        echo "$desc: ";
        $output = [];
        $returnCode = 0;
        exec("$cmd 2>&1", $output, $returnCode);
        
        if ($returnCode === 0) {
            echo "✓ SUCCESS\n";
        } else {
            echo "❌ FAILED (code: $returnCode)\n";
        }
        
        foreach ($output as $line) {
            echo "   $line\n";
        }
        echo "\n";
    }
    
    // Clear opcache
    if (function_exists("opcache_reset")) {
        opcache_reset();
        echo "OPcache cleared\n";
    }
    
    echo "\n✅ Git reset complete!\n";
    echo "Try accessing /admin/login now.\n";
    
} else {
    http_response_code(403);
    echo "Access denied";
}
?>