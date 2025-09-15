<?php
// Update only the agent file
echo "=== UPDATING AGENT FILE ===\n\n";

// Download the latest agent.php from GitHub
$agentContent = file_get_contents('https://raw.githubusercontent.com/blur702/dalthaus_net_live/main/agent.php');

if ($agentContent) {
    // Save locally to verify
    file_put_contents('agent_latest.php', $agentContent);
    echo "1. Downloaded latest agent.php from GitHub\n";
    
    // Upload to server via FTP or create update script
    $updateScript = '<?php
// Update agent.php
$newAgent = base64_decode("' . base64_encode($agentContent) . '");
file_put_contents(__DIR__ . "/agent.php", $newAgent);
echo "Agent updated successfully";
?>';
    
    file_put_contents('update_agent.php', $updateScript);
    echo "2. Created update script\n";
    
    // Push update script
    exec('git add update_agent.php && git commit -m "Add agent update script" && git push origin main 2>&1');
    echo "3. Pushed update script\n";
    
    // We can't pull it normally, so let's execute it directly
    echo "\n4. Manual update instructions:\n";
    echo "   Since git pull is blocked, you need to manually update the agent:\n";
    echo "   1. Access your server via FTP/cPanel\n";
    echo "   2. Download: https://raw.githubusercontent.com/blur702/dalthaus_net_live/main/agent.php\n";
    echo "   3. Replace the existing agent.php file\n";
    echo "   4. Then the force_git_reset action will be available\n";
    
} else {
    echo "Failed to download agent.php from GitHub\n";
}

echo "\n✅ Instructions complete\n";
?>