<?php
// Test the simple agent
echo "=== Testing Simple Agent ===\n\n";

// First test if it exists
echo "1. Testing agent availability...\n";
$test_url = 'https://dalthaus.net/simple_agent.php?action=test&key=dalthaus_agent_key_2025';
$response = @file_get_contents($test_url);

if ($response === false) {
    echo "   Simple agent not yet available. Please pull latest changes first:\n";
    echo "   cd /home/dalthaus/public_html/www\n";
    echo "   git pull origin main\n\n";
} else {
    echo "   ✓ Agent responded!\n";
    $data = json_decode($response, true);
    print_r($data);
    
    // Now try git pull
    echo "\n2. Attempting git pull...\n";
    $pull_url = 'https://dalthaus.net/simple_agent.php?action=git_pull&key=dalthaus_agent_key_2025';
    $pull_response = @file_get_contents($pull_url);
    
    if ($pull_response) {
        $pull_data = json_decode($pull_response, true);
        if ($pull_data['success']) {
            echo "   ✓ Git pull successful!\n";
            echo "   Output:\n";
            foreach ($pull_data['output'] as $line) {
                echo "   $line\n";
            }
        } else {
            echo "   ✗ Git pull failed\n";
            print_r($pull_data);
        }
    }
}

echo "\n=== Test Complete ===\n";
?>