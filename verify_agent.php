<?php
// Verify the agent is working after manual pull
echo "=== Verifying Agent After Fix ===\n\n";

// Test the agent
$url = 'https://dalthaus.net/agent.php?action=test&key=dalthaus_agent_key_2025';
$response = @file_get_contents($url);

if ($response === false) {
    echo "❌ Agent still not responding.\n";
    echo "Please run on the server:\n";
    echo "  cd /home/dalthaus/public_html/www\n";
    echo "  git pull origin main\n\n";
} else {
    $data = json_decode($response, true);
    if ($data && $data['success']) {
        echo "✅ Agent is working!\n";
        echo "PHP Version: " . $data['php_version'] . "\n";
        echo "Directory: " . $data['directory'] . "\n\n";
        
        // Now do a git pull
        echo "Attempting git pull via agent...\n";
        $pull_url = 'https://dalthaus.net/agent.php?action=git_pull&key=dalthaus_agent_key_2025';
        $pull_response = @file_get_contents($pull_url);
        
        if ($pull_response) {
            $pull_data = json_decode($pull_response, true);
            if ($pull_data['success']) {
                echo "✅ Git pull successful!\n";
                echo "Output:\n";
                foreach ($pull_data['output'] as $line) {
                    echo "  $line\n";
                }
                echo "\n🎉 The site should now be working at https://dalthaus.net/\n";
            } else {
                echo "Git pull failed:\n";
                print_r($pull_data);
            }
        }
    } else {
        echo "Agent responded but with unexpected format:\n";
        echo $response . "\n";
    }
}
?>